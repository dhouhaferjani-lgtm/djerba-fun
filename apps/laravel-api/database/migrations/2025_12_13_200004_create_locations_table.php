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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->json('name'); // Translatable: {en: "", fr: ""}
            $table->string('slug')->unique();
            $table->json('description')->nullable(); // Translatable
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('address')->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('country', 2);
            $table->string('timezone');
            $table->string('image_url')->nullable();
            $table->integer('listings_count')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('city');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
