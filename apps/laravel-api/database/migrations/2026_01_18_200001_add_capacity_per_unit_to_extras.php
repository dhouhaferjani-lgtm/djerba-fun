<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds capacity_per_unit field to support vehicle capacity tracking.
     * Example: A 4-seat vehicle has capacity_per_unit = 4
     * For a group of 7, the system calculates ceil(7/4) = 2 units needed
     */
    public function up(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            $table->unsignedInteger('capacity_per_unit')
                ->nullable()
                ->after('inventory_count')
                ->comment('Max people per unit. NULL = no capacity limit. E.g., 4 for a 4-seat vehicle.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            $table->dropColumn('capacity_per_unit');
        });
    }
};
