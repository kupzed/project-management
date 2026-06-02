<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $prefixes = ['Gudang', 'Warehouse', 'Storage'];
        $locations = [
            'Bogor', 'Jakarta Barat', 'Jakarta Timur', 'Bandung',
            'Surabaya', 'Semarang', 'Medan', 'Makassar',
            'Tangerang', 'Bekasi', 'Depok', 'Cikarang',
        ];

        $prefix = $this->faker->randomElement($prefixes);
        $location = $this->faker->randomElement($locations);

        return [
            'name' => $prefix . ' ' . $location,
            'location' => $this->faker->address(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
