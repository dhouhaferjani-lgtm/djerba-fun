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
        Schema::create('extra_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Basic info (translatable fields stored as JSON)
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('short_description')->nullable();
            $table->string('icon')->nullable(); // Heroicon name

            // Pricing
            $table->string('pricing_type'); // ExtraPricingType enum
            $table->decimal('suggested_price_tnd', 10, 2)->nullable();
            $table->decimal('suggested_price_eur', 10, 2)->nullable();
            $table->json('person_type_prices')->nullable(); // For per_person_type pricing

            // Category
            $table->string('category'); // ExtraCategory enum

            // Quantity constraints
            $table->unsignedInteger('min_quantity')->default(0);
            $table->unsignedInteger('max_quantity')->nullable();

            // Capacity tracking (for vehicles, etc.)
            $table->unsignedInteger('capacity_per_unit')->nullable();

            // Inventory tracking suggestion
            $table->boolean('track_inventory')->default(false);

            // Display
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_templates');
    }
};
