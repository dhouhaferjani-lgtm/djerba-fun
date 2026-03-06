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
        Schema::create('listing_filter_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 50); // TagType enum: tour_type, boat_type, space_type, event_feature, amenity
            $table->json('name'); // Translatable: {"en": "...", "fr": "..."}
            $table->string('slug', 100)->unique();
            $table->json('description')->nullable(); // Translatable description
            $table->string('icon', 100)->nullable(); // Heroicon name (e.g., heroicon-o-map)
            $table->string('color', 50)->nullable(); // Hex color for badge (e.g., #0077B6)
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('listings_count')->default(0); // Denormalized count for performance
            $table->json('applicable_service_types')->nullable(); // ['tour', 'nautical'] - restricts which service types can use this tag
            $table->timestamps();

            // Indexes for efficient filtering
            $table->index('type');
            $table->index('is_active');
            $table->index('display_order');
            $table->index(['type', 'is_active']);
            $table->index(['type', 'is_active', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_filter_tags');
    }
};
