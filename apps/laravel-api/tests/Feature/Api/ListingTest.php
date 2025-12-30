<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AvailabilitySlot;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can view all listings.
     */
    public function test_user_can_view_all_listings(): void
    {
        // Arrange
        Listing::factory()->count(5)->create(['is_published' => true]);

        // Act
        $response = $this->getJson('/api/v1/listings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'listings' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'price',
                        'location',
                    ],
                ],
                'meta',
            ])
            ->assertJsonCount(5, 'listings');
    }

    /**
     * Test unpublished listings are not shown.
     */
    public function test_unpublished_listings_are_not_shown(): void
    {
        // Arrange
        Listing::factory()->count(3)->create(['is_published' => true]);
        Listing::factory()->count(2)->create(['is_published' => false]);

        // Act
        $response = $this->getJson('/api/v1/listings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'listings');
    }

    /**
     * Test user can view single listing details.
     */
    public function test_user_can_view_listing_details(): void
    {
        // Arrange
        $listing = Listing::factory()->create(['is_published' => true]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'listing' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'price',
                    'location',
                    'vendor',
                ],
            ]);
    }

    /**
     * Test user can search listings by title.
     */
    public function test_user_can_search_listings_by_title(): void
    {
        // Arrange
        Listing::factory()->create([
            'title' => 'Amazing Hiking Tour',
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'title' => 'Beach Adventure',
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'title' => 'Mountain Climbing',
            'is_published' => true,
        ]);

        // Act
        $response = $this->getJson('/api/v1/listings?search=hiking');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'listings')
            ->assertJsonFragment([
                'title' => 'Amazing Hiking Tour',
            ]);
    }

    /**
     * Test user can filter listings by location.
     */
    public function test_user_can_filter_listings_by_location(): void
    {
        // Arrange
        $location1 = Location::factory()->create(['city' => 'Toronto']);
        $location2 = Location::factory()->create(['city' => 'Vancouver']);

        Listing::factory()->count(2)->create([
            'location_id' => $location1->id,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'location_id' => $location2->id,
            'is_published' => true,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings?location_id={$location1->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'listings');
    }

    /**
     * Test user can filter listings by price range.
     */
    public function test_user_can_filter_listings_by_price_range(): void
    {
        // Arrange
        Listing::factory()->create([
            'base_price' => 50.00,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'base_price' => 150.00,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'base_price' => 250.00,
            'is_published' => true,
        ]);

        // Act
        $response = $this->getJson('/api/v1/listings?min_price=100&max_price=200');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'listings');
    }

    /**
     * Test user can view listing availability.
     */
    public function test_user_can_view_listing_availability(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        AvailabilitySlot::factory()->count(5)->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(1),
            'capacity' => 10,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/availability");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'slots' => [
                    '*' => [
                        'id',
                        'start_datetime',
                        'capacity',
                        'available_capacity',
                    ],
                ],
            ])
            ->assertJsonCount(5, 'slots');
    }

    /**
     * Test user can view availability for specific date range.
     */
    public function test_user_can_view_availability_for_date_range(): void
    {
        // Arrange
        $listing = Listing::factory()->create();

        // Slots within range
        AvailabilitySlot::factory()->count(3)->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(5),
        ]);

        // Slots outside range
        AvailabilitySlot::factory()->count(2)->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(20),
        ]);

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(10)->format('Y-m-d');

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/availability?start_date={$startDate}&end_date={$endDate}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'slots');
    }

    /**
     * Test fully booked slots show zero available capacity.
     */
    public function test_fully_booked_slots_show_zero_capacity(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'booked_count' => 10,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/availability");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'available_capacity' => 0,
            ]);
    }

    /**
     * Test user can sort listings by price.
     */
    public function test_user_can_sort_listings_by_price(): void
    {
        // Arrange
        Listing::factory()->create([
            'base_price' => 250.00,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'base_price' => 50.00,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'base_price' => 150.00,
            'is_published' => true,
        ]);

        // Act
        $response = $this->getJson('/api/v1/listings?sort=price_asc');

        // Assert
        $response->assertStatus(200);
        $listings = $response->json('listings');

        $this->assertEquals(50.00, $listings[0]['base_price']);
        $this->assertEquals(150.00, $listings[1]['base_price']);
        $this->assertEquals(250.00, $listings[2]['base_price']);
    }

    /**
     * Test user can sort listings by rating.
     */
    public function test_user_can_sort_listings_by_rating(): void
    {
        // Arrange
        Listing::factory()->create([
            'average_rating' => 4.5,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'average_rating' => 3.0,
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'average_rating' => 5.0,
            'is_published' => true,
        ]);

        // Act
        $response = $this->getJson('/api/v1/listings?sort=rating_desc');

        // Assert
        $response->assertStatus(200);
        $listings = $response->json('listings');

        $this->assertEquals(5.0, $listings[0]['average_rating']);
        $this->assertEquals(4.5, $listings[1]['average_rating']);
        $this->assertEquals(3.0, $listings[2]['average_rating']);
    }

    /**
     * Test listings endpoint supports pagination.
     */
    public function test_listings_endpoint_supports_pagination(): void
    {
        // Arrange
        Listing::factory()->count(25)->create(['is_published' => true]);

        // Act
        $response = $this->getJson('/api/v1/listings?per_page=10');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(10, 'listings')
            ->assertJsonStructure([
                'listings',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }
}
