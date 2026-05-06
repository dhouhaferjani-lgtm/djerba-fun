<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class ListingResourcePricingUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ListingController::show caches the formatted resource per
        // (currency, listing id). Tests reuse id=1 across DatabaseRefresh,
        // so flush the cache between tests to avoid contamination.
        Cache::flush();
    }

    public function test_pricing_unit_label_is_exposed_when_set(): void
    {
        $listing = Listing::factory()->create([
            'status' => \App\Enums\ListingStatus::PUBLISHED,
            'pricing' => [
                'pricing_model' => 'per_person',
                'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['fr' => 'Adulte', 'en' => 'Adult'],
                        'tnd_price' => 105,
                        'eur_price' => 35,
                    ],
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.pricing.unitLabel.fr', 'par jetski')
            ->assertJsonPath('data.pricing.unitLabel.en', 'per jetski');
    }

    public function test_pricing_unit_label_is_null_when_not_set(): void
    {
        $listing = Listing::factory()->create([
            'status' => \App\Enums\ListingStatus::PUBLISHED,
            'pricing' => [
                'pricing_model' => 'per_person',
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['fr' => 'Adulte', 'en' => 'Adult'],
                        'tnd_price' => 50,
                        'eur_price' => 17,
                    ],
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.pricing.unitLabel', null);
    }

    public function test_pricing_unit_label_drops_whitespace_only_locales(): void
    {
        $listing = Listing::factory()->create([
            'status' => \App\Enums\ListingStatus::PUBLISHED,
            'pricing' => [
                'pricing_model' => 'per_person',
                'unit_label' => ['fr' => 'par jetski', 'en' => '   '],
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['fr' => 'Adulte', 'en' => 'Adult'],
                        'tnd_price' => 105,
                        'eur_price' => 35,
                    ],
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}");

        $response->assertOk()
            ->assertJsonPath('data.pricing.unitLabel.fr', 'par jetski');

        // Whitespace-only EN should be filtered out by the helper, leaving the
        // map with only FR. Decode and check directly because assertJsonMissingPath
        // matches when the parent doesn't exist either; we want to assert the
        // shape precisely.
        $unitLabel = $response->json('data.pricing.unitLabel');
        $this->assertSame(['fr' => 'par jetski'], $unitLabel);
    }
}
