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
        Schema::create('listing_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('listing_filter_tags')->cascadeOnDelete();
            $table->timestamps();

            // Composite unique constraint - each listing can have a tag only once
            $table->unique(['listing_id', 'tag_id']);

            // Indexes for efficient queries
            $table->index('tag_id');
            $table->index('listing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_tag');
    }
};
