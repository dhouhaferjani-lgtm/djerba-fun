<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the new hero_images column
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('hero_images')->nullable()->after('featured_image');
        });

        // Migrate existing featured_image data to hero_images array
        DB::table('blog_posts')
            ->whereNotNull('featured_image')
            ->where('featured_image', '!=', '')
            ->orderBy('id')
            ->each(function ($post) {
                $featuredImage = $post->featured_image;

                // Handle case where featured_image might already be JSON array
                $decoded = json_decode($featuredImage, true);
                if (is_array($decoded)) {
                    $images = array_filter($decoded);
                } else {
                    $images = [$featuredImage];
                }

                DB::table('blog_posts')
                    ->where('id', $post->id)
                    ->update(['hero_images' => json_encode(array_values($images))]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('hero_images');
        });
    }
};
