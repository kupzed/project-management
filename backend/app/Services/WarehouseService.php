<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\ProjectMaterial;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function getPaginatedWarehouses(array $filters, int $perPage): LengthAwarePaginator
    {
        return Warehouse::query()
            ->withCount('inventories')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $like = "%{$search}%";

                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('location', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createWarehouse(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function getWarehouseDetail(Warehouse $warehouse): Warehouse
    {
        return $warehouse->load(['inventories.item.category']);
    }

    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse->load(['inventories.item.category']);
    }

    public function deleteWarehouse(Warehouse $warehouse): void
    {
        if (
            $warehouse->inventories()->exists()
            || $warehouse->sourceStockMovements()->exists()
            || $warehouse->destinationStockMovements()->exists()
            || $warehouse->projectMaterials()->exists()
        ) {
            throw ValidationException::withMessages([
                'warehouse' => 'Warehouse cannot be deleted because it already has inventory or stock history.',
            ]);
        }

        $warehouse->delete();
    }

    public function getPaginatedStockMovements(array $filters, int $perPage): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['item.category', 'sourceWarehouse', 'destinationWarehouse', 'project:id,name', 'projectMaterial'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['item_id'] ?? null, fn ($query, $itemId) => $query->where('item_id', $itemId))
            ->when($filters['project_id'] ?? null, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($filters['warehouse_id'] ?? null, function ($query, $warehouseId) {
                $query->where(function ($q) use ($warehouseId) {
                    $q->where('source_warehouse_id', $warehouseId)
                        ->orWhere('destination_warehouse_id', $warehouseId);
                });
            })
            ->orderBy('occurred_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getStockMovementDetail(StockMovement $stockMovement): StockMovement
    {
        return $stockMovement->load([
            'item.category',
            'sourceWarehouse',
            'destinationWarehouse',
            'project:id,name',
            'projectMaterial',
        ]);
    }

    public function recordInbound(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $inventory = $this->getLockedInventory((int) $data['item_id'], (int) $data['destination_warehouse_id']);
            $inventory->update([
                'quantity' => $inventory->quantity + (int) $data['quantity'],
                'placement' => $data['placement'] ?? $inventory->placement,
            ]);

            return StockMovement::create([
                'type' => 'inbound',
                'item_id' => $data['item_id'],
                'source_warehouse_id' => null,
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'project_id' => null,
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'placement' => $data['placement'] ?? null,
            ])->load(['item.category', 'destinationWarehouse']);
        });
    }

    public function recordOutbound(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $this->decreaseInventory(
                (int) $data['item_id'],
                (int) $data['source_warehouse_id'],
                (int) $data['quantity']
            );

            return StockMovement::create([
                'type' => 'outbound',
                'item_id' => $data['item_id'],
                'source_warehouse_id' => $data['source_warehouse_id'],
                'destination_warehouse_id' => null,
                'project_id' => null,
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ])->load(['item.category', 'sourceWarehouse']);
        });
    }

    public function transferStock(array $data): StockMovement
    {
        if ((int) $data['source_warehouse_id'] === (int) $data['destination_warehouse_id']) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'Destination warehouse must be different from source warehouse.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $this->decreaseInventory(
                (int) $data['item_id'],
                (int) $data['source_warehouse_id'],
                (int) $data['quantity']
            );

            $destinationInventory = $this->getLockedInventory(
                (int) $data['item_id'],
                (int) $data['destination_warehouse_id']
            );
            $destinationInventory->update([
                'quantity' => $destinationInventory->quantity + (int) $data['quantity'],
                'placement' => $data['placement'] ?? $destinationInventory->placement,
            ]);

            return StockMovement::create([
                'type' => 'transfer',
                'item_id' => $data['item_id'],
                'source_warehouse_id' => $data['source_warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'project_id' => null,
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'placement' => $data['placement'] ?? null,
            ])->load(['item.category', 'sourceWarehouse', 'destinationWarehouse']);
        });
    }

    public function allocateToProject(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $this->decreaseInventory(
                (int) $data['item_id'],
                (int) $data['warehouse_id'],
                (int) $data['quantity']
            );

            $movement = StockMovement::create([
                'type' => 'project_allocation',
                'item_id' => $data['item_id'],
                'source_warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => null,
                'project_id' => $data['project_id'],
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $data['allocated_at'] ?? now(),
            ]);

            ProjectMaterial::create([
                'project_id' => $data['project_id'],
                'item_id' => $data['item_id'],
                'warehouse_id' => $data['warehouse_id'],
                'stock_movement_id' => $movement->id,
                'quantity' => $data['quantity'],
                'allocated_at' => $data['allocated_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            return $movement->load(['item.category', 'sourceWarehouse', 'project:id,name', 'projectMaterial']);
        });
    }

    /**
     * Update stock movement: reverse old effect, apply new quantity, update record.
     * Only quantity, notes, and occurred_at can be changed.
     */
    public function updateStockMovement(StockMovement $movement, array $data): StockMovement
    {
        return DB::transaction(function () use ($movement, $data) {
            $oldQuantity = (int) $movement->quantity;
            $newQuantity = (int) $data['quantity'];
            $diff = $newQuantity - $oldQuantity;

            if ($diff !== 0) {
                $this->applyQuantityDiff($movement, $diff);
            }

            $movement->update([
                'quantity' => $newQuantity,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $movement->notes,
                'occurred_at' => $data['occurred_at'] ?? $movement->occurred_at,
                'placement' => array_key_exists('placement', $data) ? $data['placement'] : $movement->placement,
            ]);

            // Sync placement to destination inventory for inbound/transfer
            if (in_array($movement->type, ['inbound', 'transfer'], true) && array_key_exists('placement', $data)) {
                $inventory = $this->getLockedInventory(
                    (int) $movement->item_id,
                    (int) $movement->destination_warehouse_id
                );
                $inventory->update([
                    'placement' => $data['placement'],
                ]);
            }

            // Sinkronkan juga ProjectMaterial jika tipe project_allocation
            if ($movement->type === 'project_allocation' && $movement->projectMaterial) {
                $movement->projectMaterial->update([
                    'quantity' => $newQuantity,
                    'notes' => array_key_exists('notes', $data) ? $data['notes'] : $movement->projectMaterial->notes,
                ]);
            }

            return $movement->load([
                'item.category',
                'sourceWarehouse',
                'destinationWarehouse',
                'project:id,name',
                'projectMaterial',
            ]);
        });
    }

    /**
     * Delete stock movement: reverse its inventory effect, remove related records.
     */
    public function deleteStockMovement(StockMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            $this->reverseMovementEffect($movement);

            // Hapus ProjectMaterial terkait jika ada
            if ($movement->type === 'project_allocation' && $movement->projectMaterial) {
                $movement->projectMaterial->delete();
            }

            $movement->delete();
        });
    }

    /**
     * Apply quantity difference to inventory based on movement type.
     * Positive diff = movement quantity increased, negative = decreased.
     */
    private function applyQuantityDiff(StockMovement $movement, int $diff): void
    {
        $type = $movement->type;

        if ($type === 'inbound') {
            // Inbound menambah destination: diff positif = tambah lagi, negatif = kurangi
            $inventory = $this->getLockedInventory($movement->item_id, $movement->destination_warehouse_id);
            $newQty = $inventory->quantity + $diff;
            if ($newQty < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot reduce inbound quantity below what is available in destination warehouse.',
                ]);
            }
            $inventory->update(['quantity' => $newQty]);
        } elseif ($type === 'outbound' || $type === 'project_allocation') {
            // Outbound/allocation mengurangi source: diff positif = kurangi lebih, negatif = kembalikan
            $warehouseId = $movement->source_warehouse_id;
            $inventory = $this->getLockedInventory($movement->item_id, $warehouseId);
            $newQty = $inventory->quantity - $diff;
            if ($newQty < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for the updated quantity.',
                ]);
            }
            $inventory->update(['quantity' => $newQty]);
        } elseif ($type === 'transfer') {
            // Transfer: kurangi lebih dari source, tambah lebih ke destination
            $sourceInv = $this->getLockedInventory($movement->item_id, $movement->source_warehouse_id);
            $newSourceQty = $sourceInv->quantity - $diff;
            if ($newSourceQty < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock in source warehouse for the updated quantity.',
                ]);
            }
            $sourceInv->update(['quantity' => $newSourceQty]);

            $destInv = $this->getLockedInventory($movement->item_id, $movement->destination_warehouse_id);
            $destInv->update(['quantity' => $destInv->quantity + $diff]);
        }
    }

    /**
     * Fully reverse the inventory effect of a stock movement (for delete).
     */
    private function reverseMovementEffect(StockMovement $movement): void
    {
        $type = $movement->type;
        $qty = (int) $movement->quantity;

        if ($type === 'inbound') {
            // Inbound menambah destination → kurangi kembali
            $inventory = $this->getLockedInventory($movement->item_id, $movement->destination_warehouse_id);
            $newQty = $inventory->quantity - $qty;
            if ($newQty < 0) {
                throw ValidationException::withMessages([
                    'stock_movement' => 'Cannot delete: reversing this inbound would result in negative stock.',
                ]);
            }
            $inventory->update(['quantity' => $newQty]);
        } elseif ($type === 'outbound' || $type === 'project_allocation') {
            // Outbound/allocation mengurangi source → tambah kembali
            $inventory = $this->getLockedInventory($movement->item_id, $movement->source_warehouse_id);
            $inventory->update(['quantity' => $inventory->quantity + $qty]);
        } elseif ($type === 'transfer') {
            // Transfer: kurangi destination, tambah source
            $destInv = $this->getLockedInventory($movement->item_id, $movement->destination_warehouse_id);
            $newDestQty = $destInv->quantity - $qty;
            if ($newDestQty < 0) {
                throw ValidationException::withMessages([
                    'stock_movement' => 'Cannot delete: reversing this transfer would result in negative stock in destination warehouse.',
                ]);
            }
            $destInv->update(['quantity' => $newDestQty]);

            $sourceInv = $this->getLockedInventory($movement->item_id, $movement->source_warehouse_id);
            $sourceInv->update(['quantity' => $sourceInv->quantity + $qty]);
        }
    }

    private function getLockedInventory(int $itemId, int $warehouseId): Inventory
    {
        $inventory = Inventory::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            return $inventory;
        }

        return Inventory::create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity' => 0,
        ]);
    }

    private function decreaseInventory(int $itemId, int $warehouseId, int $quantity): void
    {
        $inventory = $this->getLockedInventory($itemId, $warehouseId);

        if ($inventory->quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock for this item in the selected warehouse.',
            ]);
        }

        $inventory->update(['quantity' => $inventory->quantity - $quantity]);
    }
}
