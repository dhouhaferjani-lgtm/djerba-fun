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
        Schema::table('blog_posts', function (Blueprint $table) {
            // Header style determines how the header is displayed: image, gallery, or none
            $table->string('header_style')->default('image')->after('featured_image');

            // Gallery images for posts with header_style='gallery' (up to 12 images)
            $table->json('gallery_images')->nullable()->after('header_style');

            // Key takeaways section - translatable array of {text, icon} objects
            $table->json('key_takeaways')->nullable()->after('excerpt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['header_style', 'gallery_images', 'key_takeaways']);
        });
    }
};
