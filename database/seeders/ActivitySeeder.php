<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $project1 = Project::first();
        $project2 = Project::find(2);

        if ($project1) {
            Activity::create([
                'name' => 'Expense Report',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Earum possimus, nostrum fugit libero doloribus assumenda id odio laudantium. Culpa magnam suscipit voluptatum atque non ea aliquid, quaerat maxime animi sapiente.',
                'project_id' => $project1->id,
                'kategori' => 'Expense Report',
                'activity_date' => '2025-05-13',
                'attachment' => null,
            ]);

            Activity::create([
                'name' => 'Invoice',
                'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Cupiditate animi perferendis a eos, tempora fugiat illum ad quae neque maiores deserunt ipsum repellat, exercitationem, temporibus vitae? Odit dignissimos impedit ad.',
                'project_id' => $project1->id,
                'kategori' => 'Invoice',
                'activity_date' => '2025-05-15',
                'attachment' => null,
            ]);
            
            Activity::create([
                'name' => 'Quotation',
                'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Itaque eum reiciendis dicta veritatis rerum at sit nostrum aliquam corrupti voluptates libero voluptas accusantium iste eius inventore numquam, harum omnis dolores.',
                'project_id' => $project1->id,
                'kategori' => 'Quotation',
                'activity_date' => '2025-05-17',
                'attachment' => null,
            ]);
        }

        if ($project2) {
            Activity::create([
                'name' => 'Purchase Order',
                'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Officia fugit maiores eos soluta quasi repellat quaerat impedit debitis nam. Earum, eligendi. Voluptatum quae eligendi perferendis aspernatur sequi earum architecto totam.',
                'project_id' => $project2->id,
                'kategori' => 'Purchase Order',
                'activity_date' => '2025-06-01',
                'attachment' => null,
            ]);

            Activity::create([
                'name' => 'Faktur Pajak',
                'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Asperiores, veritatis? Animi, quibusdam harum consequatur reiciendis, eius distinctio earum, quisquam unde temporibus reprehenderit impedit quis dolorem laudantium? Id deserunt quas ipsum!',
                'project_id' => $project2->id,
                'kategori' => 'Faktur Pajak',
                'activity_date' => '2025-06-05',
                'attachment' => null,
            ]);
        }
    }
} 