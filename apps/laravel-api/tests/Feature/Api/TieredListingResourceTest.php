<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The listing API must expose pricingStrategy + the tier table, and present
 * a "from" headline price of T[1] for tiered listings — without changing flat
 * listings.
 */
final class TieredListingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function tieredListing(): Listing
    {
        return Listing::factory()->create([
            'status' => ListingStatus::PUBLISHED,
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
    }

    public function test_tiered_listing_exposes_strategy_and_tiers_with_eur_from_price(): void
    {
        $listing = $this->tieredListing();

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", ['X-User-Currency' => 'EUR']);
        $response->assertOk();

        $pricing = $response->json('data.pricing');

        $this->assertSame('tiered', $pricing['pricingStrategy']);
        $this->assertCount(3, $pricing['tiers']);
        $this->assertEqualsWithDelta(1, $pricing['tiers'][0]['position'], 0.001);
        $this->assertEqualsWithDelta(100, $pricing['tiers'][0]['tndTotal'], 0.001);
        $this->assertEqualsWithDelta(30, $pricing['tiers'][0]['eurTotal'], 0.001);

        // "From" headline price = T[1] in the detected currency (EUR -> 30).
        $this->assertSame('EUR', $pricing['displayCurrency']);
        $this->assertEqualsWithDelta(30, $pricing['displayPrice'], 0.001);
    }

    public function test_tiered_from_price_follows_tnd_currency(): void
    {
        $listing = $this->tieredListing();

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", ['X-User-Currency' => 'TND']);
        $response->assertOk();

        $pricing = $response->json('data.pricing');
        $this->assertEqualsWithDelta(100, $pricing['displayPrice'], 0.001);
    }

    public function test_flat_listing_reports_flat_strategy(): void
    {
        $flat = Listing::factory()->dualPriced()->create([
            'status' => ListingStatus::PUBLISHED,
            'service_type' => ServiceType::TOUR,
        ]);

        $response = $this->getJson("/api/v1/listings/{$flat->slug}", ['X-User-Currency' => 'TND']);
        $response->assertOk();

        $pricing = $response->json('data.pricing');
        $this->assertSame('flat', $pricing['pricingStrategy']);
        $this->assertNull($pricing['tiers']);
        // Flat headline unchanged: adult TND price = 150.
        $this->assertEqualsWithDelta(150, $pricing['displayPrice'], 0.001);
    }
}
