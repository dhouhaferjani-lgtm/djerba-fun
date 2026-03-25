<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Testimonials Section Stats (CMS editable)
            $table->json('testimonials_title')->nullable()->after('testimonials');
            $table->json('testimonials_subtitle')->nullable()->after('testimonials_title');
            $table->string('testimonials_feedback_count')->nullable()->after('testimonials_subtitle');
            $table->json('testimonials_feedback_label')->nullable()->after('testimonials_feedback_count');
            $table->string('testimonials_rating')->nullable()->after('testimonials_feedback_label');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'testimonials_title',
                'testimonials_subtitle',
                'testimonials_feedback_count',
                'testimonials_feedback_label',
                'testimonials_rating',
            ]);
        });
    }
};
