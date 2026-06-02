<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ItemService
{
    public function getPaginatedItems(array $filters, int $perPage): LengthAwarePaginator
    {
        return Item::query()
            ->with(['category', 'inventories.warehouse'])
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $like = "%{$search}%";

                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createItem(array $data): Item
    {
        return Item::create($data)->load(['category', 'inventories.warehouse']);
    }

    public function getItemDetail(Item $item): Item
    {
        return $item->load(['category', 'inventories.warehouse']);
    }

    public function updateItem(Item $item, array $data): Item
    {
        $item->update($data);

        return $item->load(['category', 'inventories.warehouse']);
    }

    public function deleteItem(Item $item): void
    {
        if (
            $item->inventories()->exists()
            || $item->stockMovements()->exists()
            || $item->projectMaterials()->exists()
        ) {
            throw ValidationException::withMessages([
                'item' => 'Item cannot be deleted because it already has inventory or stock history.',
            ]);
        }

        $item->delete();
    }
}
