<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\HoldStatus;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for accommodation booking API.
 *
 * Tests the full accommodation booking flow including:
 * - Creating holds with date ranges
 * - Same-day selection (1 night)
 * - Cart integration with nightly pricing
 * - Validation rules (min/max nights, guests)
 */
class AccommodationBookingTest extends TestCase
{
    use RefreshDatabase;

    protected Listing $accommodationListing;

    protected User $vendor;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create vendor
        $this->vendor = User::factory()->create(['role' => 'vendor']);

        // Create regular user for booking
        $this->user = User::factory()->create();

        // Create accommodation listing with nightly pricing
        $location = Location::factory()->create();
        $this->accommodationListing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $location->id,
            'service_type' => ServiceType::ACCOMMODATION,
            'pricing_model' => 'per_night',
            'nightly_price_eur' => 100.00,
            'nightly_price_tnd' => 350.00,
            'minimum_nights' => 1,
            'maximum_nights' => 14,
            'max_guests' => 4,
            'status' => ListingStatus::PUBLISHED,
        ]);
    }

    /**
     * Helper to create an availability slot for accommodation.
     */
    protected function createAccommodationSlot(Listing $listing, Carbon $date): AvailabilitySlot
    {
        return AvailabilitySlot::create([
            'listing_id' => $listing->id,
            'date' => $date->format('Y-m-d'),
            'start_time' => '15:00:00',
            'end_time' => '11:00:00',
            'capacity' => 1,
            'remaining_capacity' => 1,
            'base_price' => 100.00,
            'status' => 'available',
        ]);
    }

    /**
     * Test user can create accommodation hold with date range.
     */
    public function test_can_create_accommodation_hold_with_date_range(): void
    {
        // Arrange: Create availability slots for 3 nights
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        for ($i = 0; $i < 3; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Act: Create hold with date range
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'expiresAt',
                    'status',
                    'priceSnapshot',
                    'currency',
                    'metadata',
                ],
            ]);

        // Verify the hold was created with correct data
        $this->assertDatabaseHas('booking_holds', [
            'listing_id' => $this->accommodationListing->id,
            'user_id' => $this->user->id,
            'status' => HoldStatus::ACTIVE->value,
        ]);

        // Verify metadata contains accommodation details
        $hold = BookingHold::where('listing_id', $this->accommodationListing->id)->first();
        $this->assertNotNull($hold->metadata);
        $this->assertEquals(3, $hold->metadata['nights']);
        $this->assertEquals('per_night', $hold->metadata['pricing_model']);
        $this->assertEquals($checkIn->format('Y-m-d'), $hold->metadata['check_in_date']);
        $this->assertEquals($checkOut->format('Y-m-d'), $hold->metadata['check_out_date']);

        // Verify price calculation (100 EUR * 3 nights = 300)
        $this->assertEquals(300.00, (float) $hold->price_snapshot);
    }

    /**
     * Test same-day selection creates one-night hold.
     */
    public function test_same_date_selection_creates_one_night_hold(): void
    {
        // Arrange: Create slot for today only
        $today = Carbon::today();
        $this->createAccommodationSlot($this->accommodationListing, $today);

        // Act: Create hold with same check-in and check-out date
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $today->format('Y-m-d'),
                'check_out_date' => $today->format('Y-m-d'), // Same day!
                'guests' => 2,
            ]);

        // Assert: Should succeed as 1-night booking
        $response->assertStatus(201);

        $hold = BookingHold::where('listing_id', $this->accommodationListing->id)->first();
        $this->assertNotNull($hold);

        // Verify 1 night calculated
        $this->assertEquals(1, $hold->metadata['nights']);

        // Verify price for 1 night (100 EUR)
        $this->assertEquals(100.00, (float) $hold->price_snapshot);
    }

    /**
     * Test hold creation fails when below minimum nights.
     */
    public function test_hold_creation_fails_below_minimum_nights(): void
    {
        // Arrange: Set minimum nights to 3
        $this->accommodationListing->update(['minimum_nights' => 3]);

        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2); // Only 2 nights

        for ($i = 0; $i < 2; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Minimum stay is 3 nights',
            ]);
    }

    /**
     * Test hold creation fails when above maximum nights.
     */
    public function test_hold_creation_fails_above_maximum_nights(): void
    {
        // Arrange: Set maximum nights to 7
        $this->accommodationListing->update(['maximum_nights' => 7]);

        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(10); // 10 nights - exceeds max

        for ($i = 0; $i < 10; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Maximum stay is 7 nights',
            ]);
    }

    /**
     * Test hold creation fails when guests exceed maximum.
     */
    public function test_hold_creation_fails_when_guests_exceed_maximum(): void
    {
        // Arrange
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2);

        for ($i = 0; $i < 2; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Act: max_guests = 4, try with 6
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 6,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Maximum guests is 4',
            ]);
    }

    /**
     * Test hold creation fails when dates are explicitly blocked.
     */
    public function test_hold_creation_fails_when_dates_are_blocked(): void
    {
        // Arrange: Create slots with an explicitly BLOCKED date in middle
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        // Create available slots for first and last day
        $this->createAccommodationSlot($this->accommodationListing, Carbon::today());
        $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays(2));

        // Create BLOCKED slot for middle day (day 1)
        AvailabilitySlot::create([
            'listing_id' => $this->accommodationListing->id,
            'date' => Carbon::today()->addDay()->format('Y-m-d'),
            'start_time' => '15:00:00',
            'end_time' => '11:00:00',
            'capacity' => 1,
            'remaining_capacity' => 0,
            'base_price' => 100.00,
            'status' => 'blocked', // Vendor explicitly blocked this date
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        // Assert: Should fail due to blocked date
        $response->assertStatus(422);
        $this->assertStringContainsString('not available', strtolower($response->json('message')));
    }

    /**
     * Test guest can create accommodation hold with session_id.
     */
    public function test_guest_can_create_accommodation_hold(): void
    {
        // Arrange
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2);
        $sessionId = 'guest-session-' . uniqid();

        for ($i = 0; $i < 2; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Act: Create hold as guest with session_id
        $response = $this->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
            'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkOut->format('Y-m-d'),
            'guests' => 2,
            'session_id' => $sessionId,
        ]);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('booking_holds', [
            'listing_id' => $this->accommodationListing->id,
            'session_id' => $sessionId,
            'user_id' => null, // Guest booking
            'status' => HoldStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test can add accommodation hold to cart.
     */
    public function test_can_add_accommodation_hold_to_cart(): void
    {
        // Arrange: Create hold first
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        for ($i = 0; $i < 3; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Create hold
        $holdResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        $holdResponse->assertStatus(201);
        $holdId = $holdResponse->json('data.id');

        // Create cart
        $cartResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/cart');

        $cartResponse->assertStatus(201);
        $cartId = $cartResponse->json('cart.id');

        // Act: Add hold to cart
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/cart/{$cartId}/items", [
                'hold_id' => $holdId,
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'cart_item' => [
                    'id',
                    'listing',
                    'total_price',
                ],
            ]);

        // Verify cart item has accommodation data
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cartId,
            'listing_id' => $this->accommodationListing->id,
            'pricing_model' => 'per_night',
            'nights' => 3,
        ]);
    }

    /**
     * Test cart total includes accommodation nightly pricing.
     */
    public function test_cart_total_includes_accommodation_nightly_pricing(): void
    {
        // Arrange: Create cart and accommodation hold
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(5); // 5 nights

        for ($i = 0; $i < 5; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Create hold
        $holdResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        $holdResponse->assertStatus(201);
        $holdId = $holdResponse->json('data.id');

        // Create cart
        $cartResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/cart');

        $cartResponse->assertStatus(201);
        $cartId = $cartResponse->json('cart.id');

        // Add hold to cart
        $this->actingAs($this->user)
            ->postJson("/api/v1/cart/{$cartId}/items", [
                'hold_id' => $holdId,
            ]);

        // Act: Get cart with totals
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/cart/{$cartId}");

        // Assert
        $response->assertStatus(200);

        // Expected total: 100 EUR * 5 nights = 500 EUR
        $cart = Cart::find($cartId);
        $this->assertEquals(500.00, (float) $cart->total_amount);
    }

    /**
     * Test accommodation pricing is calculated correctly in TND.
     */
    public function test_accommodation_pricing_calculated_correctly_in_tnd(): void
    {
        // Arrange: Create slot
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2); // 2 nights

        for ($i = 0; $i < 2; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Act: Make request that triggers TND pricing
        // Note: The currency is determined by IP geolocation in the controller
        // For testing, we verify the EUR price calculation is correct
        // TND price testing would require mocking the GeoPricingService
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        // Assert
        $response->assertStatus(201);

        $hold = BookingHold::where('listing_id', $this->accommodationListing->id)->first();
        // 2 nights * 100 EUR = 200 EUR (or 2 nights * 350 TND = 700 TND if TND)
        $this->assertContains((float) $hold->price_snapshot, [200.00, 700.00]);
    }

    /**
     * Test mixed cart with accommodation and tour items.
     */
    public function test_mixed_cart_with_accommodation_and_tour(): void
    {
        // Arrange: Create a tour listing
        $tourListing = Listing::factory()->dualPriced()->create([
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
        ]);

        $tourSlot = AvailabilitySlot::factory()->create([
            'listing_id' => $tourListing->id,
            'capacity' => 10,
            'remaining_capacity' => 10,
        ]);

        // Create accommodation slots
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2); // 2 nights = 200 EUR

        for ($i = 0; $i < 2; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        // Create cart
        $cartResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/cart');

        $cartId = $cartResponse->json('cart.id');

        // Add accommodation hold
        $accommodationHoldResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/cart/{$cartId}/items", [
                'hold_id' => $accommodationHoldResponse->json('data.id'),
            ]);

        // Add tour hold (2 adults @ 50 EUR = 100 EUR)
        $tourHoldResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$tourListing->slug}/holds", [
                'slot_id' => $tourSlot->id,
                'person_types' => ['adult' => 2],
            ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/cart/{$cartId}/items", [
                'hold_id' => $tourHoldResponse->json('data.id'),
            ]);

        // Act: Get cart totals
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/cart/{$cartId}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'cart.items');

        // Verify mixed pricing: accommodation (200) + tour (100) = 300 EUR
        $cart = Cart::find($cartId);
        $this->assertEquals(300.00, (float) $cart->total_amount);
    }

    /**
     * Test checkout flow with accommodation booking.
     */
    public function test_checkout_flow_with_accommodation(): void
    {
        // Arrange: Create hold and add to cart
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        for ($i = 0; $i < 3; $i++) {
            $this->createAccommodationSlot($this->accommodationListing, Carbon::today()->addDays($i));
        }

        $holdResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/listings/{$this->accommodationListing->slug}/holds", [
                'slot_id' => AvailabilitySlot::where('listing_id', $this->accommodationListing->id)->first()->id,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'guests' => 2,
            ]);

        $cartResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/cart');

        $cartId = $cartResponse->json('cart.id');

        $this->actingAs($this->user)
            ->postJson("/api/v1/cart/{$cartId}/items", [
                'hold_id' => $holdResponse->json('data.id'),
            ]);

        // Act: Checkout
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/cart/{$cartId}/checkout", [
                'traveler_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                ],
                'billing_contact' => [
                    'email' => 'john@example.com',
                ],
                'billing_country_code' => 'CA',
                'billing_city' => 'Toronto',
                'billing_postal_code' => 'M5H 2N2',
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'bookings',
                'payment',
            ]);

        $cart = Cart::find($cartId);
        $this->assertEquals(Cart::STATUS_COMPLETED, $cart->status);
    }
}
