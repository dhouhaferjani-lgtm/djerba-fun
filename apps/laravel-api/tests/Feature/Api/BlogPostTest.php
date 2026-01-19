<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can list published blog posts.
     */
    public function test_can_list_published_blog_posts(): void
    {
        // Arrange - Create published posts
        BlogPost::factory()->count(3)->published()->create();

        // Create draft post (should not appear)
        BlogPost::factory()->draft()->create();

        // Act
        $response = $this->getJson('/api/v1/blog/posts');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'featuredImage',
                        'publishedAt',
                        'author' => ['id', 'name'],
                    ],
                ],
            ]);
    }

    /**
     * Test published post without published_at does NOT appear in list.
     * This is the critical regression test for Issue #1.
     */
    public function test_published_status_without_published_at_does_not_appear(): void
    {
        // Arrange - Create post with status=published but NO published_at
        // This is the bug scenario that Issue #1 fixes
        BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => null,
        ]);

        // Create properly published post
        BlogPost::factory()->published()->create();

        // Act
        $response = $this->getJson('/api/v1/blog/posts');

        // Assert - Only 1 post should appear (the properly published one)
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test can get featured blog posts.
     */
    public function test_can_get_featured_blog_posts(): void
    {
        // Arrange
        BlogPost::factory()->count(2)->published()->featured()->create();
        BlogPost::factory()->count(3)->published()->create(['is_featured' => false]);

        // Act
        $response = $this->getJson('/api/v1/blog/posts/featured?limit=3');

        // Assert - Should only return featured posts
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $post) {
            $this->assertTrue($post['isFeatured']);
        }
    }

    /**
     * Test featured posts must be published.
     */
    public function test_featured_posts_must_be_published(): void
    {
        // Arrange - Create featured but draft posts
        BlogPost::factory()->count(2)->draft()->featured()->create();

        // Create featured and published post
        BlogPost::factory()->published()->featured()->create();

        // Act
        $response = $this->getJson('/api/v1/blog/posts/featured');

        // Assert - Only the published one should appear
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test can get single blog post by slug.
     */
    public function test_can_get_blog_post_by_slug(): void
    {
        // Arrange
        $post = BlogPost::factory()->published()->create([
            'slug' => 'my-test-post',
            'title' => 'My Test Post',
        ]);

        // Act
        $response = $this->getJson('/api/v1/blog/posts/my-test-post');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'my-test-post')
            ->assertJsonPath('data.title', 'My Test Post');
    }

    /**
     * Test can filter posts by category.
     */
    public function test_can_filter_posts_by_category(): void
    {
        // Arrange
        $category = BlogCategory::factory()->create(['slug' => 'adventure']);
        BlogPost::factory()->count(2)->published()->inCategory($category)->create();
        BlogPost::factory()->count(3)->published()->create();

        // Act
        $response = $this->getJson('/api/v1/blog/posts?category=adventure');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test can search posts.
     *
     * NOTE: This test is skipped because the BlogPostController uses PostgreSQL-specific
     * 'ilike' operator which doesn't work with SQLite (used in tests). The search
     * functionality works correctly in production with PostgreSQL.
     */
    public function test_can_search_posts(): void
    {
        // Skip this test when using SQLite (tests use SQLite, production uses PostgreSQL)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Search uses PostgreSQL ilike operator, not compatible with SQLite');
        }

        // Arrange
        BlogPost::factory()->published()->create(['title' => 'Amazing Tunisia Adventure']);
        BlogPost::factory()->published()->create(['title' => 'Food Guide']);

        // Act
        $response = $this->getJson('/api/v1/blog/posts?search=Tunisia');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Amazing Tunisia Adventure');
    }

    /**
     * Test posts are returned in correct locale.
     */
    public function test_posts_returned_in_correct_locale(): void
    {
        // Arrange
        $post = BlogPost::factory()->published()->create([
            'title' => ['en' => 'English Title', 'fr' => 'Titre Français'],
        ]);

        // Act - Request in French
        $response = $this->getJson('/api/v1/blog/posts', [
            'Accept-Language' => 'fr',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Titre Français');
    }

    /**
     * Test view count increments on single post view.
     */
    public function test_view_count_increments_on_post_view(): void
    {
        // Arrange
        $post = BlogPost::factory()->published()->create([
            'slug' => 'test-post',
            'views_count' => 10,
        ]);

        // Act
        $this->getJson('/api/v1/blog/posts/test-post');

        // Assert
        $this->assertEquals(11, $post->fresh()->views_count);
    }
}
