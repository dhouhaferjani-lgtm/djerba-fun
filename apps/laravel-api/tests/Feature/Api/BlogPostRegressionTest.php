<?php

namespace Tests\Feature\Api;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests to ensure existing blog functionality continues to work
 * after adding gallery and key takeaways features.
 */
class BlogPostRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $author;
    protected BlogCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['role' => 'admin']);
        $this->category = BlogCategory::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    /** @test */
    public function can_list_published_blog_posts(): void
    {
        // Create published posts
        BlogPost::factory()->published()->count(3)->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        // Create draft post (should not appear)
        BlogPost::factory()->draft()->create([
            'author_id' => $this->author->id,
        ]);

        $response = $this->getJson('/api/v1/blog/posts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_get_blog_post_by_slug(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'title' => 'Test Post Title',
            'slug' => 'test-post-title',
            'content' => 'This is the content of the test post.',
        ]);

        $response = $this->getJson('/api/v1/blog/posts/test-post-title');

        $response->assertOk()
            ->assertJsonPath('data.title', 'Test Post Title')
            ->assertJsonPath('data.slug', 'test-post-title')
            ->assertJsonPath('data.content', 'This is the content of the test post.');
    }

    /** @test */
    public function can_filter_posts_by_category(): void
    {
        $anotherCategory = BlogCategory::factory()->create([
            'name' => 'Another Category',
            'slug' => 'another-category',
        ]);

        // Create posts in our test category
        BlogPost::factory()->published()->count(2)->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        // Create post in another category
        BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $anotherCategory->id,
        ]);

        $response = $this->getJson('/api/v1/blog/posts?category=test-category');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function featured_posts_endpoint_still_works(): void
    {
        // Create featured posts
        BlogPost::factory()->published()->featured()->count(2)->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        // Create non-featured post
        BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'is_featured' => false,
        ]);

        $response = $this->getJson('/api/v1/blog/posts/featured');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function related_posts_endpoint_still_works(): void
    {
        // Create main post
        $mainPost = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'tags' => ['travel', 'adventure'],
        ]);

        // Create related posts (same category)
        BlogPost::factory()->published()->count(2)->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$mainPost->slug}/related");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function view_count_still_increments(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'views_count' => 0,
        ]);

        // First view
        $this->getJson("/api/v1/blog/posts/{$post->slug}");
        $post->refresh();
        $this->assertEquals(1, $post->views_count);

        // Second view
        $this->getJson("/api/v1/blog/posts/{$post->slug}");
        $post->refresh();
        $this->assertEquals(2, $post->views_count);
    }

    /** @test */
    public function search_still_works(): void
    {
        BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'title' => 'Adventures in Tunisia',
            'content' => 'Exploring the beautiful landscapes.',
        ]);

        BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'title' => 'Food Guide',
            'content' => 'Best restaurants to visit.',
        ]);

        // Skip this test on SQLite as it doesn't support ilike
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support ilike operator');
        }

        $response = $this->getJson('/api/v1/blog/posts?search=Tunisia');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function pagination_still_works(): void
    {
        BlogPost::factory()->published()->count(15)->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/v1/blog/posts?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    /** @test */
    public function api_response_includes_all_existing_fields(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'title' => 'Test Title',
            'slug' => 'test-title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image' => 'blog-images/test.jpg',
            'tags' => ['tag1', 'tag2'],
            'is_featured' => true,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'featuredImage',
                    'tags',
                    'readTimeMinutes',
                    'viewsCount',
                    'isFeatured',
                    'status',
                    'publishedAt',
                    'createdAt',
                    'updatedAt',
                    'author' => ['id', 'name'],
                    'category' => ['id', 'name', 'slug', 'color'],
                    'seo' => ['title', 'description'],
                    // New fields (should also be present)
                    'headerStyle',
                    'galleryImages',
                    'keyTakeaways',
                ],
            ]);
    }

    /** @test */
    public function draft_posts_not_visible_in_api(): void
    {
        $post = BlogPost::factory()->draft()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertNotFound();
    }

    /** @test */
    public function scheduled_posts_not_visible_before_publish_date(): void
    {
        $post = BlogPost::factory()->scheduled()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'published_at' => now()->addDays(7),
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertNotFound();
    }

    /** @test */
    public function author_information_still_returned(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.author.id', $this->author->id);
    }

    /** @test */
    public function category_information_still_returned(): void
    {
        $post = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/blog/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.category.id', $this->category->id)
            ->assertJsonPath('data.category.slug', 'test-category');
    }

    /** @test */
    public function posts_ordered_by_published_date_descending(): void
    {
        $oldPost = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'published_at' => now()->subDays(10),
        ]);

        $newPost = BlogPost::factory()->published()->create([
            'author_id' => $this->author->id,
            'blog_category_id' => $this->category->id,
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/blog/posts');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($newPost->id, $data[0]['id']);
        $this->assertEquals($oldPost->id, $data[1]['id']);
    }
}
