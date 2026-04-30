<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Enums\AvailabilityRuleType;
use App\Enums\ServiceType;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cart-level regression coverage for per-slot price overrides.
 *
 * Backstory: vendors configure per-slot price overrides on AvailabilityRule.time_slots[].
 * The hold-creation HTTP endpoint correctly snapshots the override price into
 * `BookingHold.price_snapshot` (covered by `AvailabilityRuleSlotPricingTest::test_hold_creation_locks_in_slot_override_via_http_endpoint`).
 * BUT the cart's displayed per-person breakdown and subtotal previously read
 * `listing.pricing.person_types` directly — ignoring the slot's overrides — so the
 * customer saw the listing default at the checkout summary. Real client tickets
 * showed the bug at TND 45 when the slot was overridden to TND 150.
 *
 * These tests pin the corrected behaviour: when a CartItem is tied to a slot
 * carrying `price_overrides`, both `getPersonTypePricing()` and `getSubtotal()`
 * return the slot-effective values. When the slot has no override (or the cart
 * item is for an accommodation with no slot), they fall back to listing pricing
 * — the regression guard.
 */
class CartItemSlotOverridePricingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GIVEN: a tour listing with adult=50/child=30 base prices and a 3-hour slot
     *        carrying an override (adult=120, child=30 fallback).
     * WHEN:  a CartItem is created against a hold for that overridden slot.
     * THEN:  CartItem::getPersonTypePricing() returns adult=120, child=30 (the
     *        slot-effective merge), not the listing's adult=50/child=30.
     *
     * Maps directly to the screenshotted bug: customer saw listing default at
     * checkout instead of the vendor-configured slot override.
     */
    public function test_get_person_type_pricing_uses_slot_effective_when_override_present(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'capacity' => 4,
                    'price_overrides' => [
                        'person_types' => [
                            ['key' => 'adult', 'tnd_price' => 120, 'eur_price' => 40],
                        ],
                    ],
                ],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->whereTime('end_time', '12:00:00')
            ->firstOrFail();

        $cartItem = $this->createCartItemForSlot($listing, $slot, ['adult' => 2, 'child' => 1], 'TND');

        $cartItem->load(['listing', 'hold.slot']);
        $pricing = $cartItem->getPersonTypePricing();

        $this->assertNotNull($pricing, 'Cart item with a slot override must expose pricing map.');
        $this->assertSame(120.0, (float) $pricing['adult'], 'Adult must use slot override (120 TND), not listing default (50 TND).');
        $this->assertSame(30.0, (float) $pricing['child'], 'Child must fall back to listing default (30 TND) since the slot has no child override.');
    }

    /**
     * GIVEN: the same setup as above.
     * WHEN:  CartItem::getSubtotal() is invoked.
     * THEN:  it returns 270.0 (= 2×120 adult + 1×30 child) — the slot-effective
     *        total, matching the hold's locked-in price_snapshot.
     *
     * Confirms the PriceCalculationService gets the slot threaded through so
     * the displayed cart subtotal mirrors what the customer is actually charged.
     */
    public function test_get_subtotal_includes_slot_override(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'capacity' => 4,
                    'price_overrides' => [
                        'person_types' => [
                            ['key' => 'adult', 'tnd_price' => 120, 'eur_price' => 40],
                        ],
                    ],
                ],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->whereTime('end_time', '12:00:00')
            ->firstOrFail();

        $cartItem = $this->createCartItemForSlot($listing, $slot, ['adult' => 2, 'child' => 1], 'TND');

        $cartItem->load(['listing', 'hold.slot']);

        $this->assertSame(
            270.0,
            (float) $cartItem->getSubtotal(),
            'Subtotal must be 2×120 + 1×30 = 270 TND (slot override applied), not 2×50 + 1×30 = 130 TND (listing only).',
        );
    }

    /**
     * GIVEN: a tour listing with no slot-level override on the chosen slot.
     * WHEN:  the same getters are invoked.
     * THEN:  pricing falls back to the listing's defaults — the regression
     *        guard for slots/listings that DON'T use overrides.
     *
     * Without this test, a future refactor could accidentally short-circuit the
     * fallback path and break every non-override booking.
     */
    public function test_falls_back_to_listing_pricing_when_slot_has_no_override(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '10:00:00', 'capacity' => 8],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->whereTime('end_time', '10:00:00')
            ->firstOrFail();

        $cartItem = $this->createCartItemForSlot($listing, $slot, ['adult' => 1, 'child' => 1], 'TND');
        $cartItem->load(['listing', 'hold.slot']);

        $pricing = $cartItem->getPersonTypePricing();
        $this->assertSame(50.0, (float) $pricing['adult'], 'No-override slot must return listing-default adult price (50 TND).');
        $this->assertSame(30.0, (float) $pricing['child'], 'No-override slot must return listing-default child price (30 TND).');

        $this->assertSame(
            80.0,
            (float) $cartItem->getSubtotal(),
            'No-override subtotal must be 1×50 + 1×30 = 80 TND (listing-only).',
        );
    }

    /**
     * Helper: build the minimum cart graph (Cart → CartItem → BookingHold → Slot)
     * the CartItem methods need. Mirrors what `CartService::addToCart` produces
     * but bypasses the HTTP/auth surface so the test stays focused on the
     * model-level pricing logic.
     */
    private function createCartItemForSlot(
        Listing $listing,
        AvailabilitySlot $slot,
        array $personTypeBreakdown,
        string $currency,
    ): CartItem {
        $cart = Cart::create([
            'session_id' => 'cart-slot-test-' . uniqid(),
            'currency' => $currency,
            'expires_at' => now()->addHour(),
        ]);

        $hold = BookingHold::create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'session_id' => 'hold-slot-test-' . uniqid(),
            'cart_id' => $cart->id,
            'quantity' => array_sum($personTypeBreakdown),
            'person_type_breakdown' => $personTypeBreakdown,
            'currency' => $currency,
            'price_snapshot' => 0, // value irrelevant for these tests; locked separately
            'expires_at' => now()->addMinutes(15),
            'status' => 'active',
        ]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'hold_id' => $hold->id,
            'listing_id' => $listing->id,
            'listing_title' => ['en' => $listing->slug, 'fr' => $listing->slug],
            'slot_start' => $slot->start_time,
            'slot_end' => $slot->end_time,
            'quantity' => $hold->quantity,
            'person_type_breakdown' => $personTypeBreakdown,
            'unit_price' => 0,
            'currency' => $currency,
        ]);
    }
}
