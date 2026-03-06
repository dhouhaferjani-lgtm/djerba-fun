<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Rename 'sejour' service type to 'accommodation' and add 'nautical'.
 *
 * Evasion Djerba Service Types:
 * - tour (existing)
 * - nautical (new - for water activities like jet ski, parasailing, diving)
 * - accommodation (renamed from sejour)
 * - event (existing)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename existing 'sejour' records to 'accommodation'
        DB::table('listings')
            ->where('service_type', 'sejour')
            ->update(['service_type' => 'accommodation']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename 'accommodation' back to 'sejour'
        DB::table('listings')
            ->where('service_type', 'accommodation')
            ->update(['service_type' => 'sejour']);

        // Note: We cannot reverse 'nautical' listings as they didn't exist before.
        // Any 'nautical' listings would need to be manually reassigned.
    }
};
