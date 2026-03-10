<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Vendor;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for itinerary (checkpoint) data persistence.
 *
 * These tests ensure that checkpoint data is preserved when editing listings,
 * and that the API returns itinerary data correctly.
 *
 * @see https://github.com/issue/map-not-showing-on-frontend
 */
class ListingItineraryTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->create([
            'role' => UserRole::VENDOR->value,
        ]);
    }

    /**
     * Test itinerary data is stored correctly in the database for a TOUR.
     */
    public function test_tour_itinerary_stored_correctly(): void
    {
        $itineraryData = [
            [
                'id' => 'checkpoint-1',
                'title' => ['en' => 'Start Point', 'fr' => 'Point de départ'],
                'description' => ['en' => 'Meeting point', 'fr' => 'Point de rendez-vous'],
                'lat' => 33.8750,
                'lng' => 10.8570,
                'elevationMeters' => 15,
                'pinType' => 'start',
            ],
            [
                'id' => 'checkpoint-2',
                'title' => ['en' => 'End Point', 'fr' => 'Point final'],
                'lat' => 33.8800,
                'lng' => 10.8650,
                'elevationMeters' => 10,
                'pinType' => 'end',
            ],
        ];

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::DRAFT,
            'itinerary' => $itineraryData,
            'map_display_type' => 'markers',
        ]);

        // Reload from database
        $listing->refresh();

        $this->assertIsArray($listing->itinerary);
        $this->assertCount(2, $listing->itinerary);
        $this->assertEquals('checkpoint-1', $listing->itinerary[0]['id']);
        $this->assertEquals(33.8750, $listing->itinerary[0]['lat']);
        $this->assertEquals(10.8570, $listing->itinerary[0]['lng']);
        $this->assertEquals('Start Point', $listing->itinerary[0]['title']['en']);
    }

    /**
     * Test itinerary data is preserved when updating other listing fields.
     * This simulates what happens when a user edits a listing via the form.
     */
    public function test_itinerary_preserved_on_model_update(): void
    {
        $itineraryData = [
            [
                'id' => 'test-1',
                'title' => ['en' => 'Test Checkpoint'],
                'lat' => 33.8750,
                'lng' => 10.8570,
            ],
        ];

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'itinerary' => $itineraryData,
        ]);

        // Update other fields (simulating form save)
        $listing->update([
            'title' => ['en' => 'Updated Title', 'fr' => 'Titre mis à jour'],
            'summary' => ['en' => 'Updated summary'],
        ]);

        $listing->refresh();

        // Itinerary should still be preserved
        $this->assertCount(1, $listing->itinerary);
        $this->assertEquals(33.8750, $listing->itinerary[0]['lat']);
    }

    /**
     * Test API returns itinerary data correctly for published listings.
     */
    public function test_api_returns_itinerary_for_published_tour(): void
    {
        $itineraryData = [
            [
                'id' => 'api-test-checkpoint',
                'title' => ['en' => 'Test Checkpoint', 'fr' => 'Point de test'],
                'lat' => 33.8750,
                'lng' => 10.8570,
            ],
        ];

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
            'itinerary' => $itineraryData,
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.itinerary.0.lat', 33.8750)
            ->assertJsonPath('data.itinerary.0.lng', 10.8570);
    }

    /**
     * Test API returns itinerary for NAUTICAL listings (isTourLike = true).
     */
    public function test_api_returns_itinerary_for_nautical(): void
    {
        $itineraryData = [
            [
                'id' => 'port-1',
                'title' => ['en' => 'Departure Port'],
                'lat' => 33.8750,
                'lng' => 10.8570,
            ],
        ];

        $listing = Listing::factory()->nautical()->create([
            'vendor_id' => $this->vendorUser->id,
            'status' => ListingStatus::PUBLISHED,
            'itinerary' => $itineraryData,
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.itinerary.0.lat', 33.8750);
    }

    /**
     * Test API returns itinerary for ACCOMMODATION listings (isTourLike = true).
     */
    public function test_api_returns_itinerary_for_accommodation(): void
    {
        $itineraryData = [
            [
                'id' => 'attraction-1',
                'title' => ['en' => 'Nearby Beach'],
                'lat' => 33.8750,
                'lng' => 10.8570,
            ],
        ];

        $listing = Listing::factory()->accommodation()->create([
            'vendor_id' => $this->vendorUser->id,
            'status' => ListingStatus::PUBLISHED,
            'itinerary' => $itineraryData,
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.itinerary.0.lat', 33.8750);
    }

    /**
     * Test isTourLike returns true for correct service types.
     */
    public function test_is_tour_like_for_service_types(): void
    {
        $this->assertTrue(ServiceType::TOUR->isTourLike());
        $this->assertTrue(ServiceType::NAUTICAL->isTourLike());
        $this->assertTrue(ServiceType::ACCOMMODATION->isTourLike());
        $this->assertFalse(ServiceType::EVENT->isTourLike());
    }

    /**
     * Test that lat/lng coordinates are preserved with correct precision.
     */
    public function test_coordinate_precision_preserved(): void
    {
        $itineraryData = [
            [
                'id' => 'precise-point',
                'title' => ['en' => 'Precise Location'],
                'lat' => 33.87501234,
                'lng' => 10.85706789,
                'elevationMeters' => 15.5,
            ],
        ];

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'itinerary' => $itineraryData,
        ]);

        $listing->refresh();

        $this->assertEquals(33.87501234, $listing->itinerary[0]['lat']);
        $this->assertEquals(10.85706789, $listing->itinerary[0]['lng']);
        $this->assertEquals(15.5, $listing->itinerary[0]['elevationMeters']);
    }

    /**
     * Test empty itinerary remains empty.
     */
    public function test_empty_itinerary_stays_empty(): void
    {
        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'itinerary' => [],
        ]);

        // Update other fields
        $listing->update(['title' => ['en' => 'Tour Without Route']]);
        $listing->refresh();

        $this->assertIsArray($listing->itinerary);
        $this->assertEmpty($listing->itinerary);
    }

    /**
     * Test null itinerary is handled gracefully.
     */
    public function test_null_itinerary_handled_gracefully(): void
    {
        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'itinerary' => null,
        ]);

        $listing->refresh();

        // Should be null (cast to array returns null if input is null)
        $this->assertTrue(
            $listing->itinerary === null || $listing->itinerary === [],
            'Itinerary should be null or empty array'
        );
    }

    /**
     * Test mapDisplayType defaults to 'markers' when not set.
     * Note: The database may default this to 'markers', so we just verify the API returns 'markers'.
     */
    public function test_map_display_type_defaults_to_markers(): void
    {
        // Create listing without explicitly setting map_display_type
        // The factory/database default should handle it
        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        // API should return 'markers' as default (ListingResource line 79: ?? 'markers')
        $response->assertOk()
            ->assertJsonPath('data.mapDisplayType', 'markers');
    }

    /**
     * Test mapDisplayType 'circle' is returned correctly.
     */
    public function test_map_display_type_circle_returned(): void
    {
        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendorUser->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
            'map_display_type' => 'circle',
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.mapDisplayType', 'circle');
    }
}
