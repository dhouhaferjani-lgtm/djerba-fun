<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make user_id nullable for guest checkout using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE bookings ALTER COLUMN user_id DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: this will fail if there are null user_ids
        DB::statement('ALTER TABLE bookings ALTER COLUMN user_id SET NOT NULL');
    }
};
