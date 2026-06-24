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
 * Feature tests: converting a hold into a booking must charge the optional
 * group-discount total when one applies (group sizes 2..5), and otherwise the
 * normal per-person-type total. The immutable pricing snapshot must record the
 * strategy so a later vendor edit cannot alter past quotes.
 *
 * Canonical fixture: adult=50, child=30 (TND & EUR equal).
 * Group discounts: size 2 -> 90, size 5 -> 200. Sizes 1/3/4/6+ -> normal pricing.
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

    private function createTieredHold(array $breakdown, string $currency = 'TND', array $overrides = []): BookingHold
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 50, 'minAge' => 18],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 30, 'minAge' => 2, 'maxAge' => 17],
                ],
                'tiers' => [
                    ['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 90],
                    ['group_size' => 5, 'tnd_total' => 200, 'eur_total' => 200],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 20,
            'base_price' => 999, // deliberately wrong — group totals MUST ignore slot base price
            'currency' => $currency,
        ]);

        return BookingHold::create(array_merge([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => null,
            'session_id' => 'tiered-session',
            'quantity' => array_sum($breakdown),
            'person_type_breakdown' => $breakdown,
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

    public function test_group_of_1_uses_normal_pricing(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 1]), $this->traveler());

        $this->assertEqualsWithDelta(50, (float) $booking->total_amount, 0.001);
        $this->assertSame('TND', $booking->currency);
    }

    public function test_group_of_2_charges_group_discount(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 2]), $this->traveler());

        $this->assertEqualsWithDelta(90, (float) $booking->total_amount, 0.001, 'group-of-2 total 90 (not 100)');
    }

    public function test_group_of_2_total_applies_regardless_of_person_type_mix(): void
    {
        // 1 adult + 1 child = 2 travellers -> the flat group-of-2 total applies.
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 1, 'child' => 1]), $this->traveler());

        $this->assertEqualsWithDelta(90, (float) $booking->total_amount, 0.001);
    }

    public function test_group_of_3_unset_uses_normal_pricing(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 3]), $this->traveler());

        $this->assertEqualsWithDelta(150, (float) $booking->total_amount, 0.001, '3 x 50 normal (no size-3 discount)');
    }

    public function test_group_of_5_charges_group_discount(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 5]), $this->traveler());

        $this->assertEqualsWithDelta(200, (float) $booking->total_amount, 0.001, 'group-of-5 total 200 (not 250)');
    }

    public function test_group_of_6_uses_normal_pricing(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 6]), $this->traveler());

        $this->assertEqualsWithDelta(300, (float) $booking->total_amount, 0.001, 'groups > 5 never discounted');
    }

    public function test_eur_uses_independent_group_totals(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 2], 'EUR'), $this->traveler());

        $this->assertSame('EUR', $booking->currency);
        $this->assertEqualsWithDelta(90, (float) $booking->total_amount, 0.001);
    }

    public function test_booking_snapshot_records_strategy(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 2]), $this->traveler());

        $this->assertSame('tiered', $booking->pricing_snapshot['pricing_strategy'] ?? null);
    }
}
