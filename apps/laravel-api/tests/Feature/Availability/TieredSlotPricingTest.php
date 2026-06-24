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
 * A tiered listing keeps NORMAL per-person-type pricing, so its slots expose the
 * usual per-person-type effective prices and a headline "from" price taken from
 * the first person type — identical to a flat listing. Group discounts are an
 * overlay applied later to the total, not to the slot headline.
 */
class TieredSlotPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiered_slot_uses_normal_person_type_pricing(): void
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

        // Headline = first person type (adult) effective price in EUR.
        $this->assertEqualsWithDelta(40, $payload['displayPrice'], 0.001);

        // Per-person-type effective prices are exposed (no override -> listing values).
        $this->assertEqualsWithDelta(40, $payload['effectivePrices']['EUR']['adult'], 0.001);
        $this->assertEqualsWithDelta(24, $payload['effectivePrices']['EUR']['child'], 0.001);
        $this->assertEqualsWithDelta(50, $payload['effectivePrices']['TND']['adult'], 0.001);
        $this->assertEqualsWithDelta(30, $payload['effectivePrices']['TND']['child'], 0.001);
    }
}
