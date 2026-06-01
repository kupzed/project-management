<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamp('allocated_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'item_id'], 'project_materials_project_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_materials');
    }
};
