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
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->json('name');                    // Translatable: {"en": "...", "fr": "..."}
            $table->string('slug', 100)->unique();
            $table->json('description')->nullable(); // Translatable description
            $table->string('icon', 100)->nullable(); // Heroicon name for UI
            $table->string('color', 50)->nullable(); // Badge color
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('listings_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
