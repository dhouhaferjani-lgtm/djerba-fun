<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PppTestHelpers;
use Tests\TestCase;

/**
 * Test suite for PriceCalculationService.
 *
 * Validates price calculations for dual-currency listings, person type breakdowns,
 * group discounts, and backward compatibility.
 */
class PriceCalculationServiceTest extends TestCase
{
    use PppTestHelpers;
    use RefreshDatabase;

    protected PriceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PriceCalculationService::class);
    }

    /**
     * Test price calculation for Tunisia tier (TND currency).
     */
    public function test_calculate_price_for_tunisia_tier(): void
    {
        $listing = $this->createDualPricedListing();

        $breakdown = ['adult' => 2];

        $result = $this->service->calculateTotal($listing, $breakdown, 'TND');

        $this->assertEquals('TND', $result['currency'], 'Currency should be TND');
        $this->assertEquals(300, $result['total'], 'Total should be 2 adults × 150 TND = 300 TND');
        $this->assertEquals(300, $result['subtotal'], 'Subtotal should be 300 TND');
        $this->assertEquals(0, $result['discount'], 'No discount for 2 people');
        $this->assertEquals(2, $result['totalGuests']);
    }

    /**
     * Test price calculation for international tier (EUR currency).
     */
    public function test_calculate_price_for_international_tier(): void
    {
        $listing = $this->createDualPricedListing();

        $breakdown = ['adult' => 2];

        $result = $this->service->calculateTotal($listing, $breakdown, 'EUR');

        $this->assertEquals('EUR', $result['currency'], 'Currency should be EUR');
        $this->assertEquals(100, $result['total'], 'Total should be 2 adults × 50 EUR = 100 EUR');
        $this->assertEquals(100, $result['subtotal'], 'Subtotal should be 100 EUR');
        $this->assertEquals(0, $result['discount'], 'No discount for 2 people');
        $this->assertEquals(2, $result['totalGuests']);
    }

    /**
     * Test price calculation with mixed person types (adult, child, infant).
     */
    public function test_calculate_price_with_mixed_person_types(): void
    {
        $listing = $this->createDualPricedListing();

        $breakdown = [
            'adult' => 2,  // 2 × 150 = 300
            'child' => 1,  // 1 × 75 = 75
            'infant' => 1, // 1 × 0 = 0
        ];

        $result = $this->service->calculateTotal($listing, $breakdown, 'TND');

        $this->assertEquals('TND', $result['currency']);
        $this->assertEquals(375, $result['total'], 'Total should be 300 + 75 + 0 = 375 TND');
        $this->assertEquals(375, $result['subtotal']);
        $this->assertEquals(4, $result['totalGuests'], 'Total guests should be 4 (adult + child + infant)');

        // Verify breakdown details
        $this->assertCount(3, $result['breakdown'], 'Should have 3 person type entries');

        $adultEntry = collect($result['breakdown'])->firstWhere('type', 'adult');
        $this->assertEquals(150, $adultEntry['unitPrice']);
        $this->assertEquals(2, $adultEntry['quantity']);
        $this->assertEquals(300, $adultEntry['total']);

        $childEntry = collect($result['breakdown'])->firstWhere('type', 'child');
        $this->assertEquals(75, $childEntry['unitPrice']);
        $this->assertEquals(1, $childEntry['quantity']);
        $this->assertEquals(75, $childEntry['total']);

        $infantEntry = collect($result['breakdown'])->firstWhere('type', 'infant');
        $this->assertEquals(0, $infantEntry['unitPrice']);
        $this->assertEquals(1, $infantEntry['quantity']);
        $this->assertEquals(0, $infantEntry['total']);
    }

    /**
     * Test that group discount applies correctly when threshold is met.
     */
    public function test_group_discount_applies_correctly(): void
    {
        $listing = $this->createDualPricedListing();

        // 6 adults meets the min_size threshold
        $breakdown = ['adult' => 6];

        $result = $this->service->calculateTotal($listing, $breakdown, 'TND');

        $expectedSubtotal = 6 * 150; // 900 TND
        $expectedDiscount = $expectedSubtotal * 0.10; // 10% discount = 90 TND
        $expectedTotal = $expectedSubtotal - $expectedDiscount; // 810 TND

        $this->assertEquals(900, $result['subtotal'], 'Subtotal should be 6 × 150 = 900 TND');
        $this->assertEquals(90, $result['discount'], 'Discount should be 10% of 900 = 90 TND');
        $this->assertEquals(810, $result['total'], 'Total should be 900 - 90 = 810 TND');
        $this->assertEquals(6, $result['totalGuests']);
    }

    /**
     * Test that group discount does NOT apply below threshold.
     */
    public function test_group_discount_does_not_apply_below_threshold(): void
    {
        $listing = $this->createDualPricedListing();

        // 5 adults is below the min_size threshold of 6
        $breakdown = ['adult' => 5];

        $result = $this->service->calculateTotal($listing, $breakdown, 'TND');

        $expectedSubtotal = 5 * 150; // 750 TND

        $this->assertEquals(750, $result['subtotal'], 'Subtotal should be 5 × 150 = 750 TND');
        $this->assertEquals(0, $result['discount'], 'No discount should apply for groups < 6');
        $this->assertEquals(750, $result['total'], 'Total should equal subtotal when no discount');
        $this->assertEquals(5, $result['totalGuests']);
    }

    /**
     * Test that price never goes negative after discount.
     */
    public function test_price_never_negative_after_discount(): void
    {
        $listing = $this->createDualPricedListing([
            'pricing' => [
                'currency' => 'TND',
                'tnd_price' => 100,
                'eur_price' => 50,
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['en' => 'Adult'],
                        'tnd_price' => 100,
                        'eur_price' => 50,
                    ],
                ],
                'group_discount' => [
                    'min_size' => 1,
                    'discount_percent' => 150, // Impossible 150% discount
                ],
            ],
        ]);

        $breakdown = ['adult' => 2];

        $result = $this->service->calculateTotal($listing, $breakdown, 'TND');

        $this->assertGreaterThanOrEqual(0, $result['total'], 'Total should never be negative');
        $this->assertEquals(0, $result['total'], 'Total should be clamped to 0 when discount exceeds subtotal');
    }

    /**
     * Test that currency defaults to EUR if not specified.
     */
    public function test_currency_defaults_to_eur_if_not_specified(): void
    {
        $listing = $this->createDualPricedListing();

        $breakdown = ['adult' => 2];

        // Don't specify currency
        $result = $this->service->calculateTotal($listing, $breakdown);

        // Should default to listing's default currency (TND from our helper)
        $this->assertEquals('TND', $result['currency'], 'Should use listing default currency');

        // Create a listing with EUR as default
        $eurListing = $this->createDualPricedListing([
            'pricing' => [
                'currency' => 'EUR',
                'tnd_price' => 150,
                'eur_price' => 50,
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['en' => 'Adult'],
                        'tnd_price' => 150,
                        'eur_price' => 50,
                    ],
                ],
            ],
        ]);

        $result = $this->service->calculateTotal($eurListing, $breakdown);
        $this->assertEquals('EUR', $result['currency'], 'Should use EUR when listing default is EUR');
    }

    /**
     * Test backward compatibility with single-currency format.
     *
     * Older listings may only have basePrice without dual pricing.
     */
    public function test_backward_compatibility_with_single_currency_format(): void
    {
        $listing = $this->createDualPricedListing([
            'pricing' => [
                'currency' => 'EUR',
                'basePrice' => 60, // Old format
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['en' => 'Adult'],
                        'price' => 60, // Old format
                    ],
                ],
            ],
        ]);

        $breakdown = ['adult' => 2];

        $result = $this->service->calculateTotal($listing, $breakdown, 'EUR');

        $this->assertEquals('EUR', $result['currency']);
        $this->assertEquals(120, $result['total'], 'Should calculate using old basePrice format');
        $this->assertEquals(2, $result['totalGuests']);
    }

    /**
     * Test calculateSimpleTotal for backward compatibility.
     */
    public function test_calculate_simple_total_works(): void
    {
        $listing = $this->createDualPricedListing();

        $result = $this->service->calculateSimpleTotal($listing, 3, 'TND');

        $this->assertEquals('TND', $result['currency']);
        $this->assertEquals(450, $result['total'], 'Simple total should be 3 × 150 = 450 TND');
        $this->assertEquals(450, $result['subtotal']);
        $this->assertEquals(0, $result['discount'], 'No discount for 3 people');
    }

    /**
     * Test getPersonTypes returns default types when not defined.
     */
    public function test_get_person_types_returns_defaults_when_not_defined(): void
    {
        $listing = $this->createDualPricedListing([
            'pricing' => [
                'currency' => 'TND',
                'tnd_price' => 100,
                'eur_price' => 50,
                // No person_types defined
            ],
        ]);

        $personTypes = $this->service->getPersonTypes($listing, 'TND');

        $this->assertCount(3, $personTypes, 'Should return 3 default person types');
        $this->assertEquals('adult', $personTypes[0]['key']);
        $this->assertEquals('child', $personTypes[1]['key']);
        $this->assertEquals('infant', $personTypes[2]['key']);

        // Verify prices match base price and discounts
        $this->assertEquals(100, $personTypes[0]['price'], 'Adult price should match base price');
        $this->assertEquals(50, $personTypes[1]['price'], 'Child price should be 50% of adult');
        $this->assertEquals(0, $personTypes[2]['price'], 'Infant price should be free');
    }

    /**
     * Test zero quantity person types are excluded from breakdown.
     */
    public function test_zero_quantity_person_types_excluded_from_breakdown(): void
    {
        $listing = $this->createDualPricedListing();

        $breakdown = [
            'adult' => 2,
            'child' => 0, // Zero children
            'infant' => 1,
        ];

        $result = $this->service->calculateTotal($listing, $breakdown, 'TND');

        // Should only have 2 entries (adult and infant), not 3
        $this->assertCount(2, $result['breakdown'], 'Zero quantity types should be excluded');

        $types = collect($result['breakdown'])->pluck('type')->toArray();
        $this->assertContains('adult', $types);
        $this->assertContains('infant', $types);
        $this->assertNotContains('child', $types, 'Child with 0 quantity should be excluded');
    }
}
