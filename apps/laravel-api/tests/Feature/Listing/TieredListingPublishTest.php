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
 * Publish-time validation for tiered listings. A tiered listing keeps NORMAL
 * per-person-type pricing as its base (required), plus OPTIONAL group-discount
 * tiers (sizes 2-5). Any tier that is set must carry BOTH TND and EUR totals.
 * Flat listings keep their existing person-type rule (regression).
 */
class TieredListingPublishTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> Pricing with person types + optional group tiers. */
    private function tieredPricing(array $tiers): array
    {
        return [
            'currency' => 'TND',
            'pricing_strategy' => 'tiered',
            'person_types' => [
                ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 40],
            ],
            'tiers' => $tiers,
        ];
    }

    public function test_tiered_listing_with_person_types_and_group_discounts_can_publish(): void
    {
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => $this->tieredPricing([
                ['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 72],
                ['group_size' => 5, 'tnd_total' => 200, 'eur_total' => 160],
            ]),
        ]);

        $listing->status = ListingStatus::PUBLISHED;
        $listing->save();

        $this->assertSame(ListingStatus::PUBLISHED, $listing->fresh()->status);
    }

    public function test_tiered_listing_with_person_types_but_no_group_discounts_can_publish(): void
    {
        // Group discounts are OPTIONAL — a tiered listing with only person types publishes.
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => $this->tieredPricing([]),
        ]);

        $listing->status = ListingStatus::PUBLISHED;
        $listing->save();

        $this->assertSame(ListingStatus::PUBLISHED, $listing->fresh()->status);
    }

    public function test_tiered_listing_without_person_types_cannot_publish(): void
    {
        // Tiered keeps per-person pricing as its base; without it there is no price.
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'currency' => 'TND',
                'pricing_strategy' => 'tiered',
                'tiers' => [['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 72]],
            ],
        ]);

        $listing->status = ListingStatus::PUBLISHED;

        $this->expectException(ValidationException::class);
        $listing->save();
    }

    public function test_group_discount_with_missing_currency_total_cannot_publish(): void
    {
        $listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => $this->tieredPricing([
                ['group_size' => 2, 'tnd_total' => 90], // no eur_total
            ]),
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
            'pricing' => $this->tieredPricing([['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 72]]),
        ]);
        $flat = Listing::factory()->dualPriced()->create();

        $this->assertTrue($tiered->isTieredPricing());
        $this->assertFalse($flat->isTieredPricing());
    }
}
