<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::create([
            'name' => 'Sample Project One',
            'description' => 'This is a sample project description.',
            'status' => 'active',
            'start_date' => '2024-01-01',
        ]);

        Project::create([
            'name' => 'Sample Project Two',
            'description' => 'Another sample project.',
            'status' => 'completed',
            'start_date' => '2024-02-15',
        ]);
    }
} 