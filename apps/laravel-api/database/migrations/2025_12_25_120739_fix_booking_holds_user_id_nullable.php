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
        Schema::table('booking_holds', function (Blueprint $table) {
            // Make user_id nullable to support guest checkout
            $table->foreignId('user_id')->nullable()->change();

            // Add index for session-based queries
            $table->index(['session_id', 'status']);
        });

        // Ensure either user_id or session_id exists
        DB::statement('ALTER TABLE booking_holds ADD CONSTRAINT booking_holds_user_or_session_check
                       CHECK (user_id IS NOT NULL OR session_id IS NOT NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE booking_holds DROP CONSTRAINT IF EXISTS booking_holds_user_or_session_check');

        Schema::table('booking_holds', function (Blueprint $table) {
            $table->dropIndex(['session_id', 'status']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
