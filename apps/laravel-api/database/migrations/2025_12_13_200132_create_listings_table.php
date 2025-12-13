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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('service_type'); // tour, event
            $table->string('status')->default('draft')->index();

            // Common fields
            $table->json('title'); // Translatable
            $table->string('slug')->unique();
            $table->json('summary'); // Translatable
            $table->json('description'); // Translatable
            $table->json('highlights'); // Array of translatable strings
            $table->json('included'); // Array of translatable strings
            $table->json('not_included'); // Array of translatable strings
            $table->json('requirements'); // Array of translatable strings

            // Meeting point
            $table->json('meeting_point'); // {address, coordinates, instructions}

            // Pricing
            $table->json('pricing'); // Full pricing schema
            $table->json('cancellation_policy'); // Full cancellation policy

            // Group size
            $table->integer('min_group_size')->default(1);
            $table->integer('max_group_size');

            // Service-specific fields (nullable for polymorphism)
            // Tour fields
            $table->json('duration')->nullable(); // {value, unit}
            $table->string('difficulty')->nullable();
            $table->json('distance')->nullable(); // {value, unit}
            $table->json('itinerary')->nullable(); // Array of stops
            $table->boolean('has_elevation_profile')->default(false);

            // Event fields
            $table->string('event_type')->nullable(); // festival, workshop, etc.
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('venue')->nullable(); // {name, address, coordinates, capacity}
            $table->json('agenda')->nullable(); // Array of agenda items

            // Stats
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('reviews_count')->default(0);
            $table->integer('bookings_count')->default(0);

            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('vendor_id');
            $table->index('location_id');
            $table->index('service_type');
            $table->index('status');
            $table->index('published_at');
            $table->index(['start_date', 'end_date']); // For events
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
