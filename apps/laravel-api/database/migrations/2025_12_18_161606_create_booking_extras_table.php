<?php

declare(strict_types=1);

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
        Schema::create('booking_extras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('extra_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('listing_extra_id')->nullable()->constrained()->nullOnDelete();

            // What was selected
            $table->unsignedInteger('quantity')->default(1);

            // Pricing at time of booking (snapshot)
            $table->string('pricing_type', 20);
            $table->decimal('unit_price_tnd', 10, 2);
            $table->decimal('unit_price_eur', 10, 2);

            // For per_person_type pricing - breakdown with prices
            // {adult: {count: 2, price_tnd: 40, price_eur: 12}, child: {count: 1, price_tnd: 20, price_eur: 6}}
            $table->json('person_type_breakdown')->nullable();

            // Calculated totals
            $table->decimal('subtotal_tnd', 10, 2);
            $table->decimal('subtotal_eur', 10, 2);

            // Reference info (denormalized for history)
            $table->json('extra_name'); // Snapshot of name at booking time
            $table->string('extra_category', 50)->nullable();

            // For inventory tracking
            $table->boolean('inventory_reserved')->default(false);

            // Status
            $table->string('status', 20)->default('active'); // 'active', 'cancelled', 'refunded'

            $table->timestamps();

            // Indexes
            $table->index('booking_id');
            $table->index('extra_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_extras');
    }
};
