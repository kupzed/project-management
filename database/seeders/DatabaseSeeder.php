<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Activity;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProjectSeeder::class,
        ]);

        // Create some activities and associate them with a project
        $project1 = Project::first();

        if ($project1) {
            Activity::create([
                'name' => 'Design User Interface',
                'description' => 'Designing the UI/UX for the project',
                'project_id' => $project1->id,
                'status' => 'in-progress',
                'due_date' => '2024-07-01',
            ]);

            Activity::create([
                'name' => 'Develop Backend API',
                'description' => 'Developing the backend APIs for data management',
                'project_id' => $project1->id,
                'status' => 'pending',
                'due_date' => '2024-07-15',
            ]);
        }
    }
}