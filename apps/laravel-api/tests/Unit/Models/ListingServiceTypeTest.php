<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ServiceType;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Listing model service type functionality.
 *
 * MIGRATION NOTE: Evasion Djerba requires these service types:
 * - TOUR (existing)
 * - NAUTICAL (new - for water activities)
 * - ACCOMMODATION (renamed from SEJOUR)
 * - EVENT (existing)
 */
class ListingServiceTypeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test isTour() returns true only for tour listings.
     */
    public function test_is_tour_returns_true_for_tours(): void
    {
        $tour = Listing::factory()->create(['service_type' => ServiceType::TOUR]);
        $nautical = Listing::factory()->create(['service_type' => ServiceType::NAUTICAL]);
        $accommodation = Listing::factory()->create(['service_type' => ServiceType::ACCOMMODATION]);
        $event = Listing::factory()->create(['service_type' => ServiceType::EVENT]);

        $this->assertTrue($tour->isTour());
        $this->assertFalse($nautical->isTour());
        $this->assertFalse($accommodation->isTour());
        $this->assertFalse($event->isTour());
    }

    /**
     * Test isNautical() returns true only for nautical listings.
     */
    public function test_is_nautical_returns_true_for_nautical(): void
    {
        $tour = Listing::factory()->create(['service_type' => ServiceType::TOUR]);
        $nautical = Listing::factory()->create(['service_type' => ServiceType::NAUTICAL]);
        $accommodation = Listing::factory()->create(['service_type' => ServiceType::ACCOMMODATION]);
        $event = Listing::factory()->create(['service_type' => ServiceType::EVENT]);

        $this->assertFalse($tour->isNautical());
        $this->assertTrue($nautical->isNautical());
        $this->assertFalse($accommodation->isNautical());
        $this->assertFalse($event->isNautical());
    }

    /**
     * Test isAccommodation() returns true only for accommodation listings.
     */
    public function test_is_accommodation_returns_true_for_accommodations(): void
    {
        $tour = Listing::factory()->create(['service_type' => ServiceType::TOUR]);
        $nautical = Listing::factory()->create(['service_type' => ServiceType::NAUTICAL]);
        $accommodation = Listing::factory()->create(['service_type' => ServiceType::ACCOMMODATION]);
        $event = Listing::factory()->create(['service_type' => ServiceType::EVENT]);

        $this->assertFalse($tour->isAccommodation());
        $this->assertFalse($nautical->isAccommodation());
        $this->assertTrue($accommodation->isAccommodation());
        $this->assertFalse($event->isAccommodation());
    }

    /**
     * Test isEvent() returns true only for event listings.
     */
    public function test_is_event_returns_true_for_events(): void
    {
        $tour = Listing::factory()->create(['service_type' => ServiceType::TOUR]);
        $nautical = Listing::factory()->create(['service_type' => ServiceType::NAUTICAL]);
        $accommodation = Listing::factory()->create(['service_type' => ServiceType::ACCOMMODATION]);
        $event = Listing::factory()->create(['service_type' => ServiceType::EVENT]);

        $this->assertFalse($tour->isEvent());
        $this->assertFalse($nautical->isEvent());
        $this->assertFalse($accommodation->isEvent());
        $this->assertTrue($event->isEvent());
    }

    /**
     * Test isTourLike() returns true for tour-like listings.
     * Tour-like = shares tour fields (duration, itinerary, difficulty).
     * This includes: TOUR, NAUTICAL, ACCOMMODATION (but NOT EVENT).
     */
    public function test_is_tour_like_returns_true_for_tour_like_types(): void
    {
        $tour = Listing::factory()->create(['service_type' => ServiceType::TOUR]);
        $nautical = Listing::factory()->create(['service_type' => ServiceType::NAUTICAL]);
        $accommodation = Listing::factory()->create(['service_type' => ServiceType::ACCOMMODATION]);
        $event = Listing::factory()->create(['service_type' => ServiceType::EVENT]);

        $this->assertTrue($tour->isTourLike(), 'Tour should be tour-like');
        $this->assertTrue($nautical->isTourLike(), 'Nautical should be tour-like');
        $this->assertTrue($accommodation->isTourLike(), 'Accommodation should be tour-like');
        $this->assertFalse($event->isTourLike(), 'Event should NOT be tour-like');
    }

    /**
     * Test scopeTours() filters only tour listings.
     */
    public function test_scope_tours(): void
    {
        Listing::factory()->count(3)->create(['service_type' => ServiceType::TOUR]);
        Listing::factory()->count(2)->create(['service_type' => ServiceType::NAUTICAL]);
        Listing::factory()->count(2)->create(['service_type' => ServiceType::ACCOMMODATION]);
        Listing::factory()->count(1)->create(['service_type' => ServiceType::EVENT]);

        $tours = Listing::tours()->get();

        $this->assertCount(3, $tours);
        $this->assertTrue($tours->every(fn($l) => $l->service_type === ServiceType::TOUR));
    }

    /**
     * Test scopeNautical() filters only nautical listings.
     */
    public function test_scope_nautical(): void
    {
        Listing::factory()->count(2)->create(['service_type' => ServiceType::TOUR]);
        Listing::factory()->count(4)->create(['service_type' => ServiceType::NAUTICAL]);
        Listing::factory()->count(1)->create(['service_type' => ServiceType::ACCOMMODATION]);
        Listing::factory()->count(1)->create(['service_type' => ServiceType::EVENT]);

        $nautical = Listing::nautical()->get();

        $this->assertCount(4, $nautical);
        $this->assertTrue($nautical->every(fn($l) => $l->service_type === ServiceType::NAUTICAL));
    }

    /**
     * Test scopeAccommodations() filters only accommodation listings.
     */
    public function test_scope_accommodations(): void
    {
        Listing::factory()->count(2)->create(['service_type' => ServiceType::TOUR]);
        Listing::factory()->count(2)->create(['service_type' => ServiceType::NAUTICAL]);
        Listing::factory()->count(3)->create(['service_type' => ServiceType::ACCOMMODATION]);
        Listing::factory()->count(1)->create(['service_type' => ServiceType::EVENT]);

        $accommodations = Listing::accommodations()->get();

        $this->assertCount(3, $accommodations);
        $this->assertTrue($accommodations->every(fn($l) => $l->service_type === ServiceType::ACCOMMODATION));
    }

    /**
     * Test scopeEvents() filters only event listings.
     */
    public function test_scope_events(): void
    {
        Listing::factory()->count(2)->create(['service_type' => ServiceType::TOUR]);
        Listing::factory()->count(2)->create(['service_type' => ServiceType::NAUTICAL]);
        Listing::factory()->count(2)->create(['service_type' => ServiceType::ACCOMMODATION]);
        Listing::factory()->count(5)->create(['service_type' => ServiceType::EVENT]);

        $events = Listing::events()->get();

        $this->assertCount(5, $events);
        $this->assertTrue($events->every(fn($l) => $l->service_type === ServiceType::EVENT));
    }

    /**
     * Test getNumberOfDaysAttribute for accommodation listings.
     */
    public function test_number_of_days_attribute_for_accommodations(): void
    {
        $accommodation = Listing::factory()->create([
            'service_type' => ServiceType::ACCOMMODATION,
            'itinerary' => [
                ['day' => 1, 'title' => ['en' => 'Day 1'], 'description' => ['en' => 'Arrival']],
                ['day' => 2, 'title' => ['en' => 'Day 2'], 'description' => ['en' => 'Activities']],
                ['day' => 3, 'title' => ['en' => 'Day 3'], 'description' => ['en' => 'Departure']],
            ],
        ]);

        $this->assertEquals(3, $accommodation->number_of_days);
    }

    /**
     * Test getNumberOfDaysAttribute returns null for non-accommodation types.
     */
    public function test_number_of_days_attribute_returns_null_for_non_accommodations(): void
    {
        $tour = Listing::factory()->create(['service_type' => ServiceType::TOUR]);
        $nautical = Listing::factory()->create(['service_type' => ServiceType::NAUTICAL]);
        $event = Listing::factory()->create(['service_type' => ServiceType::EVENT]);

        $this->assertNull($tour->number_of_days);
        $this->assertNull($nautical->number_of_days);
        $this->assertNull($event->number_of_days);
    }

    /**
     * Test service type is properly cast to enum.
     */
    public function test_service_type_is_cast_to_enum(): void
    {
        $listing = Listing::factory()->create(['service_type' => ServiceType::TOUR]);

        $this->assertInstanceOf(ServiceType::class, $listing->service_type);
        $this->assertEquals(ServiceType::TOUR, $listing->service_type);
    }

    /**
     * Test listing can be created with each service type.
     */
    public function test_can_create_listing_with_each_service_type(): void
    {
        foreach (ServiceType::cases() as $type) {
            $listing = Listing::factory()->create(['service_type' => $type]);

            $this->assertEquals($type, $listing->service_type);
            $this->assertDatabaseHas('listings', [
                'id' => $listing->id,
                'service_type' => $type->value,
            ]);
        }
    }
}
