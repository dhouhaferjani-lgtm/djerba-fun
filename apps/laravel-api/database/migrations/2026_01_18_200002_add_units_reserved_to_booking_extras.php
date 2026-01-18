<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds units_reserved field to track how many units were reserved
     * for capacity-based extras (e.g., 2 vehicles for a group of 7).
     */
    public function up(): void
    {
        Schema::table('booking_extras', function (Blueprint $table) {
            $table->unsignedInteger('units_reserved')
                ->nullable()
                ->after('inventory_reserved')
                ->comment('Number of units reserved (for capacity-based extras)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_extras', function (Blueprint $table) {
            $table->dropColumn('units_reserved');
        });
    }
};
