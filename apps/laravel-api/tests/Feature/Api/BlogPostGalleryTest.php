<?php

namespace Tests\Feature\Api;

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
    public function api_returns_header_style_field(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'header_style' => 'gallery',
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.headerStyle', 'gallery');
    }

    /** @test */
    public function api_returns_gallery_images_as_urls(): void
    {
        Storage::fake('public');

        $images = ['blog-galleries/image1.jpg', 'blog-galleries/image2.jpg'];

        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'header_style' => 'gallery',
            'gallery_images' => $images,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.galleryImages');

        $galleryImages = $response->json('data.galleryImages');

        // Verify URLs contain storage path (may be relative in test environment)
        foreach ($galleryImages as $url) {
            $this->assertStringContainsString('storage', $url);
        }
    }

    /** @test */
    public function api_returns_key_takeaways(): void
    {
        $takeaways = [
            ['text' => 'First takeaway', 'icon' => 'check'],
            ['text' => 'Second takeaway', 'icon' => 'star'],
        ];

        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'key_takeaways' => $takeaways,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.keyTakeaways')
            ->assertJsonPath('data.keyTakeaways.0.text', 'First takeaway')
            ->assertJsonPath('data.keyTakeaways.0.icon', 'check')
            ->assertJsonPath('data.keyTakeaways.1.text', 'Second takeaway')
            ->assertJsonPath('data.keyTakeaways.1.icon', 'star');
    }

    /** @test */
    public function existing_posts_return_image_as_default_header_style(): void
    {
        // Create post without explicitly setting header_style
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.headerStyle', 'image');
    }

    /** @test */
    public function gallery_images_are_full_urls_not_paths(): void
    {
        Storage::fake('public');

        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'header_style' => 'gallery',
            'gallery_images' => ['blog-galleries/test.jpg'],
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $galleryImages = $response->json('data.galleryImages');

        // Verify URL is transformed (not just the raw path)
        $this->assertNotEquals('blog-galleries/test.jpg', $galleryImages[0]);
        // URL should contain 'storage' (relative or absolute URL)
        $this->assertStringContainsString('storage', $galleryImages[0]);
    }

    /** @test */
    public function key_takeaways_respect_accept_language_header(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        // Set translations
        $post->setTranslation('key_takeaways', 'en', [
            ['text' => 'English point', 'icon' => 'check'],
        ]);
        $post->setTranslation('key_takeaways', 'fr', [
            ['text' => 'Point en français', 'icon' => 'check'],
        ]);
        $post->save();

        // Request in English
        $responseEn = $this->getJson("/api/v1/blog/posts/{$post->slug}", [
            'Accept-Language' => 'en',
        ]);

        $responseEn->assertOk()
            ->assertJsonPath('data.keyTakeaways.0.text', 'English point');

        // Request in French
        $responseFr = $this->getJson("/api/v1/blog/posts/{$post->slug}", [
            'Accept-Language' => 'fr',
        ]);

        $responseFr->assertOk()
            ->assertJsonPath('data.keyTakeaways.0.text', 'Point en français');
    }

    /** @test */
    public function api_returns_empty_gallery_images_when_none(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'header_style' => 'image',
            'gallery_images' => null,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.galleryImages', []);
    }

    /** @test */
    public function api_returns_empty_key_takeaways_when_none(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'key_takeaways' => null,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.keyTakeaways', []);
    }

    /** @test */
    public function blog_list_includes_new_fields(): void
    {
        BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'header_style' => 'gallery',
            'gallery_images' => ['img1.jpg', 'img2.jpg'],
            'key_takeaways' => [['text' => 'Test', 'icon' => 'check']],
        ]);

        $response = $this->getJson('/api/v1/blog/posts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'headerStyle',
                        'galleryImages',
                        'keyTakeaways',
                    ],
                ],
            ]);
    }

    /** @test */
    public function featured_posts_include_new_fields(): void
    {
        BlogPost::factory()->published()->featured()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'header_style' => 'gallery',
            'key_takeaways' => [['text' => 'Featured point', 'icon' => 'star']],
        ]);

        $response = $this->getJson('/api/v1/blog/posts/featured');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'headerStyle',
                        'galleryImages',
                        'keyTakeaways',
                    ],
                ],
            ]);
    }
}
