<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\HoldStatus;
use App\Enums\ServiceType;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests: converting a TIERED hold into a booking must charge the
 * tiered total (not a per-unit multiply), and the immutable pricing snapshot
 * must record the strategy so a later vendor edit cannot alter past quotes.
 *
 * Canonical fixture: TND tiers [100,180,180] -> group of 6 = 360, 7 = 460.
 */
class TieredBookingPricingTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);
    }

    private function createTieredHold(int $quantity, string $currency = 'TND', array $overrides = []): BookingHold
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'tiers' => [
                    ['position' => 1, 'tnd_total' => 100, 'eur_total' => 30],
                    ['position' => 2, 'tnd_total' => 180, 'eur_total' => 54],
                    ['position' => 3, 'tnd_total' => 180, 'eur_total' => 54],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 20,
            'base_price' => 999, // deliberately wrong — tiered MUST ignore slot base price
            'currency' => $currency,
        ]);

        return BookingHold::create(array_merge([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => null,
            'session_id' => 'tiered-session',
            'quantity' => $quantity,
            'person_type_breakdown' => null, // tiered holds use the guests/quantity path
            'currency' => $currency,
            'price_snapshot' => 0,
            'pricing_country_code' => $currency === 'TND' ? 'TN' : 'FR',
            'pricing_source' => 'ip_geo',
            'expires_at' => now()->addMinutes(15),
            'status' => HoldStatus::ACTIVE,
        ], $overrides));
    }

    private function traveler(): array
    {
        return [['email' => 'tiered@example.com', 'first_name' => 'Tia', 'last_name' => 'Red']];
    }

    public function test_tiered_booking_charges_group_of_6_total(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(6), $this->traveler());

        $this->assertEqualsWithDelta(360, (float) $booking->total_amount, 0.001);
        $this->assertSame('TND', $booking->currency);
    }

    public function test_tiered_booking_charges_group_of_7_total(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(7), $this->traveler());

        $this->assertEqualsWithDelta(460, (float) $booking->total_amount, 0.001);
    }

    public function test_tiered_booking_eur_uses_independent_totals(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(7, 'EUR'), $this->traveler());

        // EUR tiers [30,54,54]: 2*54 + 30 = 138
        $this->assertEqualsWithDelta(138, (float) $booking->total_amount, 0.001);
    }

    public function test_tiered_booking_snapshot_records_strategy(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(6), $this->traveler());

        $this->assertSame('tiered', $booking->pricing_snapshot['pricing_strategy'] ?? null);
    }
}
