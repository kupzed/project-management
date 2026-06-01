<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'item_id' => Item::inRandomOrder()->first()?->id ?? Item::factory(),
            'warehouse_id' => Warehouse::inRandomOrder()->first()?->id ?? Warehouse::factory(),
            'quantity' => $this->faker->numberBetween(0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
