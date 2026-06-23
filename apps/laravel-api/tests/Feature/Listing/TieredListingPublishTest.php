<?php

declare(strict_types=1);

namespace Tests\Feature\Listing;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Publish-time validation for tiered listings: a tiered listing needs at least
 * one tier row with both TND and EUR totals. Flat listings keep their existing
 * person-type rule (regression).
 */
class TieredListingPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiered_listing_with_valid_tiers_can_publish(): void
    {
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'tiers' => [
                    ['position' => 1, 'tnd_total' => 100, 'eur_total' => 30],
                    ['position' => 2, 'tnd_total' => 180, 'eur_total' => 54],
                ],
            ],
        ]);

        $listing->status = ListingStatus::PUBLISHED;
        $listing->save();

        $this->assertSame(ListingStatus::PUBLISHED, $listing->fresh()->status);
    }

    public function test_tiered_listing_without_tiers_cannot_publish(): void
    {
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => ['currency' => 'TND', 'pricing_strategy' => 'tiered', 'tiers' => []],
        ]);

        $listing->status = ListingStatus::PUBLISHED;

        $this->expectException(ValidationException::class);
        $listing->save();
    }

    public function test_tiered_listing_with_missing_currency_total_cannot_publish(): void
    {
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'tiers' => [['position' => 1, 'tnd_total' => 100]], // no eur_total
            ],
        ]);

        $listing->status = ListingStatus::PUBLISHED;

        $this->expectException(ValidationException::class);
        $listing->save();
    }

    public function test_flat_listing_publish_rule_unchanged(): void
    {
        // Flat listing with person types still publishes (regression).
        $ok = Listing::factory()->draft()->dualPriced()->create(['service_type' => ServiceType::TOUR]);
        $ok->status = ListingStatus::PUBLISHED;
        $ok->save();
        $this->assertSame(ListingStatus::PUBLISHED, $ok->fresh()->status);

        // Flat listing with NO pricing still cannot publish (regression).
        $bad = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => ['currency' => 'TND'],
        ]);
        $bad->status = ListingStatus::PUBLISHED;
        $this->expectException(ValidationException::class);
        $bad->save();
    }

    public function test_listing_model_exposes_is_tiered_pricing(): void
    {
        $tiered = Listing::factory()->create([
            'pricing' => ['pricing_strategy' => 'tiered', 'tiers' => [['position' => 1, 'tnd_total' => 1, 'eur_total' => 1]]],
        ]);
        $flat = Listing::factory()->dualPriced()->create();

        $this->assertTrue($tiered->isTieredPricing());
        $this->assertFalse($flat->isTieredPricing());
    }
}
