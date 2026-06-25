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
 * Canonical fixture: adult tnd=50/eur=40, child tnd=30/eur=24.
 * Group discounts: size 2 -> tnd 90/eur 72, size 3 -> tnd 130/eur 104.
 * Greedy "circle" packing: 2->90, 3->130, 4->130+50, 5->130+90, 6->130+130.
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
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 40, 'minAge' => 18],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 24, 'minAge' => 2, 'maxAge' => 17],
                ],
                'tiers' => [
                    ['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 72],
                    ['group_size' => 3, 'tnd_total' => 130, 'eur_total' => 104],
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

    public function test_group_of_4_packs_group_of_3_plus_one_individual(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 4]), $this->traveler());

        $this->assertEqualsWithDelta(180, (float) $booking->total_amount, 0.001, '130 + 1x50 (not 400)');
    }

    public function test_group_of_4_packs_regardless_of_person_type_mix(): void
    {
        // 2 adults + 2 children = 4 travellers -> group-of-3 + 1 individual at base rate.
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 2, 'child' => 2]), $this->traveler());

        $this->assertEqualsWithDelta(180, (float) $booking->total_amount, 0.001);
    }

    public function test_group_of_5_packs_group_of_3_plus_group_of_2(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 5]), $this->traveler());

        $this->assertEqualsWithDelta(220, (float) $booking->total_amount, 0.001, '130 + 90 (the circle)');
    }

    public function test_group_of_6_packs_two_groups_of_3(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 6]), $this->traveler());

        $this->assertEqualsWithDelta(260, (float) $booking->total_amount, 0.001, '130 + 130');
    }

    public function test_eur_uses_independent_packed_total(): void
    {
        // EUR: group-of-3 (104) + 1 individual (40) = 144.
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 4], 'EUR'), $this->traveler());

        $this->assertSame('EUR', $booking->currency);
        $this->assertEqualsWithDelta(144, (float) $booking->total_amount, 0.001);
    }

    public function test_booking_snapshot_records_strategy(): void
    {
        $booking = $this->service->createFromHold($this->createTieredHold(['adult' => 2]), $this->traveler());

        $this->assertSame('tiered', $booking->pricing_snapshot['pricing_strategy'] ?? null);
    }
}
