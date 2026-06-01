<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Project;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['inbound', 'outbound', 'transfer', 'project_allocation']);

        $warehouseIds = Warehouse::pluck('id')->toArray();
        $sourceWarehouseId = null;
        $destinationWarehouseId = null;
        $projectId = null;

        switch ($type) {
            case 'inbound':
                $destinationWarehouseId = $this->faker->randomElement($warehouseIds);
                break;

            case 'outbound':
                $sourceWarehouseId = $this->faker->randomElement($warehouseIds);
                break;

            case 'transfer':
                $sourceWarehouseId = $this->faker->randomElement($warehouseIds);
                $remainingIds = array_diff($warehouseIds, [$sourceWarehouseId]);
                $destinationWarehouseId = !empty($remainingIds)
                    ? $this->faker->randomElement($remainingIds)
                    : $sourceWarehouseId;
                break;

            case 'project_allocation':
                $sourceWarehouseId = $this->faker->randomElement($warehouseIds);
                $projectId = Project::inRandomOrder()->first()?->id;
                break;
        }

        $notesMap = [
            'inbound' => ['Pembelian dari supplier', 'Retur barang dari proyek', 'Stok awal', 'Pengadaan rutin'],
            'outbound' => ['Pengiriman ke site', 'Barang rusak/disposal', 'Pinjam ke proyek lain'],
            'transfer' => ['Mutasi antar gudang', 'Pemerataan stok', 'Konsolidasi gudang'],
            'project_allocation' => ['Alokasi material proyek', 'Kebutuhan instalasi', 'Penambahan material proyek'],
        ];

        return [
            'type' => $type,
            'item_id' => Item::inRandomOrder()->first()?->id ?? Item::factory(),
            'source_warehouse_id' => $sourceWarehouseId,
            'destination_warehouse_id' => $destinationWarehouseId,
            'project_id' => $projectId,
            'quantity' => $this->faker->numberBetween(1, 100),
            'notes' => $this->faker->optional(0.7)->randomElement($notesMap[$type]),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function inbound(): static
    {
        return $this->state(fn () => ['type' => 'inbound']);
    }

    public function outbound(): static
    {
        return $this->state(fn () => ['type' => 'outbound']);
    }

    public function transfer(): static
    {
        return $this->state(fn () => ['type' => 'transfer']);
    }

    public function projectAllocation(): static
    {
        return $this->state(fn () => ['type' => 'project_allocation']);
    }
}
