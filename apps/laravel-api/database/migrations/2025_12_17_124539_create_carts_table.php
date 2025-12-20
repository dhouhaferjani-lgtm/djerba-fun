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
        // 1. Carts table - groups multiple booking holds
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable(); // For guest checkout
            $table->string('status')->default('active'); // active, checking_out, completed, abandoned
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('status');
        });

        // 2. Cart items table - individual items in a cart
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('hold_id')->constrained('booking_holds');
            $table->foreignId('listing_id')->constrained();

            // Primary contact info (person paying) - filled during checkout
            $table->json('primary_contact')->nullable();

            // Guest names - only filled if listing requires it
            $table->json('guest_names')->nullable();

            // Selected extras for this item
            $table->json('extras')->nullable();

            // Denormalized for display (avoids joins)
            $table->json('listing_title')->nullable();
            $table->timestamp('slot_start')->nullable();
            $table->timestamp('slot_end')->nullable();
            $table->integer('quantity');
            $table->json('person_type_breakdown')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3)->default('EUR');

            $table->timestamps();

            $table->unique(['cart_id', 'hold_id']);
            $table->index('cart_id');
        });

        // 3. Cart payments table - single payment for entire cart
        Schema::create('cart_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status')->default('pending'); // pending, processing, succeeded, failed
            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_id')->nullable(); // External payment reference
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('cart_id');
            $table->index('status');
        });

        // 4. Junction table: links cart payment to individual bookings
        Schema::create('cart_payment_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2); // Portion of payment for this booking
            $table->timestamps();

            $table->index('cart_payment_id');
            $table->index('booking_id');
        });

        // 5. Add cart_id to booking_holds
        Schema::table('booking_holds', function (Blueprint $table) {
            $table->foreignUuid('cart_id')->nullable()->constrained()->nullOnDelete();
        });

        // 6. Add cart_payment_id to bookings for tracking
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignUuid('cart_payment_id')->nullable()->constrained()->nullOnDelete();
        });

        // 7. Add require_traveler_names to listings (vendor setting)
        Schema::table('listings', function (Blueprint $table) {
            $table->boolean('require_traveler_names')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('require_traveler_names');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cart_payment_id');
        });

        Schema::table('booking_holds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cart_id');
        });

        Schema::dropIfExists('cart_payment_bookings');
        Schema::dropIfExists('cart_payments');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
