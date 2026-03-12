<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add initiatives text fields to platform_settings table.
 *
 * This migration adds administrable text fields for the About page initiatives section:
 * - Title (translatable)
 * - Description (translatable)
 * - Bullet points (JSON array with translatable text)
 *
 * Note: The existing `about_initiatives` column stores initiative IMAGES.
 * These new fields store the initiative TEXT content (the lime green box).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Initiatives Text Section (the lime green box with bullet points)
            $table->json('about_initiatives_title')->nullable(); // translatable
            $table->json('about_initiatives_description')->nullable(); // translatable
            $table->json('about_initiatives_bullets')->nullable(); // array of {text_en, text_fr}
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'about_initiatives_title',
                'about_initiatives_description',
                'about_initiatives_bullets',
            ]);
        });
    }
};
