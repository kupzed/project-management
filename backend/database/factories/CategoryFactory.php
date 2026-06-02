<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['item', 'project', 'activity', 'certificate']);

        $namesByType = [
            'item' => [
                'Solar Panel', 'Inverter', 'Baterai', 'Kabel', 'Mounting', 'Controller',
                'Junction Box', 'Connector', 'Fuse', 'Bracket', 'Lampu LED', 'Tiang',
                'Aksesoris', 'Alat Ukur', 'Safety Equipment',
            ],
            'project' => [
                'PLTS Hybrid', 'PLTS Ongrid', 'PLTS Offgrid', 'PJUTS All In One',
                'PJUTS Two In One', 'PJUTS Konvensional', 'Solar Farm', 'Rooftop Solar',
            ],
            'activity' => [
                'Survey', 'Instalasi', 'Commissioning', 'Maintenance',
                'Pengiriman', 'Pembelian', 'Desain', 'Administrasi',
            ],
            'certificate' => [
                'SLO', 'SERTIFIKAT LAIK OPERASI', 'Commissioning Certificate',
                'Warranty Certificate', 'Calibration Certificate', 'Test Certificate',
            ],
        ];

        return [
            'name' => $this->faker->unique()->randomElement($namesByType[$type]),
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function item(): static
    {
        return $this->state(fn () => ['type' => 'item']);
    }

    public function project(): static
    {
        return $this->state(fn () => ['type' => 'project']);
    }

    public function activity(): static
    {
        return $this->state(fn () => ['type' => 'activity']);
    }

    public function certificate(): static
    {
        return $this->state(fn () => ['type' => 'certificate']);
    }
}
