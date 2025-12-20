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
        Schema::create('booking_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();

            // Unique voucher code for QR code check-in (e.g., "VOC-ABC123XYZ")
            $table->string('voucher_code', 20)->unique();

            // Participant details (nullable until filled post-checkout)
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('person_type')->nullable(); // adult, child, infant
            $table->text('special_requests')->nullable();

            // Check-in tracking
            $table->boolean('checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();

            $table->timestamps();

            // Indexes for fast lookups
            $table->index('voucher_code');
            $table->index('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_participants');
    }
};
