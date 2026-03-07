<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can add listing to wishlist.
     */
    public function test_authenticated_user_can_add_listing_to_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/wishlists/{$listing->id}");

        // Assert
        $response->assertStatus(201)
            ->assertJsonFragment(['listing_id' => $listing->id]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);
    }

    /**
     * Test authenticated user can view their wishlist.
     */
    public function test_authenticated_user_can_view_their_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listings = Listing::factory()->published()->count(3)->create();

        foreach ($listings as $listing) {
            Wishlist::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
            ]);
        }

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/wishlists');

        // Assert
        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test authenticated user can remove listing from wishlist.
     */
    public function test_authenticated_user_can_remove_listing_from_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/wishlists/{$listing->id}");

        // Assert
        $response->assertOk();

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);
    }

    /**
     * Test unauthenticated user cannot access wishlist.
     */
    public function test_unauthenticated_user_cannot_access_wishlist(): void
    {
        // Arrange
        $listing = Listing::factory()->published()->create();

        // Act (no actingAs)
        $response = $this->postJson("/api/v1/wishlists/{$listing->id}");

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Test cannot add unpublished listing to wishlist.
     */
    public function test_cannot_add_unpublished_listing_to_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->draft()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/wishlists/{$listing->id}");

        // Assert
        $response->assertNotFound();
    }

    /**
     * Test toggle adds to wishlist if not present.
     */
    public function test_toggle_adds_to_wishlist_if_not_present(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/wishlists/{$listing->id}/toggle");

        // Assert
        $response->assertOk()
            ->assertJsonFragment(['in_wishlist' => true]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);
    }

    /**
     * Test toggle removes from wishlist if present.
     */
    public function test_toggle_removes_from_wishlist_if_present(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/wishlists/{$listing->id}/toggle");

        // Assert
        $response->assertOk()
            ->assertJsonFragment(['in_wishlist' => false]);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);
    }

    /**
     * Test can check if listing is in wishlist.
     */
    public function test_can_check_if_listing_is_in_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/v1/wishlists/{$listing->id}/check");

        // Assert
        $response->assertOk()
            ->assertJsonFragment(['in_wishlist' => true]);
    }

    /**
     * Test can get wishlist IDs for efficient client-side checking.
     */
    public function test_can_get_wishlist_ids(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listings = Listing::factory()->published()->count(3)->create();

        foreach ($listings as $listing) {
            Wishlist::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
            ]);
        }

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/wishlists/ids');

        // Assert
        $response->assertOk()
            ->assertJsonCount(3, 'data.listing_ids');
    }

    /**
     * Test adding duplicate listing returns 200 (idempotent).
     */
    public function test_adding_duplicate_listing_is_idempotent(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/wishlists/{$listing->id}");

        // Assert - should return 200 (already exists)
        $response->assertOk()
            ->assertJsonFragment(['in_wishlist' => true]);

        // Should still only have one entry
        $this->assertEquals(1, Wishlist::where('user_id', $user->id)->count());
    }

    /**
     * Test removing non-existent listing returns 404.
     */
    public function test_removing_nonexistent_listing_returns_404(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        // Act
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/wishlists/{$listing->id}");

        // Assert
        $response->assertNotFound();
    }

    /**
     * Test wishlist includes listing details.
     */
    public function test_wishlist_includes_listing_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->published()->create();

        Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/wishlists');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'listing_id',
                        'added_at',
                        'listing' => [
                            'id',
                            'slug',
                            'title',
                        ],
                    ],
                ],
            ]);
    }
}
