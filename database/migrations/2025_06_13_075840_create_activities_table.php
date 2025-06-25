<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('kategori', [
                'Expense Report',
                'Invoice',
                'Purchase Order',
                'Payment',
                'Quotation',
                'Faktur Pajak',
                'Kasbon',
                'Laporan Teknis',
                'Surat Masuk',
                'Surat Keluar',
            ])->default('Expense Report');
            $table->date('activity_date');
            $table->string('attachment', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
