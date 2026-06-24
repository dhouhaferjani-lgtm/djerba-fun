<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ServiceType;
use App\Models\Listing;
use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tiered = OPTIONAL group-discount totals for group sizes 2..5, layered on top
 * of the listing's NORMAL per-person-type pricing.
 *
 * Rules:
 *  - size 1, any size with no discount set, and groups of 6+ -> NORMAL pricing
 *  - a size 2..5 with a tier total set -> that flat total (overrides the per-type sum)
 *
 * Fixture: adult=50, child=30 (TND & EUR equal). Group discounts: size 2 -> 90, size 5 -> 200.
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

    private function tiered(): Listing
    {
        return Listing::factory()->create([
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
    }

    public function test_size_1_uses_normal_pricing(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 1], 'TND');
        $this->assertEqualsWithDelta(50, $r['total'], 0.001);
    }

    public function test_size_2_uses_group_discount(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 2], 'TND');
        $this->assertEqualsWithDelta(90, $r['total'], 0.001, '2 travellers -> group-of-2 total 90 (not 100)');
    }

    public function test_size_2_group_total_applies_regardless_of_mix(): void
    {
        // 1 adult + 1 child = 2 travellers -> the group-of-2 total applies.
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 1, 'child' => 1], 'TND');
        $this->assertEqualsWithDelta(90, $r['total'], 0.001);
    }

    public function test_size_3_unset_uses_normal_pricing(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 3], 'TND');
        $this->assertEqualsWithDelta(150, $r['total'], 0.001, '3 x 50 normal (no size-3 discount)');
    }

    public function test_size_4_unset_uses_normal_pricing(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 4], 'TND');
        $this->assertEqualsWithDelta(200, $r['total'], 0.001, '4 x 50 normal (no size-4 discount)');
    }

    public function test_size_5_uses_group_discount(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 5], 'TND');
        $this->assertEqualsWithDelta(200, $r['total'], 0.001, 'group-of-5 total 200 (not 250)');
    }

    public function test_size_6_uses_normal_pricing(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 6], 'TND');
        $this->assertEqualsWithDelta(300, $r['total'], 0.001, '6 x 50 normal (groups > 5 never discounted)');
    }

    public function test_eur_uses_independent_group_totals(): void
    {
        $r = $this->service->calculateTotal($this->tiered(), ['adult' => 2], 'EUR');
        $this->assertSame('EUR', $r['currency']);
        $this->assertEqualsWithDelta(90, $r['total'], 0.001);
    }

    public function test_simple_total_applies_group_discount(): void
    {
        $this->assertEqualsWithDelta(90, $this->service->calculateSimpleTotal($this->tiered(), 2, 'TND')['total'], 0.001);
        $this->assertEqualsWithDelta(150, $this->service->calculateSimpleTotal($this->tiered(), 3, 'TND')['total'], 0.001);
    }

    public function test_tiered_listing_with_no_group_discounts_is_pure_normal(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'person_types' => [['key' => 'adult', 'label' => ['en' => 'Adult'], 'tnd_price' => 50, 'eur_price' => 50]],
                'tiers' => [],
            ],
        ]);
        $this->assertEqualsWithDelta(100, $this->service->calculateTotal($listing, ['adult' => 2], 'TND')['total'], 0.001);
    }

    public function test_flat_listing_is_unaffected(): void
    {
        $flat = Listing::factory()->dualPriced()->create();
        $this->assertEqualsWithDelta(300, $this->service->calculateTotal($flat, ['adult' => 2], 'TND')['total'], 0.001);
        $this->assertEqualsWithDelta(450, $this->service->calculateSimpleTotal($flat, 3, 'TND')['total'], 0.001);
    }
}
