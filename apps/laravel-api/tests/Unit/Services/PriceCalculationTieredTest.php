<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ServiceType;
use App\Models\Listing;
use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tiered = OPTIONAL group-discount totals for group sizes 2..5, priced by GREEDY
 * "circle" packing on top of the listing's normal per-person-type pricing.
 *
 * Rule for a headcount H:
 *  - no group tiers, or H smaller than the smallest configured group -> NORMAL per-person pricing
 *  - otherwise repeatedly apply the LARGEST configured group price that fits, let the
 *    remainder cycle back through the group prices, and charge any final leftover
 *    (< smallest configured group) as individuals at the base per-person rate.
 *
 * Fixture: adult tnd=50/eur=40, child tnd=30/eur=24.
 * Group discounts: size 2 -> tnd 90 / eur 72, size 3 -> tnd 130 / eur 104.
 *
 * So (TND): 1->50, 2->90, 3->130, 4->130+50=180, 5->130+90=220, 6->130+130=260, 7->310.
 */
class PriceCalculationTieredTest extends TestCase
{
    use RefreshDatabase;

    protected PriceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PriceCalculationService::class);
    }

    private function tiered(?array $tiers = null): Listing
    {
        return Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 40, 'minAge' => 18],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 24, 'minAge' => 2, 'maxAge' => 17],
                ],
                'tiers' => $tiers ?? [
                    ['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 72],
                    ['group_size' => 3, 'tnd_total' => 130, 'eur_total' => 104],
                ],
            ],
        ]);
    }

    private function total(Listing $listing, array $breakdown, string $currency = 'TND'): float
    {
        return $this->service->calculateTotal($listing, $breakdown, $currency)['total'];
    }

    public function test_single_adult_uses_individual_price(): void
    {
        $this->assertEqualsWithDelta(50, $this->total($this->tiered(), ['adult' => 1]), 0.001);
    }

    public function test_single_child_keeps_its_own_per_type_price(): void
    {
        // Below the smallest group size -> normal per-person-type pricing (not the adult rate).
        $this->assertEqualsWithDelta(30, $this->total($this->tiered(), ['child' => 1]), 0.001);
    }

    public function test_size_2_uses_group_of_2(): void
    {
        $this->assertEqualsWithDelta(90, $this->total($this->tiered(), ['adult' => 2]), 0.001);
    }

    public function test_size_3_uses_group_of_3(): void
    {
        $this->assertEqualsWithDelta(130, $this->total($this->tiered(), ['adult' => 3]), 0.001);
    }

    public function test_size_4_packs_group_of_3_plus_one_individual(): void
    {
        $this->assertEqualsWithDelta(180, $this->total($this->tiered(), ['adult' => 4]), 0.001, '130 + 1x50');
    }

    public function test_size_5_packs_group_of_3_plus_group_of_2(): void
    {
        // The "circle": the leftover of 2 uses the group-of-2 price, not 2 individuals.
        $this->assertEqualsWithDelta(220, $this->total($this->tiered(), ['adult' => 5]), 0.001, '130 + 90');
    }

    public function test_size_6_packs_two_groups_of_3(): void
    {
        $this->assertEqualsWithDelta(260, $this->total($this->tiered(), ['adult' => 6]), 0.001, '130 + 130');
    }

    public function test_size_7_packs_two_groups_of_3_plus_one_individual(): void
    {
        $this->assertEqualsWithDelta(310, $this->total($this->tiered(), ['adult' => 7]), 0.001, '130 + 130 + 50');
    }

    public function test_packing_ignores_person_type_mix(): void
    {
        // 2 adults + 2 children = 4 travellers -> group-of-3 + 1 individual (base/adult rate).
        $this->assertEqualsWithDelta(180, $this->total($this->tiered(), ['adult' => 2, 'child' => 2]), 0.001);
    }

    public function test_eur_uses_independent_group_and_individual_rates(): void
    {
        // EUR: group-3 104 + 1 individual 40 = 144.
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 4], 'EUR');
        $this->assertSame('EUR', $r['currency']);
        $this->assertEqualsWithDelta(144, $r['total'], 0.001);
        // EUR size 5: 104 + 72 = 176.
        $this->assertEqualsWithDelta(176, $this->total($this->tiered(), ['adult' => 5], 'EUR'), 0.001);
    }

    public function test_gap_only_group_of_3_configured(): void
    {
        $listing = $this->tiered([
            ['group_size' => 3, 'tnd_total' => 130, 'eur_total' => 104],
        ]);
        // H=2 is below the smallest group (3) -> normal per-person (2x50).
        $this->assertEqualsWithDelta(100, $this->total($listing, ['adult' => 2]), 0.001);
        $this->assertEqualsWithDelta(130, $this->total($listing, ['adult' => 3]), 0.001);
        $this->assertEqualsWithDelta(180, $this->total($listing, ['adult' => 4]), 0.001, '130 + 50');
        // H=5: group-3 then leftover 2 < smallest group -> 2 individuals.
        $this->assertEqualsWithDelta(230, $this->total($listing, ['adult' => 5]), 0.001, '130 + 2x50');
    }

    public function test_simple_total_applies_packing(): void
    {
        $this->assertEqualsWithDelta(180, $this->service->calculateSimpleTotal($this->tiered(), 4, 'TND')['total'], 0.001);
        $this->assertEqualsWithDelta(220, $this->service->calculateSimpleTotal($this->tiered(), 5, 'TND')['total'], 0.001);
    }

    public function test_group_price_is_applied_verbatim_even_when_not_a_discount(): void
    {
        // Product decision: the engine trusts the vendor's group price and does
        // NOT cap it at the normal per-person total. A group price set higher
        // than size x per-person is applied as-is (vendor's responsibility).
        // This test locks that behaviour so a later "cap at normal" change can't
        // silently break the flat-group-price intent.
        $listing = $this->tiered([
            ['group_size' => 2, 'tnd_total' => 120, 'eur_total' => 120], // 120 > 2 x 50
        ]);
        $this->assertEqualsWithDelta(120, $this->total($listing, ['adult' => 2]), 0.001, 'group price applied even though 120 > 100 normal');
    }

    public function test_tiered_listing_with_no_group_discounts_is_pure_normal(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'person_types' => [['key' => 'adult', 'label' => ['en' => 'Adult'], 'tnd_price' => 50, 'eur_price' => 40]],
                'tiers' => [],
            ],
        ]);
        $this->assertEqualsWithDelta(200, $this->total($listing, ['adult' => 4]), 0.001, '4x50 normal');
    }

    public function test_flat_listing_is_unaffected(): void
    {
        $flat = Listing::factory()->dualPriced()->create();
        $this->assertEqualsWithDelta(600, $this->total($flat, ['adult' => 4]), 0.001, '4x150 flat');
        $this->assertEqualsWithDelta(750, $this->service->calculateSimpleTotal($flat, 5, 'TND')['total'], 0.001);
    }
}
