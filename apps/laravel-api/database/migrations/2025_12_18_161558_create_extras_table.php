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
        Schema::create('extras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            // Basic Info (translatable)
            $table->json('name'); // {en: "Bike Rental", fr: "Location de vélo"}
            $table->json('description')->nullable();
            $table->json('short_description')->nullable();

            // Media
            $table->string('image_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();

            // Pricing Configuration
            $table->string('pricing_type', 20); // 'per_person', 'per_booking', 'per_unit', 'per_person_type'
            $table->decimal('base_price_tnd', 10, 2)->default(0);
            $table->decimal('base_price_eur', 10, 2)->default(0);

            // Per Person Type Pricing (JSON for flexibility)
            // {adult: {tnd: 50, eur: 15}, child: {tnd: 25, eur: 8}, infant: {tnd: 0, eur: 0}}
            $table->json('person_type_prices')->nullable();

            // Quantity Controls
            $table->unsignedInteger('min_quantity')->default(0); // Minimum if selected (0 = optional)
            $table->unsignedInteger('max_quantity')->nullable(); // NULL = unlimited
            $table->unsignedInteger('default_quantity')->default(1);

            // Inventory (optional)
            $table->boolean('track_inventory')->default(false);
            $table->unsignedInteger('inventory_count')->nullable();

            // Behavior Flags
            $table->boolean('is_required')->default(false); // Must be selected
            $table->boolean('auto_add')->default(false); // Automatically added to booking
            $table->boolean('allow_quantity_change')->default(true);

            // Display
            $table->unsignedInteger('display_order')->default(0);
            $table->string('category', 50)->nullable(); // 'equipment', 'meal', 'insurance', 'upgrade', 'merchandise', 'transport'

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('vendor_id');
            $table->index('category');
            $table->index('is_active');
            $table->index('pricing_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extras');
    }
};
