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
        Schema::table('booking_participants', function (Blueprint $table) {
            // Badge number - sequential per listing/event, nullable for tours
            $table->unsignedInteger('badge_number')->nullable()->after('voucher_code');

            // Composite index for badge number lookups within a booking
            $table->index(['booking_id', 'badge_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_participants', function (Blueprint $table) {
            $table->dropIndex(['booking_id', 'badge_number']);
            $table->dropColumn('badge_number');
        });
    }
};
