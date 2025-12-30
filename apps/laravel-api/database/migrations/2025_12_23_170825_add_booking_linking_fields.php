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
            // Add session_id for guest checkout tracking (if not already present)
            if (! Schema::hasColumn('bookings', 'session_id')) {
                $table->string('session_id')->nullable()->after('user_id');
            }

            // Track when/how booking was linked to user account
            $table->timestamp('linked_at')->nullable()->after('cancelled_at');
            $table->enum('linked_method', ['auto', 'manual', 'claimed'])->nullable()->after('linked_at');

            // Add composite index for efficient guest booking lookups
            $table->index(['user_id', 'session_id']);
        });

        // Add JSON index for email lookups (PostgreSQL syntax)
        // This dramatically improves performance when finding claimable bookings by email
        // Skip for SQLite as it doesn't support JSON operators in the same way
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX bookings_billing_contact_email_idx ON bookings ((billing_contact->>\'email\'))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop JSON index first
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS bookings_billing_contact_email_idx');
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'session_id']);
            $table->dropColumn(['linked_at', 'linked_method', 'session_id']);
        });
    }
};
