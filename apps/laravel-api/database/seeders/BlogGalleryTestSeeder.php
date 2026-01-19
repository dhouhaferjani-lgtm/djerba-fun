<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder to create test blog posts for verifying gallery and takeaways features.
 * Run with: php artisan db:seed --class=BlogGalleryTestSeeder
 */
class BlogGalleryTestSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create an admin author
        $author = User::where('role', 'admin')->first();
        if (! $author) {
            $author = User::factory()->create(['role' => 'admin', 'email' => 'test-admin@example.com']);
        }

        // Get or create a category
        $category = BlogCategory::first();
        if (! $category) {
            $category = BlogCategory::factory()->create(['name' => 'Test Category', 'slug' => 'test-category']);
        }

        // 1. Test post with SINGLE IMAGE header (default behavior - regression test)
        BlogPost::factory()->published()->create([
            'author_id' => $author->id,
            'blog_category_id' => $category->id,
            'title' => '[TEST] Single Image Header Post',
            'slug' => 'test-single-image-header',
            'header_style' => 'image',
            'featured_image' => 'blog/test-featured.jpg', // You'll need to upload this
            'key_takeaways' => null,
            'gallery_images' => null,
        ]);

        // 2. Test post with GALLERY header
        BlogPost::factory()->published()->create([
            'author_id' => $author->id,
            'blog_category_id' => $category->id,
            'title' => '[TEST] Gallery Header Post',
            'slug' => 'test-gallery-header',
            'header_style' => 'gallery',
            'featured_image' => null,
            'gallery_images' => [
                'blog-galleries/test1.jpg',
                'blog-galleries/test2.jpg',
                'blog-galleries/test3.jpg',
                'blog-galleries/test4.jpg',
            ],
            'key_takeaways' => [
                ['text' => 'First key point about this adventure', 'icon' => 'check'],
                ['text' => 'Important tip for travelers', 'icon' => 'bulb'],
                ['text' => 'Best time to visit', 'icon' => 'clock'],
            ],
        ]);

        // 3. Test post with NO HEADER
        BlogPost::factory()->published()->create([
            'author_id' => $author->id,
            'blog_category_id' => $category->id,
            'title' => '[TEST] No Header Image Post',
            'slug' => 'test-no-header',
            'header_style' => 'none',
            'featured_image' => null,
            'gallery_images' => null,
            'key_takeaways' => [
                ['text' => 'Even without images, takeaways work', 'icon' => 'star'],
            ],
        ]);

        // 4. Test post with TRANSLATIONS (French takeaways)
        $translatedPost = BlogPost::factory()->published()->create([
            'author_id' => $author->id,
            'blog_category_id' => $category->id,
            'title' => '[TEST] Translated Takeaways Post',
            'slug' => 'test-translated-takeaways',
            'header_style' => 'image',
        ]);

        // Set English takeaways
        $translatedPost->setTranslation('key_takeaways', 'en', [
            ['text' => 'This is the English takeaway', 'icon' => 'check'],
            ['text' => 'Another English point', 'icon' => 'heart'],
        ]);

        // Set French takeaways
        $translatedPost->setTranslation('key_takeaways', 'fr', [
            ['text' => 'Ceci est le point clé en français', 'icon' => 'check'],
            ['text' => 'Un autre point en français', 'icon' => 'heart'],
        ]);

        $translatedPost->save();

        // 5. Test post with MAX takeaways (8)
        BlogPost::factory()->published()->create([
            'author_id' => $author->id,
            'blog_category_id' => $category->id,
            'title' => '[TEST] Max Takeaways Post',
            'slug' => 'test-max-takeaways',
            'header_style' => 'image',
            'key_takeaways' => [
                ['text' => 'Point 1 - Check icon', 'icon' => 'check'],
                ['text' => 'Point 2 - Star icon', 'icon' => 'star'],
                ['text' => 'Point 3 - Arrow icon', 'icon' => 'arrow'],
                ['text' => 'Point 4 - Bulb icon', 'icon' => 'bulb'],
                ['text' => 'Point 5 - Heart icon', 'icon' => 'heart'],
                ['text' => 'Point 6 - Map icon', 'icon' => 'map'],
                ['text' => 'Point 7 - Clock icon', 'icon' => 'clock'],
                ['text' => 'Point 8 - Money icon', 'icon' => 'money'],
            ],
        ]);

        $this->command->info('Created 5 test blog posts for gallery/takeaways verification:');
        $this->command->info('  - test-single-image-header (regression test)');
        $this->command->info('  - test-gallery-header (new feature)');
        $this->command->info('  - test-no-header (new feature)');
        $this->command->info('  - test-translated-takeaways (i18n test)');
        $this->command->info('  - test-max-takeaways (all icons)');
    }
}
