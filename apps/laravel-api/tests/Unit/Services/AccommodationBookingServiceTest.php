<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\AvailabilitySlot;
use App\Models\Listing;
use App\Models\User;
use App\Services\AccommodationBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for AccommodationBookingService.
 *
 * Validates date range validation, pricing calculation, and blocked date detection
 * for accommodation bookings with per-night pricing.
 */
class AccommodationBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccommodationBookingService $service;

    protected Listing $listing;

    protected User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AccommodationBookingService::class);

        // Create vendor
        $this->vendor = User::factory()->create();

        // Create accommodation listing with per-night pricing
        $this->listing = Listing::factory()->create([
            'user_id' => $this->vendor->id,
            'service_type' => ServiceType::ACCOMMODATION,
            'pricing_model' => 'per_night',
            'nightly_price_eur' => 100.00,
            'nightly_price_tnd' => 350.00,
            'minimum_nights' => 1,
            'maximum_nights' => 30,
            'max_guests' => 4,
            'status' => ListingStatus::PUBLISHED,
        ]);
    }

    /**
     * Test that same-day booking returns 1 night.
     */
    public function test_validate_date_range_same_day_equals_one_night(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today(); // Same day

        // Create availability slot for today
        $this->createAvailabilitySlot($this->listing, $checkIn);

        $result = $this->service->validateDateRange($this->listing, $checkIn, $checkOut, 2);

        $this->assertTrue($result['valid'], 'Same-day booking should be valid');
        $this->assertEquals(1, $result['nights'], 'Same-day should be counted as 1 night');
    }

    /**
     * Test that date range calculates nights correctly.
     */
    public function test_validate_date_range_calculates_nights_correctly(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(5);

        // Create availability slots for all dates
        for ($i = 0; $i < 5; $i++) {
            $this->createAvailabilitySlot($this->listing, Carbon::today()->addDays($i));
        }

        $result = $this->service->validateDateRange($this->listing, $checkIn, $checkOut, 2);

        $this->assertTrue($result['valid']);
        $this->assertEquals(5, $result['nights']);
    }

    /**
     * Test that validation fails when below minimum nights.
     */
    public function test_validate_date_range_fails_below_minimum_nights(): void
    {
        // Update listing to require minimum 3 nights
        $this->listing->update(['minimum_nights' => 3]);

        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2); // Only 2 nights

        // Create slots
        $this->createAvailabilitySlot($this->listing, $checkIn);
        $this->createAvailabilitySlot($this->listing, Carbon::today()->addDay());

        $result = $this->service->validateDateRange($this->listing, $checkIn, $checkOut, 2);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Minimum stay is 3 nights', $result['message']);
    }

    /**
     * Test that validation fails when above maximum nights.
     */
    public function test_validate_date_range_fails_above_maximum_nights(): void
    {
        // Update listing to max 7 nights
        $this->listing->update(['maximum_nights' => 7]);

        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(10); // 10 nights - exceeds max

        // Create slots for 10 days
        for ($i = 0; $i < 10; $i++) {
            $this->createAvailabilitySlot($this->listing, Carbon::today()->addDays($i));
        }

        $result = $this->service->validateDateRange($this->listing, $checkIn, $checkOut, 2);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Maximum stay is 7 nights', $result['message']);
    }

    /**
     * Test that validation fails when guests exceed maximum.
     */
    public function test_validate_date_range_fails_when_guests_exceed_maximum(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2);

        $this->createAvailabilitySlot($this->listing, $checkIn);
        $this->createAvailabilitySlot($this->listing, Carbon::today()->addDay());

        // Listing has max_guests = 4, try with 6
        $result = $this->service->validateDateRange($this->listing, $checkIn, $checkOut, 6);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Maximum guests is 4', $result['message']);
    }

    /**
     * Test that getBlockedDates returns empty when all dates are available.
     */
    public function test_get_blocked_dates_returns_empty_when_all_available(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        // Create all slots as available
        for ($i = 0; $i < 3; $i++) {
            $this->createAvailabilitySlot($this->listing, Carbon::today()->addDays($i));
        }

        $blockedDates = $this->service->getBlockedDates($this->listing, $checkIn, $checkOut);

        $this->assertEmpty($blockedDates);
    }

    /**
     * Test that getBlockedDates returns blocked dates.
     */
    public function test_get_blocked_dates_returns_blocked_dates(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        // Create first and last slot available, middle one missing (blocked)
        $this->createAvailabilitySlot($this->listing, $checkIn);
        // Skip middle date - no slot = blocked
        $this->createAvailabilitySlot($this->listing, Carbon::today()->addDays(2));

        $blockedDates = $this->service->getBlockedDates($this->listing, $checkIn, $checkOut);

        $this->assertCount(1, $blockedDates);
        $this->assertEquals(Carbon::today()->addDay()->format('Y-m-d'), $blockedDates[0]->format('Y-m-d'));
    }

    /**
     * Test that getBlockedDates handles same-day booking.
     */
    public function test_get_blocked_dates_handles_same_day(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today(); // Same day

        // Create slot for today
        $this->createAvailabilitySlot($this->listing, $checkIn);

        $blockedDates = $this->service->getBlockedDates($this->listing, $checkIn, $checkOut);

        $this->assertEmpty($blockedDates, 'Same-day booking with available slot should have no blocked dates');
    }

    /**
     * Test price calculation with EUR currency.
     */
    public function test_calculate_price_with_eur_currency(): void
    {
        $result = $this->service->calculatePrice($this->listing, 3, 'EUR');

        $this->assertEquals(300.00, $result['total']); // 100 EUR × 3 nights
        $this->assertEquals(100.00, $result['nightly_rate']);
        $this->assertEquals(3, $result['nights']);
        $this->assertEquals('EUR', $result['currency']);
    }

    /**
     * Test price calculation with TND currency.
     */
    public function test_calculate_price_with_tnd_currency(): void
    {
        $result = $this->service->calculatePrice($this->listing, 2, 'TND');

        $this->assertEquals(700.00, $result['total']); // 350 TND × 2 nights
        $this->assertEquals(350.00, $result['nightly_rate']);
        $this->assertEquals(2, $result['nights']);
        $this->assertEquals('TND', $result['currency']);
    }

    /**
     * Test findSlotForCheckIn returns available slot.
     */
    public function test_find_slot_for_check_in_returns_available_slot(): void
    {
        $checkIn = Carbon::today();
        $slot = $this->createAvailabilitySlot($this->listing, $checkIn);

        $foundSlot = $this->service->findSlotForCheckIn($this->listing, $checkIn);

        $this->assertNotNull($foundSlot);
        $this->assertEquals($slot->id, $foundSlot->id);
    }

    /**
     * Test findSlotForCheckIn returns null when no slot exists.
     */
    public function test_find_slot_for_check_in_returns_null_when_no_slot(): void
    {
        $checkIn = Carbon::today();
        // No slot created

        $foundSlot = $this->service->findSlotForCheckIn($this->listing, $checkIn);

        $this->assertNull($foundSlot);
    }

    /**
     * Test isPerNightPricing returns true for accommodation.
     */
    public function test_is_per_night_pricing_returns_true_for_accommodation(): void
    {
        $this->assertTrue($this->service->isPerNightPricing($this->listing));
    }

    /**
     * Test isPerNightPricing returns false for tour.
     */
    public function test_is_per_night_pricing_returns_false_for_tour(): void
    {
        $tourListing = Listing::factory()->create([
            'user_id' => $this->vendor->id,
            'service_type' => ServiceType::TOUR,
            'pricing_model' => 'per_person',
        ]);

        $this->assertFalse($this->service->isPerNightPricing($tourListing));
    }

    /**
     * Helper to create an availability slot.
     */
    protected function createAvailabilitySlot(Listing $listing, Carbon $date): AvailabilitySlot
    {
        return AvailabilitySlot::create([
            'listing_id' => $listing->id,
            'start' => $date->format('Y-m-d') . ' 15:00:00',
            'end' => $date->copy()->addDay()->format('Y-m-d') . ' 11:00:00',
            'capacity' => 1,
            'remaining_capacity' => 1,
            'status' => 'available',
        ]);
    }
}
