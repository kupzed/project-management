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
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'status')) {
                $table->renameColumn('status', 'kategori');
            }
            if (Schema::hasColumn('activities', 'due_date')) {
                $table->renameColumn('due_date', 'activity_date');
            }
            if (!Schema::hasColumn('activities', 'attachment')) {
                $table->string('attachment', 255)->nullable()->after('activity_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'kategori')) {
                $table->renameColumn('kategori', 'status');
            }
            if (Schema::hasColumn('activities', 'activity_date')) {
                $table->renameColumn('activity_date', 'due_date');
            }
            if (Schema::hasColumn('activities', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
}; 