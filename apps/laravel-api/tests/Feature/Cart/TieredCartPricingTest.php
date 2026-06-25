<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Enums\ServiceType;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A tiered cart item prices via greedy "circle" packing of the optional group
 * discounts on top of normal per-person-type pricing.
 *
 * Canonical fixture: adult=50, child=30. Group discounts: size 2 -> 90, size 3 -> 130.
 * Packing: 2->90, 3->130, 4->130+50, 5->130+90, 6->130+130.
 */
class TieredCartPricingTest extends TestCase
{
    use RefreshDatabase;

    private function createTieredCartItem(array $breakdown, string $currency = 'TND'): CartItem
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
                    ['group_size' => 3, 'tnd_total' => 130, 'eur_total' => 130],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 20,
            'base_price' => 999,
            'currency' => $currency,
        ]);

        $cart = Cart::create([
            'session_id' => 'tiered-cart-' . uniqid(),
            'currency' => $currency,
            'expires_at' => now()->addHour(),
        ]);

        $quantity = array_sum($breakdown);

        $hold = BookingHold::create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'session_id' => 'tiered-hold-' . uniqid(),
            'cart_id' => $cart->id,
            'quantity' => $quantity,
            'person_type_breakdown' => $breakdown,
            'currency' => $currency,
            'price_snapshot' => 0,
            'expires_at' => now()->addMinutes(15),
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'hold_id' => $hold->id,
            'listing_id' => $listing->id,
            'listing_title' => ['en' => $listing->slug, 'fr' => $listing->slug],
            'slot_start' => $slot->start_time,
            'slot_end' => $slot->end_time,
            'quantity' => $quantity,
            'person_type_breakdown' => $breakdown,
            'unit_price' => 0, // deliberately 0 — a linear fallback would yield 0
            'currency' => $currency,
        ]);

        $item->load(['listing', 'hold.slot']);

        return $item;
    }

    public function test_group_of_2_applies_group_discount(): void
    {
        $this->assertEqualsWithDelta(90, $this->createTieredCartItem(['adult' => 2])->getSubtotal(), 0.001);
    }

    public function test_group_of_4_packs_group_of_3_plus_individual(): void
    {
        $this->assertEqualsWithDelta(180, $this->createTieredCartItem(['adult' => 4])->getSubtotal(), 0.001, '130 + 50');
    }

    public function test_group_of_4_packs_regardless_of_mix(): void
    {
        $this->assertEqualsWithDelta(180, $this->createTieredCartItem(['adult' => 2, 'child' => 2])->getSubtotal(), 0.001);
    }

    public function test_group_of_5_packs_group_of_3_plus_group_of_2(): void
    {
        $this->assertEqualsWithDelta(220, $this->createTieredCartItem(['adult' => 5])->getSubtotal(), 0.001, '130 + 90');
    }

    public function test_group_of_6_packs_two_groups_of_3(): void
    {
        $this->assertEqualsWithDelta(260, $this->createTieredCartItem(['adult' => 6])->getSubtotal(), 0.001);
    }

    public function test_single_traveller_uses_normal_pricing(): void
    {
        $this->assertEqualsWithDelta(50, $this->createTieredCartItem(['adult' => 1])->getSubtotal(), 0.001);
    }
}
