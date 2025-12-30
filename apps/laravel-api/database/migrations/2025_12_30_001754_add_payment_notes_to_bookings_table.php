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
            // Payment notes for manual payment tracking
            $table->text('payment_notes')->nullable()->after('cancellation_reason');

            // Track who manually confirmed payment (vendor or admin)
            $table->foreignId('manual_payment_confirmed_by')
                ->nullable()
                ->after('payment_notes')
                ->constrained('users')
                ->nullOnDelete();

            // When manual payment was confirmed
            $table->timestamp('manual_payment_confirmed_at')
                ->nullable()
                ->after('manual_payment_confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['manual_payment_confirmed_by']);
            $table->dropColumn([
                'payment_notes',
                'manual_payment_confirmed_by',
                'manual_payment_confirmed_at',
            ]);
        });
    }
};
