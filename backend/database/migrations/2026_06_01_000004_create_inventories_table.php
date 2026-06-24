<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('placement', 100)->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'warehouse_id'], 'inventories_item_warehouse_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
