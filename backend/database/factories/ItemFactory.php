<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $itemCategory = Category::where('type', 'item')->inRandomOrder()->first();

        $units = ['pcs', 'unit', 'set', 'meter', 'roll', 'box', 'kg', 'lembar', 'batang', 'buah'];

        $itemNames = [
            'Solar Panel 450W Monocrystalline',
            'Solar Panel 550W Bifacial',
            'Solar Panel 330W Polycrystalline',
            'Inverter Hybrid 5kW',
            'Inverter On-grid 10kW',
            'Inverter Off-grid 3kW',
            'Baterai Lithium 100Ah',
            'Baterai Lithium 200Ah',
            'Baterai VRLA 150Ah',
            'Kabel Solar 4mm² PV',
            'Kabel Solar 6mm² PV',
            'Kabel NYY 4x10mm²',
            'Kabel NYA 1x16mm²',
            'Mounting Rail Aluminium 4.2m',
            'Mounting Mid Clamp',
            'Mounting End Clamp',
            'Charge Controller MPPT 60A',
            'Charge Controller PWM 30A',
            'Junction Box IP65',
            'MC4 Connector Male',
            'MC4 Connector Female',
            'Fuse 15A DC',
            'Fuse 20A DC',
            'Bracket L Galvanis',
            'Lampu LED 60W All In One',
            'Lampu LED 100W Two In One',
            'Lampu LED 150W Konvensional',
            'Tiang Oktagonal 7m',
            'Tiang Oktagonal 9m',
            'Tiang Bulat 6m',
            'Combiner Box 4 String',
            'SPD DC 1000V',
            'MCB DC 32A 2P',
            'ACB 3P 100A',
            'Earthing Kit',
            'Grounding Rod 5/8" x 2.4m',
            'Grounding Cable BC 50mm²',
        ];

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('ITM-####-???')),
            'category_id' => $itemCategory?->id ?? Category::factory()->item(),
            'name' => $this->faker->unique()->randomElement($itemNames),
            'unit' => $this->faker->randomElement($units),
            'minimum_stock' => $this->faker->numberBetween(5, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
