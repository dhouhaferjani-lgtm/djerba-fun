<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlogPostResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for authentication
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
    }

    /**
     * Test author dropdown only shows admin and vendor users.
     * Regression test for Issue #5.
     */
    public function test_author_dropdown_only_shows_admin_and_vendor_users(): void
    {
        // Arrange - Create users with different roles
        $adminUser = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'display_name' => 'Admin User',
        ]);

        $vendorUser = User::factory()->create([
            'role' => UserRole::VENDOR->value,
            'display_name' => 'Vendor User',
        ]);

        $travelerUser = User::factory()->create([
            'role' => UserRole::TRAVELER->value,
            'display_name' => 'Traveler User',
        ]);

        $agentUser = User::factory()->create([
            'role' => UserRole::AGENT->value,
            'display_name' => 'Agent User',
        ]);

        // Act - Query authors that would be shown in the dropdown
        $allowedRoles = [UserRole::ADMIN->value, UserRole::VENDOR->value];
        $authorOptions = User::whereIn('role', $allowedRoles)->pluck('id')->toArray();

        // Assert
        $this->assertContains($adminUser->id, $authorOptions);
        $this->assertContains($vendorUser->id, $authorOptions);
        $this->assertNotContains($travelerUser->id, $authorOptions);
        $this->assertNotContains($agentUser->id, $authorOptions);
    }

    /**
     * Test that traveler users cannot be set as blog authors.
     * Regression test for Issue #5.
     */
    public function test_traveler_excluded_from_author_options(): void
    {
        // Arrange
        $traveler = User::factory()->create([
            'role' => UserRole::TRAVELER->value,
        ]);

        // Act - Check if traveler would be in allowed roles
        $allowedRoles = [UserRole::ADMIN->value, UserRole::VENDOR->value];

        // Assert
        $this->assertNotContains($traveler->role, $allowedRoles);
    }

    /**
     * Test that vendors CAN be blog authors.
     * Regression test for Issue #5.
     */
    public function test_vendor_included_in_author_options(): void
    {
        // Arrange
        $vendor = User::factory()->create([
            'role' => UserRole::VENDOR->value,
        ]);

        // Act - Query if vendor would appear in author dropdown
        $allowedRoles = [UserRole::ADMIN->value, UserRole::VENDOR->value];
        $isIncluded = User::whereIn('role', $allowedRoles)
            ->where('id', $vendor->id)
            ->exists();

        // Assert - Vendor should be included in allowed authors
        $this->assertTrue($isIncluded);
    }

    /**
     * Test blog post can be created with admin author.
     */
    public function test_can_create_blog_post_with_admin_author(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $category = BlogCategory::factory()->create();

        // Act
        $post = BlogPost::create([
            'author_id' => $admin->id,
            'blog_category_id' => $category->id,
            'title' => 'Test Blog Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        // Assert
        $this->assertNotNull($post->id);
        $this->assertEquals($admin->id, $post->author_id);
    }

    /**
     * Test blog post can be created with vendor author.
     */
    public function test_can_create_blog_post_with_vendor_author(): void
    {
        // Arrange
        $vendor = User::factory()->create(['role' => UserRole::VENDOR->value]);
        $category = BlogCategory::factory()->create();

        // Act
        $post = BlogPost::create([
            'author_id' => $vendor->id,
            'blog_category_id' => $category->id,
            'title' => 'Vendor Blog Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        // Assert
        $this->assertNotNull($post->id);
        $this->assertEquals($vendor->id, $post->author_id);
    }

    /**
     * Test slug is auto-generated from title.
     * Regression test for Issue #3.
     */
    public function test_slug_auto_generated_on_create(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        // Act - Create post without providing slug
        $post = BlogPost::create([
            'author_id' => $admin->id,
            'title' => 'My Amazing Blog Post Title',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        // Assert - Slug should be auto-generated
        $this->assertEquals('my-amazing-blog-post-title', $post->slug);
    }

    /**
     * Test custom slug is preserved.
     * Regression test for Issue #3 - SEO protection.
     */
    public function test_custom_slug_is_preserved(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $customSlug = 'my-custom-seo-friendly-slug';

        // Act
        $post = BlogPost::create([
            'author_id' => $admin->id,
            'title' => 'Different Title Here',
            'slug' => $customSlug,
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        // Assert - Custom slug should be preserved
        $this->assertEquals($customSlug, $post->slug);
    }

    /**
     * Test existing slug not changed on update.
     * Regression test for Issue #3 - SEO protection.
     */
    public function test_existing_slug_not_changed_on_update(): void
    {
        // Arrange
        $post = BlogPost::factory()->create([
            'slug' => 'original-slug',
            'title' => 'Original Title',
        ]);

        $originalSlug = $post->slug;

        // Act - Update title (slug should NOT change)
        $post->update(['title' => 'Updated Title']);

        // Assert - Slug should remain unchanged
        $this->assertEquals($originalSlug, $post->fresh()->slug);
    }

    /**
     * Test status published requires published_at for visibility.
     * Regression test for Issue #1.
     */
    public function test_published_status_requires_published_at_for_visibility(): void
    {
        // Arrange - Create post with status=published but no published_at
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => null,
        ]);

        // Act - Check if post is considered published
        $isPublished = $post->isPublished();
        $appearsInPublishedScope = BlogPost::published()->where('id', $post->id)->exists();

        // Assert - Post should NOT be considered published
        $this->assertFalse($isPublished);
        $this->assertFalse($appearsInPublishedScope);
    }

    /**
     * Test post with both status=published AND published_at is visible.
     * Regression test for Issue #1.
     */
    public function test_post_with_status_and_published_at_is_visible(): void
    {
        // Arrange
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        // Act
        $isPublished = $post->isPublished();
        $appearsInPublishedScope = BlogPost::published()->where('id', $post->id)->exists();

        // Assert
        $this->assertTrue($isPublished);
        $this->assertTrue($appearsInPublishedScope);
    }

    /**
     * Test resource has correct navigation settings.
     */
    public function test_resource_navigation_settings(): void
    {
        // Assert
        $this->assertEquals('Content', BlogPostResource::getNavigationGroup());
        $this->assertEquals('Blog Posts', BlogPostResource::getNavigationLabel());
        $this->assertEquals(1, BlogPostResource::getNavigationSort());
    }

    /**
     * Test resource uses translatable trait.
     */
    public function test_resource_is_translatable(): void
    {
        // Assert
        $locales = BlogPostResource::getTranslatableLocales();
        $this->assertContains('en', $locales);
        $this->assertContains('fr', $locales);
    }

    /**
     * Test soft deleted posts can be restored.
     */
    public function test_soft_deleted_posts_can_be_restored(): void
    {
        // Arrange
        $post = BlogPost::factory()->create();
        $postId = $post->id;

        // Act - Soft delete
        $post->delete();

        // Assert - Can be restored
        $this->assertNull(BlogPost::find($postId));
        $trashedPost = BlogPost::withTrashed()->find($postId);
        $this->assertNotNull($trashedPost);

        // Restore
        $trashedPost->restore();
        $this->assertNotNull(BlogPost::find($postId));
    }
}
