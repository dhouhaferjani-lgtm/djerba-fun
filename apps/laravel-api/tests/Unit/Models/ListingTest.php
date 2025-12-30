<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Review;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listing belongs to vendor.
     */
    public function test_listing_belongs_to_vendor(): void
    {
        // Arrange
        $vendor = VendorProfile::factory()->create();
        $listing = Listing::factory()->create(['vendor_profile_id' => $vendor->id]);

        // Act
        $listingVendor = $listing->vendorProfile;

        // Assert
        $this->assertInstanceOf(VendorProfile::class, $listingVendor);
        $this->assertEquals($vendor->id, $listingVendor->id);
    }

    /**
     * Test listing belongs to location.
     */
    public function test_listing_belongs_to_location(): void
    {
        // Arrange
        $location = Location::factory()->create();
        $listing = Listing::factory()->create(['location_id' => $location->id]);

        // Act
        $listingLocation = $listing->location;

        // Assert
        $this->assertInstanceOf(Location::class, $listingLocation);
        $this->assertEquals($location->id, $listingLocation->id);
    }

    /**
     * Test listing has many availability slots.
     */
    public function test_listing_has_many_availability_slots(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        AvailabilitySlot::factory()->count(3)->create(['listing_id' => $listing->id]);

        // Act
        $slots = $listing->availabilitySlots;

        // Assert
        $this->assertCount(3, $slots);
        $this->assertInstanceOf(AvailabilitySlot::class, $slots->first());
    }

    /**
     * Test listing has many bookings.
     */
    public function test_listing_has_many_bookings(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        Booking::factory()->count(5)->create(['listing_id' => $listing->id]);

        // Act
        $bookings = $listing->bookings;

        // Assert
        $this->assertCount(5, $bookings);
        $this->assertInstanceOf(Booking::class, $bookings->first());
    }

    /**
     * Test listing has many reviews.
     */
    public function test_listing_has_many_reviews(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        Review::factory()->count(3)->create(['listing_id' => $listing->id]);

        // Act
        $reviews = $listing->reviews;

        // Assert
        $this->assertCount(3, $reviews);
        $this->assertInstanceOf(Review::class, $reviews->first());
    }

    /**
     * Test active listings scope.
     */
    public function test_active_listings_scope(): void
    {
        // Arrange
        Listing::factory()->count(3)->create(['is_active' => true]);
        Listing::factory()->count(2)->create(['is_active' => false]);

        // Act
        $activeListings = Listing::active()->get();

        // Assert
        $this->assertCount(3, $activeListings);
    }

    /**
     * Test published listings scope.
     */
    public function test_published_listings_scope(): void
    {
        // Arrange
        Listing::factory()->count(2)->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        Listing::factory()->create([
            'is_active' => true,
            'published_at' => null,
        ]);

        // Act
        $publishedListings = Listing::published()->get();

        // Assert
        $this->assertCount(2, $publishedListings);
    }

    /**
     * Test average rating calculation.
     */
    public function test_average_rating_calculation(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        Review::factory()->create(['listing_id' => $listing->id, 'rating' => 5]);
        Review::factory()->create(['listing_id' => $listing->id, 'rating' => 4]);
        Review::factory()->create(['listing_id' => $listing->id, 'rating' => 3]);

        // Act
        $avgRating = $listing->reviews()->avg('rating');

        // Assert
        $this->assertEquals(4.0, $avgRating);
    }

    /**
     * Test slug is unique.
     */
    public function test_slug_is_unique(): void
    {
        // Arrange
        $listing1 = Listing::factory()->create();
        $listing2 = Listing::factory()->create();

        // Assert
        $this->assertNotEquals($listing1->slug, $listing2->slug);
        $this->assertNotNull($listing1->slug);
        $this->assertNotNull($listing2->slug);
    }

    /**
     * Test listing has pricing fields.
     */
    public function test_listing_has_pricing_fields(): void
    {
        // Arrange
        $listing = Listing::factory()->create([
            'adult_price_tnd' => 100.00,
            'adult_price_eur' => 30.00,
            'child_price_tnd' => 50.00,
            'child_price_eur' => 15.00,
        ]);

        // Assert
        $this->assertEquals(100.00, $listing->adult_price_tnd);
        $this->assertEquals(30.00, $listing->adult_price_eur);
        $this->assertEquals(50.00, $listing->child_price_tnd);
        $this->assertEquals(15.00, $listing->child_price_eur);
    }

    /**
     * Test listing metadata is cast to array.
     */
    public function test_listing_metadata_is_cast_to_array(): void
    {
        // Arrange
        $metadata = [
            'difficulty_level' => 'moderate',
            'duration_hours' => 4,
        ];

        $listing = Listing::factory()->create(['metadata' => $metadata]);

        // Act
        $storedMetadata = $listing->metadata;

        // Assert
        $this->assertIsArray($storedMetadata);
        $this->assertEquals('moderate', $storedMetadata['difficulty_level']);
        $this->assertEquals(4, $storedMetadata['duration_hours']);
    }

    /**
     * Test listing can have itinerary.
     */
    public function test_listing_can_have_itinerary(): void
    {
        // Arrange
        $itinerary = [
            ['name' => 'Start Point', 'description' => 'Meeting point'],
            ['name' => 'Checkpoint 1', 'description' => 'First stop'],
        ];

        $listing = Listing::factory()->create(['itinerary' => $itinerary]);

        // Assert
        $this->assertIsArray($listing->itinerary);
        $this->assertCount(2, $listing->itinerary);
        $this->assertEquals('Start Point', $listing->itinerary[0]['name']);
    }

    /**
     * Test listing availability check.
     */
    public function test_listing_has_available_slots(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(5),
            'capacity' => 10,
            'booked_count' => 5,
        ]);

        // Act
        $hasSlots = $listing->availabilitySlots()->count() > 0;

        // Assert
        $this->assertTrue($hasSlots);
    }
}
