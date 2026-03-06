<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for CategoryStatsController.
 *
 * MIGRATION NOTE: Evasion Djerba requires stats for all 4 service types:
 * - tours
 * - nautical
 * - accommodations
 * - events
 */
class CategoryStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('category_stats');
    }

    /**
     * Test category stats endpoint returns all service types.
     */
    public function test_category_stats_returns_all_service_types(): void
    {
        // Arrange
        Listing::factory()->count(3)->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);
        Listing::factory()->count(4)->create([
            'service_type' => ServiceType::NAUTICAL,
            'status' => ListingStatus::PUBLISHED,
        ]);
        Listing::factory()->count(2)->create([
            'service_type' => ServiceType::ACCOMMODATION,
            'status' => ListingStatus::PUBLISHED,
        ]);
        Listing::factory()->count(5)->create([
            'service_type' => ServiceType::EVENT,
            'status' => ListingStatus::PUBLISHED,
        ]);

        // Act
        $response = $this->getJson('/api/v1/category-stats');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'tours' => ['count', 'images'],
                    'nautical' => ['count', 'images'],
                    'accommodations' => ['count', 'images'],
                    'events' => ['count', 'images'],
                ],
            ])
            ->assertJson([
                'data' => [
                    'tours' => ['count' => 3],
                    'nautical' => ['count' => 4],
                    'accommodations' => ['count' => 2],
                    'events' => ['count' => 5],
                ],
            ]);
    }

    /**
     * Test category stats only counts published listings.
     */
    public function test_category_stats_only_counts_published_listings(): void
    {
        // Arrange
        Listing::factory()->count(3)->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);
        Listing::factory()->count(2)->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::DRAFT,
        ]);
        Listing::factory()->count(1)->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::ARCHIVED,
        ]);

        // Act
        $response = $this->getJson('/api/v1/category-stats');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'tours' => ['count' => 3], // Only published
                ],
            ]);
    }

    /**
     * Test category stats returns empty counts when no listings exist.
     */
    public function test_category_stats_returns_zero_counts_when_empty(): void
    {
        // Act
        $response = $this->getJson('/api/v1/category-stats');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'tours' => ['count' => 0],
                    'nautical' => ['count' => 0],
                    'accommodations' => ['count' => 0],
                    'events' => ['count' => 0],
                ],
            ]);
    }

    /**
     * Test category stats includes images from listings.
     */
    public function test_category_stats_includes_images(): void
    {
        // Arrange
        Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);

        // Act
        $response = $this->getJson('/api/v1/category-stats');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');

        // Images should be an array (may contain fallbacks or actual images)
        $this->assertIsArray($data['tours']['images']);
    }

    /**
     * Test category stats is cached.
     */
    public function test_category_stats_is_cached(): void
    {
        // Arrange
        Listing::factory()->count(2)->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);

        // Act - First request
        $response1 = $this->getJson('/api/v1/category-stats');
        $response1->assertJson(['data' => ['tours' => ['count' => 2]]]);

        // Create more listings (should not affect cached result)
        Listing::factory()->count(3)->create([
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);

        // Act - Second request (should use cache)
        $response2 = $this->getJson('/api/v1/category-stats');

        // Assert - Should still show cached count of 2
        $response2->assertJson(['data' => ['tours' => ['count' => 2]]]);

        // Clear cache and verify new count
        Cache::forget('category_stats');
        $response3 = $this->getJson('/api/v1/category-stats');
        $response3->assertJson(['data' => ['tours' => ['count' => 5]]]);
    }
}
