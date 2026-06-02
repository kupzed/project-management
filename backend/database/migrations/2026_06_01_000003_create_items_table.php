<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('unit', 50);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->timestamps();

            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
