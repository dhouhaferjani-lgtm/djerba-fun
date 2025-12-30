<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Extra;
use App\Models\Listing;
use App\Models\ListingExtra;
use App\Services\ExtrasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtrasServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExtrasService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExtrasService::class);
    }

    /**
     * Test getting available extras for a listing.
     */
    public function test_get_available_extras_for_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $extra = Extra::factory()->create(['is_active' => true]);

        $listingExtra = ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra->id,
            'is_active' => true,
            'display_order' => 1,
        ]);

        // Act
        $availableExtras = $this->service->getAvailableExtras($listing);

        // Assert
        $this->assertCount(1, $availableExtras);
        $this->assertEquals($listingExtra->id, $availableExtras->first()['id']);
    }

    /**
     * Test that inactive extras are not returned.
     */
    public function test_inactive_extras_are_not_returned(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $inactiveExtra = Extra::factory()->create(['is_active' => false]);

        ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $inactiveExtra->id,
            'is_active' => true,
            'display_order' => 1,
        ]);

        // Act
        $availableExtras = $this->service->getAvailableExtras($listing);

        // Assert
        $this->assertCount(0, $availableExtras);
    }

    /**
     * Test that inactive listing extras are not returned.
     */
    public function test_inactive_listing_extras_are_not_returned(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $extra = Extra::factory()->create(['is_active' => true]);

        ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra->id,
            'is_active' => false,
            'display_order' => 1,
        ]);

        // Act
        $availableExtras = $this->service->getAvailableExtras($listing);

        // Assert
        $this->assertCount(0, $availableExtras);
    }

    /**
     * Test formatting extra for booking flow.
     */
    public function test_format_for_booking_flow(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $extra = Extra::factory()->create([
            'is_active' => true,
            'name' => ['en' => 'Equipment Rental', 'fr' => 'Location d\'équipement'],
            'track_inventory' => false,
        ]);

        $listingExtra = ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra->id,
            'is_active' => true,
            'display_order' => 1,
            'price_tnd' => 25.00,
            'price_eur' => 5.00,
        ]);

        // Act
        $formatted = $this->service->formatForBookingFlow($listingExtra, 'en');

        // Assert
        $this->assertEquals($listingExtra->id, $formatted['id']);
        $this->assertEquals($extra->id, $formatted['extraId']);
        $this->assertEquals('Equipment Rental', $formatted['name']);
        $this->assertEquals(25.00, $formatted['priceTnd']);
        $this->assertEquals(5.00, $formatted['priceEur']);
        $this->assertTrue($formatted['hasAvailableInventory']);
    }

    /**
     * Test extras with inventory tracking.
     */
    public function test_extras_with_inventory_tracking(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $extra = Extra::factory()->create([
            'is_active' => true,
            'track_inventory' => true,
            'inventory_count' => 5,
        ]);

        $listingExtra = ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra->id,
            'is_active' => true,
            'display_order' => 1,
        ]);

        // Act
        $formatted = $this->service->formatForBookingFlow($listingExtra);

        // Assert
        $this->assertTrue($formatted['trackInventory']);
        $this->assertEquals(5, $formatted['inventoryCount']);
        $this->assertTrue($formatted['hasAvailableInventory']);
    }

    /**
     * Test extras with zero inventory.
     */
    public function test_extras_with_zero_inventory(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $extra = Extra::factory()->create([
            'is_active' => true,
            'track_inventory' => true,
            'inventory_count' => 0,
        ]);

        $listingExtra = ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra->id,
            'is_active' => true,
            'display_order' => 1,
        ]);

        // Act
        $formatted = $this->service->formatForBookingFlow($listingExtra);

        // Assert
        $this->assertTrue($formatted['trackInventory']);
        $this->assertEquals(0, $formatted['inventoryCount']);
        $this->assertFalse($formatted['hasAvailableInventory']);
    }

    /**
     * Test extras are ordered by display order.
     */
    public function test_extras_are_ordered_by_display_order(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $extra1 = Extra::factory()->create(['is_active' => true, 'name' => ['en' => 'Extra 1']]);
        $extra2 = Extra::factory()->create(['is_active' => true, 'name' => ['en' => 'Extra 2']]);
        $extra3 = Extra::factory()->create(['is_active' => true, 'name' => ['en' => 'Extra 3']]);

        ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra1->id,
            'is_active' => true,
            'display_order' => 3,
        ]);

        ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra2->id,
            'is_active' => true,
            'display_order' => 1,
        ]);

        ListingExtra::create([
            'listing_id' => $listing->id,
            'extra_id' => $extra3->id,
            'is_active' => true,
            'display_order' => 2,
        ]);

        // Act
        $availableExtras = $this->service->getAvailableExtras($listing);

        // Assert
        $this->assertCount(3, $availableExtras);
        $this->assertEquals('Extra 2', $availableExtras->get(0)['name']);
        $this->assertEquals('Extra 3', $availableExtras->get(1)['name']);
        $this->assertEquals('Extra 1', $availableExtras->get(2)['name']);
    }
}
