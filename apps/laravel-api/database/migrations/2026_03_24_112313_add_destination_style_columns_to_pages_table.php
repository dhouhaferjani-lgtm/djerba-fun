<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add destination-style columns to the pages table.
     * These mirror the structure used in Platform Settings > Destinations.
     * Existing columns are NOT dropped for backward compatibility.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // General - field-by-field translations
            $table->text('description_en')->nullable()->after('intro');
            $table->text('description_fr')->nullable()->after('description_en');
            $table->string('link', 500)->nullable()->after('description_fr');

            // SEO - field-by-field instead of JSON (mirrors destinations)
            $table->string('seo_title_en', 120)->nullable()->after('link');
            $table->string('seo_title_fr', 120)->nullable()->after('seo_title_en');
            $table->text('seo_description_en')->nullable()->after('seo_title_fr');
            $table->text('seo_description_fr')->nullable()->after('seo_description_en');
            $table->text('seo_text_en')->nullable()->after('seo_description_fr');
            $table->text('seo_text_fr')->nullable()->after('seo_text_en');

            // Content Sections (JSON arrays - same as destinations)
            $table->json('highlights')->nullable()->after('seo_text_fr');
            $table->json('key_facts')->nullable()->after('highlights');
            $table->json('gallery')->nullable()->after('key_facts');
            $table->json('points_of_interest')->nullable()->after('gallery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                // General
                'description_en',
                'description_fr',
                'link',
                // SEO
                'seo_title_en',
                'seo_title_fr',
                'seo_description_en',
                'seo_description_fr',
                'seo_text_en',
                'seo_text_fr',
                // Content Sections
                'highlights',
                'key_facts',
                'gallery',
                'points_of_interest',
            ]);
        });
    }
};
