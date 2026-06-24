<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['inbound', 'outbound', 'transfer', 'project_allocation'])->index();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('placement', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['item_id', 'occurred_at'], 'stock_movements_item_date_idx');
            $table->index(['source_warehouse_id', 'destination_warehouse_id'], 'stock_movements_wh_flow_idx');
            $table->index('project_id', 'stock_movements_project_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
