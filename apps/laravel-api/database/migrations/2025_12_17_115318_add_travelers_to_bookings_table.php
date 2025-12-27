<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add travelers array column for multi-guest bookings
            // This stores all travelers; traveler_info remains for backward compatibility
            $table->json('travelers')->nullable()->after('traveler_info');
        });

        // Migrate existing bookings: copy traveler_info to travelers array
        DB::statement('
            UPDATE bookings
            SET travelers = JSON_BUILD_ARRAY(traveler_info)
            WHERE traveler_info IS NOT NULL AND travelers IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('travelers');
        });
    }
};
