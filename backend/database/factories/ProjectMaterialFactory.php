<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectMaterialFactory extends Factory
{
    protected $model = ProjectMaterial::class;

    public function definition(): array
    {
        $stockMovement = StockMovement::where('type', 'project_allocation')
            ->inRandomOrder()
            ->first();

        return [
            'project_id' => $stockMovement?->project_id ?? Project::inRandomOrder()->first()?->id ?? Project::factory(),
            'item_id' => $stockMovement?->item_id ?? Item::inRandomOrder()->first()?->id ?? Item::factory(),
            'warehouse_id' => $stockMovement?->source_warehouse_id ?? Warehouse::inRandomOrder()->first()?->id ?? Warehouse::factory(),
            'stock_movement_id' => $stockMovement?->id ?? StockMovement::factory(),
            'quantity' => $stockMovement?->quantity ?? $this->faker->numberBetween(1, 50),
            'allocated_at' => $stockMovement?->occurred_at ?? $this->faker->dateTimeBetween('-6 months', 'now'),
            'notes' => $this->faker->optional(0.5)->randomElement([
                'Material untuk instalasi panel',
                'Kebutuhan mounting struktur',
                'Material kabel dan wiring',
                'Komponen inverter dan controller',
                'Material grounding dan proteksi',
                'Kebutuhan lampu jalan',
                'Aksesoris dan pendukung',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
