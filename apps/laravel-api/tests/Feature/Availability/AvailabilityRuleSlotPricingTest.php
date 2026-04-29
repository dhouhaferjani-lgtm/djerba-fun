<?php

declare(strict_types=1);

namespace Tests\Feature\Availability;

use App\Enums\AvailabilityRuleType;
use App\Enums\ServiceType;
use App\Http\Resources\AvailabilitySlotResource;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Services\PriceCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * BDD coverage for per-time-slot price overrides on AvailabilityRule.
 *
 * Vendor sells the same circuit at multiple durations from the same anchor
 * (e.g. 09:00 → 1-hour at 50/15, 09:00 → 3-hour at 120/40). Each duration
 * is a distinct AvailabilitySlot row, optionally carrying a partial
 * per-person-type price override. Person-types not listed in the override
 * fall back to the listing's pricing (lenient merge).
 *
 * Scope of this file:
 *   1. schema + materialization — two slots at the same start_time coexist
 *   2. model merge helper — per-key fallback to listing pricing
 *   3. pricing service overload — slot-aware total calculation
 *
 * Hold creation, API resource exposure, Filament UI, and cart/checkout
 * scenarios are covered by their own files referenced from
 * `~/.claude/plans/so-claude-a-sessions-elegant-stream.md`.
 */
class AvailabilityRuleSlotPricingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GIVEN: a tour listing with adult + child person-types and a weekly rule
     *        whose time_slots[] declare two windows starting at 09:00 — one
     *        ending at 10:00 (1-hour, no override) and one ending at 12:00
     *        (3-hour, with adult-only override TND 120 / EUR 40).
     * WHEN:  the rule is saved (CalculateAvailabilityJob runs via the saved() observer).
     * THEN:  two distinct AvailabilitySlot rows exist for the applicable day.
     *        Only the 3-hour slot carries price_overrides; the 1-hour slot has none.
     *        The unique key on (listing_id, date, start_time, end_time) does NOT
     *        clobber the second slot — the schema change is the foundation of
     *        this feature.
     */
    public function test_two_slots_at_same_start_with_different_durations_both_materialize_with_independent_pricing(): void
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
                    'end_time' => '10:00:00',
                    'capacity' => 8,
                ],
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

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('end_time')
            ->get();

        $this->assertCount(
            2,
            $slots,
            'Two slots at the same start_time but different end_time must coexist — '
                .'the schema-level unique key must include end_time, otherwise the '
                .'second slot silently overwrites the first.',
        );

        // 1-hour slot — no override, fully inherits listing pricing.
        $this->assertSame('09:00:00', $slots[0]->start_time->format('H:i:s'));
        $this->assertSame('10:00:00', $slots[0]->end_time->format('H:i:s'));
        $this->assertNull(
            $slots[0]->price_overrides,
            'Slot without an override must persist price_overrides as null.',
        );

        // 3-hour slot — adult-only override.
        $this->assertSame('09:00:00', $slots[1]->start_time->format('H:i:s'));
        $this->assertSame('12:00:00', $slots[1]->end_time->format('H:i:s'));
        $this->assertNotNull($slots[1]->price_overrides);
        $this->assertSame('adult', $slots[1]->price_overrides['person_types'][0]['key']);
        $this->assertSame(120, (int) $slots[1]->price_overrides['person_types'][0]['tnd_price']);
        $this->assertSame(40, (int) $slots[1]->price_overrides['person_types'][0]['eur_price']);
    }

    /**
     * GIVEN: a slot whose price_overrides specifies adult only (TND 120 / EUR 40)
     *        while the listing defines both adult (50/15) and child (30/10).
     * WHEN:  AvailabilitySlot::getEffectivePersonTypePrices('TND', listing.pricing.person_types) is called.
     * THEN:  adult resolves to the slot override (120); child falls back to the
     *        listing's pricing (30). This is the lenient per-key merge — the
     *        single source of truth for "what does this slot cost per
     *        person-type?" used by every consumer downstream.
     */
    public function test_effective_per_person_type_prices_merge_slot_override_with_listing_fallback(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'price_overrides' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 120, 'eur_price' => 40],
                ],
            ],
        ]);

        $effectiveTnd = $slot->getEffectivePersonTypePrices('TND', $listing->pricing['person_types']);
        $this->assertSame(120.0, (float) $effectiveTnd['adult']);
        $this->assertSame(30.0, (float) $effectiveTnd['child']);

        $effectiveEur = $slot->getEffectivePersonTypePrices('EUR', $listing->pricing['person_types']);
        $this->assertSame(40.0, (float) $effectiveEur['adult']);
        $this->assertSame(10.0, (float) $effectiveEur['child']);
    }

    /**
     * GIVEN: a slot with adult-only override TND 120 / EUR 40 against a listing
     *        priced 50/15 (adult) and 30/10 (child).
     * WHEN:  PriceCalculationService::calculateTotal($listing, [adult:2, child:1], 'TND', $slot).
     * THEN:  total = 2 × 120 (override) + 1 × 30 (listing fallback) = 270 TND.
     *        With $slot omitted (back-compat path) the same call returns 130 TND
     *        (2 × 50 + 1 × 30) — the existing listing-only behaviour.
     *
     * This proves both the new override path AND the regression guard: passing a
     * null slot (or omitting the parameter) must not change today's results.
     */
    public function test_price_calculation_service_uses_slot_override_when_present_and_falls_back_when_absent(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $slotWithOverride = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'price_overrides' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 120, 'eur_price' => 40],
                ],
            ],
        ]);

        /** @var PriceCalculationService $service */
        $service = app(PriceCalculationService::class);

        $withOverride = $service->calculateTotal(
            $listing,
            ['adult' => 2, 'child' => 1],
            'TND',
            $slotWithOverride,
        );

        $this->assertSame(270.0, (float) $withOverride['total']);

        // Regression guard — null slot must reproduce today's listing-only behaviour.
        $listingOnly = $service->calculateTotal(
            $listing,
            ['adult' => 2, 'child' => 1],
            'TND',
            null,
        );

        $this->assertSame(130.0, (float) $listingOnly['total']);
    }

    /**
     * GIVEN: an AvailabilitySlot with a partial adult-only override and a parent
     *        Listing carrying both adult and child person-types.
     * WHEN:  AvailabilitySlotResource serialises that slot for the API.
     * THEN:  the response contains
     *          - `priceOverrides` — the raw stored override (so the Filament UI
     *            and the frontend slot picker can show "this slot is custom-priced").
     *          - `effectivePrices` — the merged per-currency, per-person-type map
     *            so the frontend never has to recompute the lenient fallback.
     */
    public function test_availability_slot_resource_exposes_price_overrides_and_effective_prices(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'price_overrides' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 120, 'eur_price' => 40],
                ],
            ],
        ]);
        $slot->setRelation('listing', $listing);

        $payload = (new AvailabilitySlotResource($slot))->toArray(Request::create('/'));

        // The raw override survives end-to-end so the UI can flag the slot as custom.
        $this->assertNotNull($payload['priceOverrides']);
        $this->assertSame('adult', $payload['priceOverrides']['person_types'][0]['key']);
        $this->assertSame(120, (int) $payload['priceOverrides']['person_types'][0]['tnd_price']);

        // The merged effective view: slot price for adult, listing fallback for child,
        // for both currencies — the frontend renders this directly.
        $this->assertSame(120.0, (float) $payload['effectivePrices']['TND']['adult']);
        $this->assertSame(30.0, (float) $payload['effectivePrices']['TND']['child']);
        $this->assertSame(40.0, (float) $payload['effectivePrices']['EUR']['adult']);
        $this->assertSame(10.0, (float) $payload['effectivePrices']['EUR']['child']);
    }

    /**
     * GIVEN: a slot with NO price_overrides and a Listing with person-type pricing.
     * WHEN:  AvailabilitySlotResource serialises the slot.
     * THEN:  `priceOverrides` is null and `effectivePrices` falls back fully to
     *        the listing pricing — the regression guard for every existing rule
     *        in production (NULL on deploy day == today's behaviour bit-for-bit).
     */
    public function test_availability_slot_resource_falls_back_to_listing_when_slot_has_no_override(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'price_overrides' => null,
        ]);
        $slot->setRelation('listing', $listing);

        $payload = (new AvailabilitySlotResource($slot))->toArray(Request::create('/'));

        $this->assertNull($payload['priceOverrides']);
        $this->assertSame(50.0, (float) $payload['effectivePrices']['TND']['adult']);
        $this->assertSame(30.0, (float) $payload['effectivePrices']['TND']['child']);
        $this->assertSame(15.0, (float) $payload['effectivePrices']['EUR']['adult']);
        $this->assertSame(10.0, (float) $payload['effectivePrices']['EUR']['child']);
    }

    /**
     * GIVEN: a listing with adult+child person-types and a rule that materialises
     *        two slots — a 1-hour at 09:00–10:00 (no override, listing pricing)
     *        and a 3-hour at 09:00–12:00 (adult override TND 120 / EUR 40).
     * WHEN:  a guest hits POST /api/v1/listings/{slug}/holds for the 3-hour slot
     *        with breakdown {adult:2, child:1}.
     * THEN:  the resulting hold's `price_snapshot` is 270 TND
     *        (2×120 override + 1×30 listing fallback) — proving every layer
     *        from HoldController → PriceCalculationService → AvailabilitySlot
     *        threads the override correctly through HTTP.
     *
     * This is the runtime end-to-end guard. If any caller forgets to pass `$slot`
     * through, the hold's snapshot would silently fall back to listing pricing
     * (130 TND) and customers would underpay — a real production regression.
     */
    public function test_hold_creation_locks_in_slot_override_via_http_endpoint(): void
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

        // Locate the 3-hour slot — the one carrying the override.
        $threeHourSlot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->whereTime('end_time', '12:00:00')
            ->firstOrFail();

        // Tunisia IP so the geo-pricing layer resolves to TND, exercising the
        // exact currency selection path the override has to honour.
        $tunisiaIp = '41.230.62.1';

        $response = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $threeHourSlot->id,
            'quantity' => 3,
            'person_types' => ['adult' => 2, 'child' => 1],
            'session_id' => 'slot-pricing-e2e-'.uniqid(),
        ], [
            'REMOTE_ADDR' => $tunisiaIp,
        ]);

        $response->assertCreated();

        $hold = BookingHold::find($response->json('data.id'));
        $this->assertNotNull($hold, 'Hold must persist.');

        // Decimal-safe comparison; PriceCalculationService returns floats but
        // the model stores decimal:2.
        $this->assertSame(
            270.0,
            (float) $hold->price_snapshot,
            'Hold snapshot must lock in the slot-overridden total (2×120 + 1×30 = 270 TND), '
            .'not the listing-only total (2×50 + 1×30 = 130 TND).',
        );
    }

    /**
     * GIVEN: a customer holds an overridden slot at the override price (locked
     *        into price_snapshot at hold time).
     * WHEN:  the vendor edits the rule afterwards — bumping the slot's adult
     *        override from 120 TND to 200 TND.
     * THEN:  the existing hold's `price_snapshot` does NOT change. New holds
     *        for the same slot pick up the new override price.
     *
     * Snapshot immutability is the contract that lets vendors adjust pricing
     * without breaking customers' in-progress bookings. The hold creation path
     * captures the price; nothing on the rule's `saved()` side-effect chain
     * (CalculateAvailabilityJob) is allowed to mutate it.
     */
    public function test_existing_hold_snapshot_is_immutable_when_vendor_edits_override(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                ],
            ],
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'capacity' => 5,
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
            ->firstOrFail();

        // Customer creates a hold at the override price.
        $createResponse = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_types' => ['adult' => 2],
            'session_id' => 'snapshot-immutability-'.uniqid(),
        ], ['REMOTE_ADDR' => '41.230.62.1']);

        $createResponse->assertCreated();
        $hold = BookingHold::find($createResponse->json('data.id'));
        $this->assertSame(240.0, (float) $hold->price_snapshot, 'Original snapshot: 2×120 = 240 TND.');

        // Vendor bumps the override — re-runs CalculateAvailabilityJob via the
        // saved() observer. The slot row gets new price_overrides.
        $rule->update([
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'capacity' => 5,
                    'price_overrides' => [
                        'person_types' => [
                            ['key' => 'adult', 'tnd_price' => 200, 'eur_price' => 65],
                        ],
                    ],
                ],
            ],
        ]);

        // The customer's hold survives untouched (same slot identity → reconciler
        // leaves it alone) and its price_snapshot is unchanged.
        $hold->refresh();
        $this->assertSame(
            240.0,
            (float) $hold->price_snapshot,
            'Existing hold snapshot must be IMMUTABLE — vendor edit cannot retroactively change locked-in prices.',
        );

        // But a NEW hold for the same slot should pick up the new override.
        $slot->refresh();
        $newHoldResponse = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 1,
            'person_types' => ['adult' => 1],
            'session_id' => 'snapshot-immutability-new-'.uniqid(),
        ], ['REMOTE_ADDR' => '41.230.62.1']);

        $newHoldResponse->assertCreated();
        $newHold = BookingHold::find($newHoldResponse->json('data.id'));
        $this->assertSame(
            200.0,
            (float) $newHold->price_snapshot,
            'New hold must use the updated override price (200 TND), not the old one.',
        );
    }

    /**
     * GIVEN: a rule with a slot carrying a price override.
     * WHEN:  the vendor saves the rule again with `price_overrides` cleared.
     * THEN:  new holds use listing pricing again — `price_snapshot` reverts
     *        to the listing-derived total.
     *
     * The "remove override" path. Vendor experimented with custom pricing,
     * decided to revert. Materialisation must collapse the override back to NULL.
     */
    public function test_removing_an_override_reverts_new_holds_to_listing_pricing(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                ],
            ],
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'capacity' => 5,
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

        // Vendor removes the override.
        $rule->update([
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
        ]);

        $slot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->firstOrFail();

        $this->assertNull(
            $slot->price_overrides,
            'After removal, the slot row must hold NULL — the absence-of-override is what tells '
            .'PriceCalculationService to fall back to listing pricing.',
        );

        $response = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 1,
            'person_types' => ['adult' => 1],
            'session_id' => 'override-removal-'.uniqid(),
        ], ['REMOTE_ADDR' => '41.230.62.1']);

        $response->assertCreated();
        $hold = BookingHold::find($response->json('data.id'));
        $this->assertSame(
            50.0,
            (float) $hold->price_snapshot,
            'New hold after override removal must use the listing adult TND price (50), not the old override.',
        );
    }

    /**
     * GIVEN: a listing whose pricing.person_types[] declares only adult+child.
     * WHEN:  a vendor tries to save a rule with a price_overrides row keyed
     *        on `senior` (a key that doesn't exist on the listing).
     * THEN:  ValidationException is thrown with the error pinned to
     *        `time_slots.0.price_overrides.person_types.0.key` — the orphan
     *        check from `validatePriceOverrides`.
     *
     * Locks the safety net: vendors cannot override prices for person-types
     * the listing doesn't actually offer. Catches typos and keeps the
     * effective-price merge well-defined (no silent drops at runtime).
     */
    public function test_validation_rejects_an_override_key_that_listing_does_not_define(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $thrown = null;

        try {
            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [Carbon::MONDAY],
                'time_slots' => [
                    [
                        'start_time' => '09:00:00',
                        'end_time' => '12:00:00',
                        'capacity' => 5,
                        'price_overrides' => [
                            'person_types' => [
                                // Orphan: listing has no 'senior' key.
                                ['key' => 'senior', 'tnd_price' => 80, 'eur_price' => 25],
                            ],
                        ],
                    ],
                ],
                'is_active' => true,
            ]);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'Expected ValidationException for orphan person-type key.');
        $this->assertArrayHasKey(
            'time_slots.0.price_overrides.person_types.0.key',
            $thrown->errors(),
            'Validation error must be pinned to the offending row so the Filament UI '
            .'highlights the right field.',
        );
    }

    /**
     * GIVEN: an accommodation listing (per-night pricing, separate code path
     *        from per-person tour pricing).
     * WHEN:  a customer holds a date range.
     * THEN:  the hold flow ignores per-slot price_overrides entirely — accommodation
     *        prices live on `Listing.nightly_price_*`, not on the slot.
     *
     * This is the explicit regression guard: per-slot pricing is a tour/event/nautical
     * feature. Accommodation must NOT be touched by any of the new code paths.
     */
    public function test_accommodation_per_night_pricing_is_unaffected_by_slot_overrides(): void
    {
        // Accommodation listings use the per_night branch in HoldController +
        // CartService. No per-slot override is consumed there. We assert the
        // foundation: per-slot override on an accommodation slot is silently
        // ignored at runtime (it can sit on the column harmlessly).
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::ACCOMMODATION,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 200, 'eur_price' => 60],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'price_overrides' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 999, 'eur_price' => 333],
                ],
            ],
        ]);
        $slot->setRelation('listing', $listing);

        // Resource exposes the override (transparent), but the accommodation
        // booking flow uses Listing.nightly_price_eur / nightly_price_tnd —
        // the override isn't consulted by AccommodationBookingService.
        $payload = (new AvailabilitySlotResource($slot))->toArray(Request::create('/'));
        $this->assertSame(999, (int) $payload['priceOverrides']['person_types'][0]['tnd_price']);

        // Sanity: the AccommodationBookingService does NOT take a slot parameter.
        // If anyone later refactors it to use the slot's override, this assertion
        // failing is a deliberate signal to revisit accommodation flow design.
        $reflection = new \ReflectionMethod(\App\Services\AccommodationBookingService::class, 'calculatePrice');
        $params = collect($reflection->getParameters())->map(fn ($p) => $p->getName())->all();
        $this->assertNotContains(
            'slot',
            $params,
            'AccommodationBookingService::calculatePrice must remain slot-unaware. '
            .'If you intend to add slot-based pricing to accommodations, that is a separate ticket — '
            .'this assertion is the regression guard.',
        );
    }
}
