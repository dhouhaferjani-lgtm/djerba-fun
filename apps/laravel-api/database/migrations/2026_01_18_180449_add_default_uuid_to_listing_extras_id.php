<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes 500 error when adding extras to listings.
     * The id column was NOT NULL without a default value, and Filament's
     * AttachAction bypasses the ListingExtra model's HasUuids trait,
     * causing a "null value in column id violates not-null constraint" error.
     */
    public function up(): void
    {
        // Add default UUID generator for PostgreSQL
        DB::statement("ALTER TABLE listing_extras ALTER COLUMN id SET DEFAULT gen_random_uuid()");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE listing_extras ALTER COLUMN id DROP DEFAULT");
    }
};
