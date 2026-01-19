<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test blog post belongs to author.
     */
    public function test_blog_post_belongs_to_author(): void
    {
        // Arrange
        $author = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $post = BlogPost::factory()->create(['author_id' => $author->id]);

        // Act
        $postAuthor = $post->author;

        // Assert
        $this->assertInstanceOf(User::class, $postAuthor);
        $this->assertEquals($author->id, $postAuthor->id);
    }

    /**
     * Test blog post belongs to category.
     */
    public function test_blog_post_belongs_to_category(): void
    {
        // Arrange
        $category = BlogCategory::factory()->create();
        $post = BlogPost::factory()->create(['blog_category_id' => $category->id]);

        // Act
        $postCategory = $post->category;

        // Assert
        $this->assertInstanceOf(BlogCategory::class, $postCategory);
        $this->assertEquals($category->id, $postCategory->id);
    }

    /**
     * Test slug auto-generation from title.
     */
    public function test_slug_auto_generated_from_title(): void
    {
        // Arrange
        $author = User::factory()->create(['role' => UserRole::ADMIN->value]);

        // Act
        $post = BlogPost::create([
            'author_id' => $author->id,
            'title' => 'My Awesome Blog Post Title',
            'content' => 'Some content here',
            'status' => 'draft',
        ]);

        // Assert
        $this->assertEquals('my-awesome-blog-post-title', $post->slug);
    }

    /**
     * Test slug is unique.
     */
    public function test_slug_is_unique(): void
    {
        // Arrange
        $post1 = BlogPost::factory()->create();
        $post2 = BlogPost::factory()->create();

        // Assert
        $this->assertNotEquals($post1->slug, $post2->slug);
        $this->assertNotNull($post1->slug);
        $this->assertNotNull($post2->slug);
    }

    /**
     * Test slug is preserved when provided explicitly.
     */
    public function test_slug_preserved_when_explicitly_set(): void
    {
        // Arrange
        $author = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $customSlug = 'my-custom-slug-here';

        // Act
        $post = BlogPost::create([
            'author_id' => $author->id,
            'title' => 'Different Title',
            'slug' => $customSlug,
            'content' => 'Some content',
            'status' => 'draft',
        ]);

        // Assert
        $this->assertEquals($customSlug, $post->slug);
    }

    /**
     * Test published scope requires both status and published_at.
     */
    public function test_published_scope_requires_status_and_published_at(): void
    {
        // Arrange - Published post (should be included)
        BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Status published but no published_at (should NOT be included)
        BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => null,
        ]);

        // Status draft with published_at (should NOT be included)
        BlogPost::factory()->create([
            'status' => 'draft',
            'published_at' => now()->subDay(),
        ]);

        // Status published but future date (should NOT be included)
        BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        // Act
        $publishedPosts = BlogPost::published()->get();

        // Assert - Only the first post should be included
        $this->assertCount(1, $publishedPosts);
    }

    /**
     * Test isPublished method returns correct value.
     */
    public function test_is_published_method(): void
    {
        // Arrange & Act - Published post
        $publishedPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Not published - missing published_at
        $missingDatePost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => null,
        ]);

        // Not published - draft status
        $draftPost = BlogPost::factory()->create([
            'status' => 'draft',
            'published_at' => now()->subDay(),
        ]);

        // Not published - future date
        $futurePost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        // Assert
        $this->assertTrue($publishedPost->isPublished());
        $this->assertFalse($missingDatePost->isPublished());
        $this->assertFalse($draftPost->isPublished());
        $this->assertFalse($futurePost->isPublished());
    }

    /**
     * Test featured scope.
     */
    public function test_featured_scope(): void
    {
        // Arrange
        BlogPost::factory()->count(2)->create(['is_featured' => true]);
        BlogPost::factory()->count(3)->create(['is_featured' => false]);

        // Act
        $featuredPosts = BlogPost::featured()->get();

        // Assert
        $this->assertCount(2, $featuredPosts);
    }

    /**
     * Test read time auto-calculation.
     */
    public function test_read_time_auto_calculated(): void
    {
        // Arrange - Create content with ~400 words (should be 2 minutes)
        $author = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $content = str_repeat('word ', 400);

        // Act
        $post = BlogPost::create([
            'author_id' => $author->id,
            'title' => 'Test Post',
            'content' => $content,
            'status' => 'draft',
        ]);

        // Assert - 400 words / 200 wpm = 2 minutes
        $this->assertEquals(2, $post->read_time_minutes);
    }

    /**
     * Test excerpt auto-generation.
     */
    public function test_excerpt_auto_generated_from_content(): void
    {
        // Arrange
        $author = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $content = str_repeat('This is test content. ', 50);

        // Act
        $post = BlogPost::create([
            'author_id' => $author->id,
            'title' => 'Test Post',
            'content' => $content,
            'status' => 'draft',
        ]);

        // Assert
        $this->assertNotNull($post->excerpt);
        $this->assertLessThanOrEqual(203, strlen($post->excerpt)); // 200 + '...'
    }

    /**
     * Test views increment.
     */
    public function test_views_increment(): void
    {
        // Arrange
        $post = BlogPost::factory()->create(['views_count' => 10]);

        // Act
        $post->incrementViews();

        // Assert
        $this->assertEquals(11, $post->fresh()->views_count);
    }

    /**
     * Test route key is slug.
     */
    public function test_route_key_is_slug(): void
    {
        // Arrange
        $post = BlogPost::factory()->create();

        // Assert
        $this->assertEquals('slug', $post->getRouteKeyName());
    }

    /**
     * Test byCategory scope.
     */
    public function test_by_category_scope(): void
    {
        // Arrange
        $category1 = BlogCategory::factory()->create(['slug' => 'travel']);
        $category2 = BlogCategory::factory()->create(['slug' => 'food']);

        BlogPost::factory()->count(2)->create(['blog_category_id' => $category1->id]);
        BlogPost::factory()->count(3)->create(['blog_category_id' => $category2->id]);

        // Act
        $travelPosts = BlogPost::byCategory('travel')->get();
        $foodPosts = BlogPost::byCategory('food')->get();

        // Assert
        $this->assertCount(2, $travelPosts);
        $this->assertCount(3, $foodPosts);
    }

    /**
     * Test tags are cast to array.
     */
    public function test_tags_cast_to_array(): void
    {
        // Arrange
        $tags = ['travel', 'adventure', 'tunisia'];
        $post = BlogPost::factory()->create(['tags' => $tags]);

        // Assert
        $this->assertIsArray($post->tags);
        $this->assertEquals($tags, $post->tags);
    }

    /**
     * Test soft deletes.
     */
    public function test_soft_deletes(): void
    {
        // Arrange
        $post = BlogPost::factory()->create();
        $postId = $post->id;

        // Act
        $post->delete();

        // Assert
        $this->assertNull(BlogPost::find($postId));
        $this->assertNotNull(BlogPost::withTrashed()->find($postId));
    }
}
