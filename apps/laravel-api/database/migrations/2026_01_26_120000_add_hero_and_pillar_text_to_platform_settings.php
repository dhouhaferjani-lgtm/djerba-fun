<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add translatable text fields for hero section and brand pillars.
     * These allow CMS control over homepage text content.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Hero Section Text (Homepage)
            // Stored as JSON for Spatie Translatable (en/fr)
            // Single title field - frontend will style first word in green, rest in white
            $table->json('hero_title')->nullable()->after('event_of_year_enabled');
            $table->json('hero_subtitle')->nullable()->after('hero_title');

            // Brand Pillar 1 (Sustainable/Tourisme Responsable)
            $table->json('pillar_1_title')->nullable()->after('hero_subtitle');
            $table->json('pillar_1_description')->nullable()->after('pillar_1_title');

            // Brand Pillar 2 (Authentic/Authenticité Garantie)
            $table->json('pillar_2_title')->nullable()->after('pillar_1_description');
            $table->json('pillar_2_description')->nullable()->after('pillar_2_title');

            // Brand Pillar 3 (Adventure/Sensations Fortes)
            $table->json('pillar_3_title')->nullable()->after('pillar_2_description');
            $table->json('pillar_3_description')->nullable()->after('pillar_3_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_title',
                'hero_subtitle',
                'pillar_1_title',
                'pillar_1_description',
                'pillar_2_title',
                'pillar_2_description',
                'pillar_3_title',
                'pillar_3_description',
            ]);
        });
    }
};
