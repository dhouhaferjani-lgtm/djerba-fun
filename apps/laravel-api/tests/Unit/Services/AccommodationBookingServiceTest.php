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
        $this->vendor = User::factory()->create(['role' => 'vendor']);

        // Create accommodation listing with per-night pricing
        $this->listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
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
     * Test that getBlockedDates returns explicitly blocked dates.
     */
    public function test_get_blocked_dates_returns_blocked_dates(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(3);

        // Create available slots for first and last day
        $this->createAvailabilitySlot($this->listing, $checkIn);
        $this->createAvailabilitySlot($this->listing, Carbon::today()->addDays(2));

        // Create BLOCKED slot for middle day (vendor explicitly blocked this date)
        AvailabilitySlot::create([
            'listing_id' => $this->listing->id,
            'date' => Carbon::today()->addDay()->format('Y-m-d'),
            'start_time' => Carbon::today()->addDay()->format('Y-m-d') . ' 15:00:00',
            'end_time' => Carbon::today()->addDays(2)->format('Y-m-d') . ' 11:00:00',
            'capacity' => 1,
            'remaining_capacity' => 0,
            'base_price' => 100.00,
            'status' => 'blocked',
        ]);

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
     * Test findSlotForCheckIn creates slot on-the-fly when no slot exists.
     * For accommodations, dates are available by default unless explicitly blocked.
     */
    public function test_find_slot_for_check_in_creates_slot_when_none_exists(): void
    {
        $checkIn = Carbon::today();
        // No slot created beforehand

        $foundSlot = $this->service->findSlotForCheckIn($this->listing, $checkIn);

        // Should create a slot on-the-fly for accommodations
        $this->assertNotNull($foundSlot);
        $this->assertEquals($this->listing->id, $foundSlot->listing_id);
        $this->assertEquals($checkIn->toDateString(), $foundSlot->date->toDateString());
        $this->assertEquals(1, $foundSlot->capacity);
        $this->assertTrue($foundSlot->isBookable());
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
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::TOUR,
            'pricing_model' => 'per_person',
        ]);

        $this->assertFalse($this->service->isPerNightPricing($tourListing));
    }

    /**
     * REGRESSION TEST: Slot with stale remaining_capacity=0 but no active holds
     * should still be bookable via computed accessor.
     *
     * This tests the fix for the DUAL-TRUTH CAPACITY BUG where
     * getBlockedDates() queried the stale database column instead of
     * using the computed accessor.
     */
    public function test_get_blocked_dates_uses_computed_accessor_not_stale_db_column(): void
    {
        $checkIn = Carbon::today();
        $checkOut = Carbon::today()->addDays(2);

        // Create slots with STALE remaining_capacity=0 in database
        // But NO actual holds or bookings exist
        for ($i = 0; $i < 2; $i++) {
            AvailabilitySlot::create([
                'listing_id' => $this->listing->id,
                'date' => Carbon::today()->addDays($i)->format('Y-m-d'),
                'start_time' => Carbon::today()->addDays($i)->setHour(15)->format('Y-m-d H:i:s'),
                'end_time' => Carbon::today()->addDays($i + 1)->setHour(11)->format('Y-m-d H:i:s'),
                'capacity' => 1,
                'remaining_capacity' => 0, // STALE VALUE - simulates expired hold scenario
                'base_price' => 100.00,
                'status' => 'available',
            ]);
        }

        // The computed accessor should return capacity=1 (no holds/bookings)
        // So getBlockedDates should return empty
        $blockedDates = $this->service->getBlockedDates($this->listing, $checkIn, $checkOut);

        $this->assertEmpty(
            $blockedDates,
            'Slots with stale remaining_capacity=0 but no actual holds should be available'
        );
    }

    /**
     * REGRESSION TEST: findSlotForCheckIn with stale remaining_capacity=0
     * should still return the slot when computed accessor shows availability.
     */
    public function test_find_slot_for_check_in_uses_computed_accessor_not_stale_db_column(): void
    {
        $checkIn = Carbon::today();

        // Create slot with STALE remaining_capacity=0 in database
        $slot = AvailabilitySlot::create([
            'listing_id' => $this->listing->id,
            'date' => $checkIn->format('Y-m-d'),
            'start_time' => $checkIn->setHour(15)->format('Y-m-d H:i:s'),
            'end_time' => $checkIn->copy()->addDay()->setHour(11)->format('Y-m-d H:i:s'),
            'capacity' => 1,
            'remaining_capacity' => 0, // STALE VALUE
            'base_price' => 100.00,
            'status' => 'available',
        ]);

        // Computed accessor should return capacity=1 (no holds/bookings)
        $foundSlot = $this->service->findSlotForCheckIn($this->listing, Carbon::today());

        $this->assertNotNull(
            $foundSlot,
            'Slot with stale remaining_capacity=0 but no actual holds should be returned'
        );
        $this->assertEquals($slot->id, $foundSlot->id);
    }

    /**
     * Test that slot with ACTUAL active hold is correctly blocked.
     */
    public function test_slot_with_active_hold_is_blocked(): void
    {
        $checkIn = Carbon::today();

        // Create slot with capacity=1
        $slot = AvailabilitySlot::create([
            'listing_id' => $this->listing->id,
            'date' => $checkIn->format('Y-m-d'),
            'start_time' => $checkIn->setHour(15)->format('Y-m-d H:i:s'),
            'end_time' => $checkIn->copy()->addDay()->setHour(11)->format('Y-m-d H:i:s'),
            'capacity' => 1,
            'remaining_capacity' => 1, // Accurate value
            'base_price' => 100.00,
            'status' => 'available',
        ]);

        // Create an ACTIVE hold that hasn't expired
        \App\Models\BookingHold::create([
            'slot_id' => $slot->id,
            'listing_id' => $this->listing->id,
            'quantity' => 1,
            'status' => \App\Enums\HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15), // Still valid
        ]);

        // Now the slot should be blocked
        $foundSlot = $this->service->findSlotForCheckIn($this->listing, Carbon::today());

        $this->assertNull(
            $foundSlot,
            'Slot with active hold should NOT be returned'
        );
    }

    /**
     * Test that slot with EXPIRED hold is available.
     */
    public function test_slot_with_expired_hold_is_available(): void
    {
        $checkIn = Carbon::today();

        // Create slot with capacity=1
        $slot = AvailabilitySlot::create([
            'listing_id' => $this->listing->id,
            'date' => $checkIn->format('Y-m-d'),
            'start_time' => $checkIn->setHour(15)->format('Y-m-d H:i:s'),
            'end_time' => $checkIn->copy()->addDay()->setHour(11)->format('Y-m-d H:i:s'),
            'capacity' => 1,
            'remaining_capacity' => 0, // Stale - was set when hold was created
            'base_price' => 100.00,
            'status' => 'available',
        ]);

        // Create an EXPIRED hold (expires_at is in the past)
        \App\Models\BookingHold::create([
            'slot_id' => $slot->id,
            'listing_id' => $this->listing->id,
            'quantity' => 1,
            'status' => \App\Enums\HoldStatus::ACTIVE, // Still marked active in DB
            'expires_at' => now()->subMinutes(5), // But already expired!
        ]);

        // The computed accessor should ignore the expired hold
        $foundSlot = $this->service->findSlotForCheckIn($this->listing, Carbon::today());

        $this->assertNotNull(
            $foundSlot,
            'Slot with expired hold should be available'
        );
        $this->assertEquals($slot->id, $foundSlot->id);
    }

    /**
     * BDD TEST: Service finds slots created by CalculateAvailabilityJob.
     *
     * Given: A slot created by CalculateAvailabilityJob with listing's check-in time
     * When: findSlotForCheckIn is called
     * Then: It should find the job-created slot (not create a duplicate)
     */
    public function test_find_slot_for_check_in_finds_job_created_slots(): void
    {
        $checkIn = Carbon::today();

        // Simulate job-created slot with listing's check_in_time (14:00)
        $jobCreatedSlot = AvailabilitySlot::create([
            'listing_id' => $this->listing->id,
            'date' => $checkIn->format('Y-m-d'),
            'start_time' => '14:00:00', // Same as listing's check_in_time
            'end_time' => '12:00:00',   // Next day check-out
            'capacity' => 1,
            'remaining_capacity' => 1,
            'base_price' => 100.00, // From nightly_price_eur
            'status' => 'available',
        ]);

        $foundSlot = $this->service->findSlotForCheckIn($this->listing, $checkIn);

        $this->assertNotNull($foundSlot, 'Should find the job-created slot');
        $this->assertEquals($jobCreatedSlot->id, $foundSlot->id, 'Should return the SAME slot, not create a new one');

        // Verify no duplicate slots were created
        $slotCount = AvailabilitySlot::where('listing_id', $this->listing->id)
            ->whereDate('date', $checkIn->format('Y-m-d'))
            ->count();
        $this->assertEquals(1, $slotCount, 'Should not create duplicate slots');
    }

    /**
     * BDD TEST: getBlockedDates finds job-created slots correctly.
     *
     * Given: Slots created with listing's check-in time
     * When: getBlockedDates is called for a date range
     * Then: It should find those slots (not create new ones)
     */
    public function test_get_blocked_dates_finds_job_created_slots(): void
    {
        // Use fixed dates to avoid Carbon mutation issues
        $baseDate = Carbon::today()->startOfDay();
        $checkIn = $baseDate->copy();
        $checkOut = $baseDate->copy()->addDays(3);

        // Ensure listing has check_in_time set (defaults to 14:00:00 in service)
        $checkInTime = $this->listing->check_in_time ?? '14:00:00';

        // Create slots with listing's check_in_time
        for ($i = 0; $i < 3; $i++) {
            $date = $baseDate->copy()->addDays($i)->toDateString();
            AvailabilitySlot::create([
                'listing_id' => $this->listing->id,
                'date' => $date,
                'start_time' => $checkInTime,
                'end_time' => '12:00:00',
                'capacity' => 1,
                'remaining_capacity' => 1,
                'base_price' => 100.00,
                'status' => 'available',
            ]);
        }

        // Count slots BEFORE calling getBlockedDates
        $countBefore = AvailabilitySlot::where('listing_id', $this->listing->id)->count();
        $this->assertEquals(3, $countBefore, 'Should have 3 slots before getBlockedDates');

        $blockedDates = $this->service->getBlockedDates($this->listing, $checkIn, $checkOut);

        $this->assertEmpty($blockedDates, 'All dates should be available');

        // Count slots AFTER - should still be 3 (no duplicates created)
        $countAfter = AvailabilitySlot::where('listing_id', $this->listing->id)->count();
        $this->assertEquals(3, $countAfter, 'Should still have 3 slots (no duplicates)');
    }

    /**
     * BDD TEST: Job-created slots have correct base_price from nightly_price_eur.
     *
     * Given: An accommodation listing with nightly_price_eur = 100.00
     * When: CalculateAvailabilityJob creates slots (triggered by AvailabilityRule observer)
     * Then: Slots should have base_price = 100.00 (not 0)
     */
    public function test_job_slots_have_correct_base_price(): void
    {
        // Note: AvailabilityRule observer automatically runs CalculateAvailabilityJob
        // when a rule is created, so we just need to verify the results

        // Create an availability rule for the listing
        // The observer will trigger slot creation automatically
        $rule = \App\Models\AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => \App\Enums\AvailabilityRuleType::WEEKLY,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'start_time' => Carbon::parse('14:00:00'),
            'end_time' => Carbon::parse('12:00:00'),
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'capacity' => 1,
            'is_active' => true,
        ]);

        // Check that slots have correct base_price (created by observer-triggered job)
        $slot = AvailabilitySlot::where('listing_id', $this->listing->id)
            ->whereDate('date', Carbon::today()->format('Y-m-d'))
            ->first();

        $this->assertNotNull($slot, 'Slot should have been created by observer-triggered job');
        $this->assertEquals(100.00, (float) $slot->base_price, 'Base price should be from nightly_price_eur');
        $this->assertEquals('14:00:00', $slot->start_time->format('H:i:s'), 'Start time should be from rule');
    }

    /**
     * BDD TEST: Consistent slot times between job and service.
     *
     * Given: CalculateAvailabilityJob and AccommodationBookingService
     * When: Both create slots for the same accommodation listing
     * Then: They should use the same start_time (from listing's check_in_time)
     */
    public function test_job_and_service_use_consistent_slot_times(): void
    {
        $checkIn = Carbon::today();

        // Update listing with specific check-in/check-out times
        $this->listing->update([
            'check_in_time' => '15:00:00',
            'check_out_time' => '10:00:00',
        ]);
        $this->listing->refresh();

        // Create on-demand slot via service
        $serviceSlot = $this->service->findSlotForCheckIn($this->listing, $checkIn);

        $this->assertNotNull($serviceSlot);
        $this->assertEquals('15:00:00', $serviceSlot->start_time->format('H:i:s'), 'Service should use listing check_in_time');
        $this->assertEquals('10:00:00', $serviceSlot->end_time->format('H:i:s'), 'Service should use listing check_out_time');
    }

    /**
     * Helper to create an availability slot.
     */
    protected function createAvailabilitySlot(Listing $listing, Carbon $date): AvailabilitySlot
    {
        return AvailabilitySlot::create([
            'listing_id' => $listing->id,
            'date' => $date->format('Y-m-d'),
            'start_time' => $date->format('Y-m-d') . ' 15:00:00',
            'end_time' => $date->copy()->addDay()->format('Y-m-d') . ' 11:00:00',
            'capacity' => 1,
            'remaining_capacity' => 1,
            'base_price' => 100.00,
            'status' => 'available',
        ]);
    }
}
