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
        Schema::create('listing_extras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('extra_id')->constrained()->cascadeOnDelete();

            // Override Pricing (optional - NULL uses extra's default)
            $table->decimal('override_price_tnd', 10, 2)->nullable();
            $table->decimal('override_price_eur', 10, 2)->nullable();
            $table->json('override_person_type_prices')->nullable();

            // Override Quantity Limits
            $table->unsignedInteger('override_min_quantity')->nullable();
            $table->unsignedInteger('override_max_quantity')->nullable();

            // Override Behavior
            $table->boolean('override_is_required')->nullable();

            // Availability Constraints
            $table->json('available_for_slots')->nullable(); // NULL = all slots, or [slot_id1, slot_id2]
            $table->json('available_for_person_types')->nullable(); // NULL = all, or ['adult', 'child']

            // Conditional Display Rules (JSON)
            // {conditions: [{field: 'quantity', operator: '>=', value: 4}], action: 'show'}
            $table->json('display_conditions')->nullable();

            // Display
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_featured')->default(false); // Highlight this extra

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Unique constraint - each extra can only be linked to a listing once
            $table->unique(['listing_id', 'extra_id']);

            // Indexes
            $table->index('listing_id');
            $table->index('extra_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_extras');
    }
};
