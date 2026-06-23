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
 * A tiered cart item must price by headcount (non-linear). It must NEVER
 * fall back to `unit_price * quantity`, which would be wrong for tiered.
 */
class TieredCartPricingTest extends TestCase
{
    use RefreshDatabase;

    private function createTieredCartItem(int $quantity, string $currency = 'TND'): CartItem
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
            'base_price' => 999,
            'currency' => $currency,
        ]);

        $cart = Cart::create([
            'session_id' => 'tiered-cart-' . uniqid(),
            'currency' => $currency,
            'expires_at' => now()->addHour(),
        ]);

        $hold = BookingHold::create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'session_id' => 'tiered-hold-' . uniqid(),
            'cart_id' => $cart->id,
            'quantity' => $quantity,
            'person_type_breakdown' => null,
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
            'person_type_breakdown' => null,
            'unit_price' => 0, // deliberately 0 — a linear path would yield 0
            'currency' => $currency,
        ]);

        $item->load(['listing', 'hold.slot']);

        return $item;
    }

    public function test_tiered_cart_item_subtotal_for_group_of_6(): void
    {
        $this->assertEqualsWithDelta(360, $this->createTieredCartItem(6)->getSubtotal(), 0.001);
    }

    public function test_tiered_cart_item_does_not_multiply_unit_price_linearly(): void
    {
        // unit_price = 0; linear path -> 0. Tiered engine must yield T[2] = 180.
        $this->assertEqualsWithDelta(180, $this->createTieredCartItem(2)->getSubtotal(), 0.001);
    }
}
