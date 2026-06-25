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
 * The listing API must expose pricingStrategy + the optional group-discount tier
 * table (sizes 2-5). A tiered listing keeps its NORMAL per-person-type "from"
 * headline price — group discounts are an overlay, not a replacement. Flat
 * listings are unaffected.
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
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 40, 'minAge' => 18],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 24, 'minAge' => 2, 'maxAge' => 17],
                ],
                'tiers' => [
                    ['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 72],
                    ['group_size' => 5, 'tnd_total' => 200, 'eur_total' => 160],
                ],
            ],
        ]);
    }

    public function test_tiered_listing_exposes_strategy_and_group_tiers(): void
    {
        $listing = $this->tieredListing();

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", ['X-User-Currency' => 'EUR']);
        $response->assertOk();

        $pricing = $response->json('data.pricing');

        $this->assertSame('tiered', $pricing['pricingStrategy']);
        $this->assertCount(2, $pricing['tiers']);

        $this->assertEqualsWithDelta(2, $pricing['tiers'][0]['groupSize'], 0.001);
        $this->assertEqualsWithDelta(90, $pricing['tiers'][0]['tndTotal'], 0.001);
        $this->assertEqualsWithDelta(72, $pricing['tiers'][0]['eurTotal'], 0.001);
        // displayTotal follows the detected currency (EUR).
        $this->assertEqualsWithDelta(72, $pricing['tiers'][0]['displayTotal'], 0.001);

        $this->assertEqualsWithDelta(5, $pricing['tiers'][1]['groupSize'], 0.001);

        // "From" headline price stays the NORMAL adult price in the detected
        // currency (EUR -> 40) — group discounts do not change it.
        $this->assertSame('EUR', $pricing['displayCurrency']);
        $this->assertEqualsWithDelta(40, $pricing['displayPrice'], 0.001);
    }

    public function test_tiered_from_price_follows_tnd_currency(): void
    {
        $listing = $this->tieredListing();

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", ['X-User-Currency' => 'TND']);
        $response->assertOk();

        $pricing = $response->json('data.pricing');
        // Normal adult TND price = 50.
        $this->assertEqualsWithDelta(50, $pricing['displayPrice'], 0.001);
        $this->assertEqualsWithDelta(90, $pricing['tiers'][0]['displayTotal'], 0.001);
    }

    public function test_tiered_listing_without_group_discounts_reports_null_tiers(): void
    {
        $listing = Listing::factory()->create([
            'status' => ListingStatus::PUBLISHED,
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult'], 'tnd_price' => 50, 'eur_price' => 40],
                ],
                'tiers' => [],
            ],
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", ['X-User-Currency' => 'TND']);
        $response->assertOk();

        $pricing = $response->json('data.pricing');
        $this->assertSame('tiered', $pricing['pricingStrategy']);
        $this->assertNull($pricing['tiers']);
        $this->assertEqualsWithDelta(50, $pricing['displayPrice'], 0.001);
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
