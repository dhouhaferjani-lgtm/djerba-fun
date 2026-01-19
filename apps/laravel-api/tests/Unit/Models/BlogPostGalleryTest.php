<?php

namespace Tests\Unit\Models;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogPostGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected User $author;
    protected BlogCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['role' => 'admin']);
        $this->category = BlogCategory::factory()->create();
    }

    /** @test */
    public function blog_post_has_header_style_attribute(): void
    {
        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'header_style' => 'gallery',
        ]);

        $this->assertEquals('gallery', $post->header_style);
    }

    /** @test */
    public function header_style_defaults_to_image(): void
    {
        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
        ]);

        $this->assertEquals('image', $post->header_style);
    }

    /** @test */
    public function gallery_images_cast_to_array(): void
    {
        $images = ['blog-galleries/image1.jpg', 'blog-galleries/image2.jpg'];

        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'gallery_images' => $images,
        ]);

        $post->refresh();

        $this->assertIsArray($post->gallery_images);
        $this->assertCount(2, $post->gallery_images);
        $this->assertEquals($images, $post->gallery_images);
    }

    /** @test */
    public function key_takeaways_cast_to_array(): void
    {
        $takeaways = [
            ['text' => 'First takeaway', 'icon' => 'check'],
            ['text' => 'Second takeaway', 'icon' => 'star'],
        ];

        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'key_takeaways' => $takeaways,
        ]);

        $post->refresh();

        $this->assertIsArray($post->key_takeaways);
        $this->assertCount(2, $post->key_takeaways);
        $this->assertEquals('First takeaway', $post->key_takeaways[0]['text']);
        $this->assertEquals('check', $post->key_takeaways[0]['icon']);
    }

    /** @test */
    public function key_takeaways_are_translatable(): void
    {
        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
        ]);

        // Set English takeaways
        $post->setTranslation('key_takeaways', 'en', [
            ['text' => 'English takeaway', 'icon' => 'check'],
        ]);

        // Set French takeaways
        $post->setTranslation('key_takeaways', 'fr', [
            ['text' => 'Point clé en français', 'icon' => 'check'],
        ]);

        $post->save();
        $post->refresh();

        // Verify English
        $this->assertEquals(
            'English takeaway',
            $post->getTranslation('key_takeaways', 'en')[0]['text']
        );

        // Verify French
        $this->assertEquals(
            'Point clé en français',
            $post->getTranslation('key_takeaways', 'fr')[0]['text']
        );
    }

    /** @test */
    public function gallery_image_urls_accessor_returns_full_urls(): void
    {
        Storage::fake('public');

        $images = ['blog-galleries/image1.jpg', 'blog-galleries/image2.jpg'];

        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'header_style' => 'gallery',
            'gallery_images' => $images,
        ]);

        $urls = $post->gallery_image_urls;

        $this->assertIsArray($urls);
        $this->assertCount(2, $urls);

        // Verify URLs contain the storage path and image paths
        // In test environment, Storage::fake returns relative paths (/storage/...)
        // In production, it returns full URLs (http://...)
        foreach ($urls as $url) {
            $this->assertStringContainsString('storage', $url);
            $this->assertStringContainsString('blog-galleries', $url);
        }
    }

    /** @test */
    public function gallery_image_urls_returns_empty_array_when_null(): void
    {
        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'gallery_images' => null,
        ]);

        $this->assertIsArray($post->gallery_image_urls);
        $this->assertEmpty($post->gallery_image_urls);
    }

    /** @test */
    public function gallery_image_urls_returns_empty_array_when_empty(): void
    {
        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'gallery_images' => [],
        ]);

        $this->assertIsArray($post->gallery_image_urls);
        $this->assertEmpty($post->gallery_image_urls);
    }

    /** @test */
    public function header_style_accepts_valid_values(): void
    {
        $validStyles = ['image', 'gallery', 'none'];

        foreach ($validStyles as $style) {
            $post = BlogPost::factory()->create([
                'author_id' => $this->author->id,
                'header_style' => $style,
            ]);

            $this->assertEquals($style, $post->header_style);
        }
    }

    /** @test */
    public function can_create_post_with_all_new_fields(): void
    {
        $post = BlogPost::factory()->create([
            'author_id' => $this->author->id,
            'header_style' => 'gallery',
            'gallery_images' => ['img1.jpg', 'img2.jpg', 'img3.jpg'],
            'key_takeaways' => [
                ['text' => 'Point 1', 'icon' => 'check'],
                ['text' => 'Point 2', 'icon' => 'bulb'],
            ],
        ]);

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'header_style' => 'gallery',
        ]);

        $post->refresh();
        $this->assertCount(3, $post->gallery_images);
        $this->assertCount(2, $post->key_takeaways);
    }
}
