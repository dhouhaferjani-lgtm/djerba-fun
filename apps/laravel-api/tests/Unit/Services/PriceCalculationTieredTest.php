<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ServiceType;
use App\Models\Listing;
use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for the OPTIONAL "tiered" (positional) pricing strategy.
 *
 * In tiered mode the vendor supplies CUMULATIVE group totals T[1..K]
 * (the full price for a group of 1, 2, 3 … up to K positions), entered
 * independently per currency. The total for a group of N travellers is:
 *
 *     total(N) = floor(N / K) * T[K] + T[N mod K]      (T[0] = 0)
 *
 * Canonical client fixture: TND tiers = [100, 180, 180] (K = 3, 3rd free).
 *   N=1 -> 100, N=2 -> 180, N=3 -> 180, N=6 -> 360, N=7 -> 460.
 *
 * These tests are the money guard for a LIVE payments system: flat listings
 * (no pricing_strategy key) MUST be unaffected.
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

    /**
     * Build a tour with tiered pricing. TND tiers = [100,180,180],
     * EUR tiers = [30,54,54] (deliberately different to prove the engine
     * reads the per-currency column, not a shared value).
     */
    private function createTieredListing(?array $tiers = null): Listing
    {
        $tiers ??= [
            ['position' => 1, 'tnd_total' => 100, 'eur_total' => 30],
            ['position' => 2, 'tnd_total' => 180, 'eur_total' => 54],
            ['position' => 3, 'tnd_total' => 180, 'eur_total' => 54],
        ];

        return Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'tiers' => $tiers,
            ],
        ]);
    }

    public function test_tiered_group_of_1_returns_first_tier(): void
    {
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 1, 'TND');

        $this->assertSame('TND', $result['currency']);
        $this->assertEqualsWithDelta(100, $result['total'], 0.001);
        $this->assertEqualsWithDelta(100, $result['subtotal'], 0.001);
        $this->assertEqualsWithDelta(0, $result['discount'], 0.001, 'Tiered mode has no separate discount');
    }

    public function test_tiered_group_of_2_returns_second_tier(): void
    {
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 2, 'TND');

        $this->assertEqualsWithDelta(180, $result['total'], 0.001);
    }

    public function test_tiered_group_of_3_returns_third_tier_with_free_traveller(): void
    {
        // T[3] == T[2] == 180 -> the 3rd traveller is effectively free.
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 3, 'TND');

        $this->assertEqualsWithDelta(180, $result['total'], 0.001);
    }

    public function test_tiered_group_of_6_repeats_the_pattern(): void
    {
        // floor(6/3)*T[3] + T[0] = 2*180 + 0 = 360
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 6, 'TND');

        $this->assertEqualsWithDelta(360, $result['total'], 0.001);
    }

    public function test_tiered_group_of_7_is_cycle_plus_remainder(): void
    {
        // floor(7/3)*T[3] + T[1] = 2*180 + 100 = 460
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 7, 'TND');

        $this->assertEqualsWithDelta(460, $result['total'], 0.001);
    }

    public function test_tiered_eur_uses_independent_totals(): void
    {
        // EUR tiers [30,54,54]: floor(7/3)*54 + 30 = 138
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 7, 'EUR');

        $this->assertSame('EUR', $result['currency']);
        $this->assertEqualsWithDelta(138, $result['total'], 0.001);
    }

    public function test_tiered_single_tier_is_pure_linear(): void
    {
        // K=1 -> total(N) = N * T[1]
        $listing = $this->createTieredListing([
            ['position' => 1, 'tnd_total' => 50, 'eur_total' => 20],
        ]);

        $result = $this->service->calculateTieredTotal($listing, 4, 'TND');

        $this->assertEqualsWithDelta(200, $result['total'], 0.001);
    }

    public function test_tiered_zero_quantity_returns_zero(): void
    {
        $result = $this->service->calculateTieredTotal($this->createTieredListing(), 0, 'TND');

        $this->assertEqualsWithDelta(0, $result['total'], 0.001);
    }

    public function test_tiered_empty_tiers_returns_zero_gracefully(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => ['currency' => 'TND', 'pricing_strategy' => 'tiered', 'tiers' => []],
        ]);

        $result = $this->service->calculateTieredTotal($listing, 3, 'TND');

        $this->assertEqualsWithDelta(0, $result['total'], 0.001);
    }

    public function test_tiered_rounds_to_two_decimals(): void
    {
        $listing = $this->createTieredListing([
            ['position' => 1, 'tnd_total' => 33.333, 'eur_total' => 10.005],
        ]);

        $result = $this->service->calculateTieredTotal($listing, 3, 'EUR');

        // 3 * 10.005 = 30.015 -> rounded to 30.02 (round half up)
        $this->assertEqualsWithDelta(30.02, $result['total'], 0.001);
    }

    public function test_calculate_simple_total_routes_tiered_listing_to_tiered_engine(): void
    {
        // The booking flow uses the guests/quantity path -> calculateSimpleTotal.
        $result = $this->service->calculateSimpleTotal($this->createTieredListing(), 6, 'TND');

        $this->assertEqualsWithDelta(360, $result['total'], 0.001);
    }

    public function test_calculate_total_collapses_breakdown_to_headcount_for_tiered(): void
    {
        // Even if a person-type breakdown is passed, tiered listings price by headcount.
        $result = $this->service->calculateTotal(
            $this->createTieredListing(),
            ['adult' => 4, 'child' => 2], // 6 travellers total
            'TND',
        );

        $this->assertEqualsWithDelta(360, $result['total'], 0.001);
        $this->assertSame(6, $result['totalGuests']);
        $this->assertArrayHasKey('breakdown', $result);
    }

    /**
     * REGRESSION GUARD: a flat listing (no pricing_strategy key) must be
     * completely unaffected by the tiered code path.
     */
    public function test_flat_listing_is_unaffected(): void
    {
        $flat = Listing::factory()->dualPriced()->create();

        $result = $this->service->calculateTotal($flat, ['adult' => 2], 'TND');

        $this->assertEqualsWithDelta(300, $result['total'], 0.001, '2 adults x 150 TND = 300 (unchanged)');
        $this->assertSame(2, $result['totalGuests']);

        $simple = $this->service->calculateSimpleTotal($flat, 3, 'TND');
        $this->assertEqualsWithDelta(450, $simple['total'], 0.001, '3 x 150 TND = 450 (unchanged)');
    }
}
