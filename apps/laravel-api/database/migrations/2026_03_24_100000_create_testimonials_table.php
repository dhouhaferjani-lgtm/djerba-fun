<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('photo')->nullable();
            $table->json('text'); // Spatie translatable: {fr: "...", en: "..."}
            $table->unsignedTinyInteger('rating')->default(5); // 1-5 stars
            $table->string('location', 100)->nullable(); // e.g., "Paris, France"
            $table->string('activity', 200)->nullable(); // e.g., "Desert Safari Tour"
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
