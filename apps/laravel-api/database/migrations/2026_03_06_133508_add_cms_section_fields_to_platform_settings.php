<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add CMS section fields to platform_settings table.
 *
 * This migration adds administration fields for 6 homepage sections:
 * 1. Experience Categories Section
 * 2. Blog Section
 * 3. Featured Packages Section
 * 4. Custom Experience CTA Section
 * 5. Newsletter Section
 * 6. About Page (hero, founder, commitments, partners, initiatives)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // =========================================================================
            // 1. Experience Categories Section
            // =========================================================================
            $table->boolean('experience_categories_enabled')->default(true);
            $table->json('experience_categories_title')->nullable(); // translatable
            $table->json('experience_categories_subtitle')->nullable(); // translatable

            // =========================================================================
            // 2. Blog Section
            // =========================================================================
            $table->boolean('blog_section_enabled')->default(true);
            $table->json('blog_section_title')->nullable(); // translatable
            $table->json('blog_section_subtitle')->nullable(); // translatable
            $table->unsignedTinyInteger('blog_section_post_limit')->default(3);

            // =========================================================================
            // 3. Featured Packages Section
            // =========================================================================
            $table->boolean('featured_packages_enabled')->default(true);
            $table->json('featured_packages_title')->nullable(); // translatable
            $table->json('featured_packages_subtitle')->nullable(); // translatable
            $table->unsignedTinyInteger('featured_packages_limit')->default(3);

            // =========================================================================
            // 4. Custom Experience CTA Section
            // =========================================================================
            $table->boolean('custom_experience_enabled')->default(true);
            $table->json('custom_experience_title')->nullable(); // translatable
            $table->json('custom_experience_description')->nullable(); // translatable
            $table->json('custom_experience_button_text')->nullable(); // translatable
            $table->string('custom_experience_link')->nullable();

            // =========================================================================
            // 5. Newsletter Section
            // =========================================================================
            $table->boolean('newsletter_enabled')->default(true);
            $table->json('newsletter_title')->nullable(); // translatable
            $table->json('newsletter_subtitle')->nullable(); // translatable
            $table->json('newsletter_button_text')->nullable(); // translatable

            // =========================================================================
            // 6. About Page
            // =========================================================================
            // Hero
            $table->json('about_hero_title')->nullable(); // translatable
            $table->json('about_hero_subtitle')->nullable(); // translatable
            $table->json('about_hero_tagline')->nullable(); // translatable

            // Founder
            $table->string('about_founder_name')->nullable();
            $table->json('about_founder_story')->nullable(); // translatable
            $table->json('about_founder_quote')->nullable(); // translatable

            // Story section ("L'Aventurier" heading and text)
            $table->json('about_story_heading')->nullable(); // translatable
            $table->json('about_story_intro')->nullable(); // translatable
            $table->json('about_story_text_1')->nullable(); // translatable
            $table->json('about_story_text_2')->nullable(); // translatable

            // Team section
            $table->json('about_team_title')->nullable(); // translatable
            $table->json('about_team_description')->nullable(); // translatable

            // Impact section
            $table->json('about_impact_text')->nullable(); // translatable

            // Complex JSON arrays
            $table->json('about_commitments')->nullable(); // array of {icon, title_en, title_fr, description_en, description_fr}
            $table->json('about_partners')->nullable(); // array of {name, logo}
            $table->json('about_initiatives')->nullable(); // array of {image, alt_en, alt_fr}
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Experience Categories
            $table->dropColumn([
                'experience_categories_enabled',
                'experience_categories_title',
                'experience_categories_subtitle',
            ]);

            // Blog Section
            $table->dropColumn([
                'blog_section_enabled',
                'blog_section_title',
                'blog_section_subtitle',
                'blog_section_post_limit',
            ]);

            // Featured Packages
            $table->dropColumn([
                'featured_packages_enabled',
                'featured_packages_title',
                'featured_packages_subtitle',
                'featured_packages_limit',
            ]);

            // Custom Experience
            $table->dropColumn([
                'custom_experience_enabled',
                'custom_experience_title',
                'custom_experience_description',
                'custom_experience_button_text',
                'custom_experience_link',
            ]);

            // Newsletter
            $table->dropColumn([
                'newsletter_enabled',
                'newsletter_title',
                'newsletter_subtitle',
                'newsletter_button_text',
            ]);

            // About Page
            $table->dropColumn([
                'about_hero_title',
                'about_hero_subtitle',
                'about_hero_tagline',
                'about_founder_name',
                'about_founder_story',
                'about_founder_quote',
                'about_story_heading',
                'about_story_intro',
                'about_story_text_1',
                'about_story_text_2',
                'about_team_title',
                'about_team_description',
                'about_impact_text',
                'about_commitments',
                'about_partners',
                'about_initiatives',
            ]);
        });
    }
};
