<?php

declare(strict_types=1);

namespace Tests\Feature\Availability;

use App\Enums\ServiceType;
use App\Http\Resources\AvailabilitySlotResource;
use App\Models\AvailabilitySlot;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * For a tiered listing, a slot has no person-type effective prices, so its
 * headline "from" price must come from tier T[1]. Flat slots are unaffected.
 */
class TieredSlotPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiered_slot_display_price_is_first_tier(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'tiers' => [
                    ['position' => 1, 'tnd_total' => 100, 'eur_total' => 30],
                    ['position' => 2, 'tnd_total' => 180, 'eur_total' => 54],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'price_overrides' => null,
        ]);
        $slot->setRelation('listing', $listing);

        // Request::create('/') has no user_currency attribute -> defaults to EUR.
        $payload = (new AvailabilitySlotResource($slot))->toArray(Request::create('/'));

        $this->assertEqualsWithDelta(30, $payload['displayPrice'], 0.001, 'From price = T[1] in EUR');
        $this->assertEqualsWithDelta(30, $payload['eurPrice'], 0.001);
        $this->assertEqualsWithDelta(100, $payload['tndPrice'], 0.001);
        // Tiered slots expose no per-person-type effective prices.
        $this->assertSame([], $payload['effectivePrices']['EUR']);
    }
}
