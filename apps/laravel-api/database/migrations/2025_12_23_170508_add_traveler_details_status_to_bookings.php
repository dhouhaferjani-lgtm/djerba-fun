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
        Schema::table('bookings', function (Blueprint $table) {
            // Track whether traveler details collection is complete
            $table->enum('traveler_details_status', [
                'not_required',  // Listing doesn't require names
                'pending',       // Required but not yet provided
                'partial',       // Some participants filled, some missing
                'complete',       // All required details collected
            ])->default('not_required')->after('currency');

            // When details were completed (for analytics/SLA tracking)
            $table->timestamp('traveler_details_completed_at')->nullable()->after('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['traveler_details_status', 'traveler_details_completed_at']);
        });
    }
};
