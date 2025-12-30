<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\IncomePricingConfig;
use App\Services\IncomePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomePricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private IncomePricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IncomePricingService();
    }

    /**
     * Test price calculation with default ratio.
     */
    public function test_calculates_price_with_default_ratio(): void
    {
        // Act - No config exists, should use default
        $eurPrice = $this->service->calculateExpectedPrice(100.00);

        // Assert - Default ratio is 0.1286
        $this->assertEquals(12.86, $eurPrice);
    }

    /**
     * Test price calculation with configured ratio.
     */
    public function test_calculates_price_with_configured_ratio(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.15,
            'is_active' => true,
        ]);

        // Act
        $eurPrice = $this->service->calculateExpectedPrice(100.00);

        // Assert - Using configured ratio of 0.15
        $this->assertEquals(15.00, $eurPrice);
    }

    /**
     * Test pricing validation passes when within tolerance.
     */
    public function test_validation_passes_when_within_tolerance(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.1286,
            'tolerance_percent' => 20,
            'is_active' => true,
        ]);

        // Act - TND 100 -> Expected EUR 12.86, Actual EUR 13.00 (within 20% tolerance)
        $result = $this->service->validatePricing(100.00, 13.00);

        // Assert
        $this->assertTrue($result['is_valid']);
        $this->assertStringContainsString('within income parity tolerance', $result['message']);
    }

    /**
     * Test pricing validation fails when above tolerance.
     */
    public function test_validation_fails_when_above_tolerance(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.1286,
            'tolerance_percent' => 10,
            'is_active' => true,
        ]);

        // Act - TND 100 -> Expected EUR 12.86, Actual EUR 20.00 (way above tolerance)
        $result = $this->service->validatePricing(100.00, 20.00);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('above the income parity suggestion', $result['message']);
        $this->assertEquals(12.86, $result['suggested_eur']);
        $this->assertArrayHasKey('lower_bound', $result);
        $this->assertArrayHasKey('upper_bound', $result);
    }

    /**
     * Test pricing validation fails when below tolerance.
     */
    public function test_validation_fails_when_below_tolerance(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.1286,
            'tolerance_percent' => 10,
            'is_active' => true,
        ]);

        // Act - TND 100 -> Expected EUR 12.86, Actual EUR 5.00 (below tolerance)
        $result = $this->service->validatePricing(100.00, 5.00);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('below the income parity suggestion', $result['message']);
    }

    /**
     * Test tolerance bounds calculation.
     */
    public function test_calculates_tolerance_bounds_correctly(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.10,
            'tolerance_percent' => 20,
            'is_active' => true,
        ]);

        // Act - TND 100 -> Expected EUR 10.00
        $result = $this->service->validatePricing(100.00, 15.00);

        // Assert - 20% tolerance on 10.00 = 8.00 to 12.00
        $this->assertFalse($result['is_valid']); // 15 is outside bounds
        $this->assertEquals(8.00, $result['lower_bound']);
        $this->assertEquals(12.00, $result['upper_bound']);
    }

    /**
     * Test price at exact lower bound is valid.
     */
    public function test_price_at_lower_bound_is_valid(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.10,
            'tolerance_percent' => 20,
            'is_active' => true,
        ]);

        // Act - TND 100 -> Expected EUR 10.00, Lower bound 8.00
        $result = $this->service->validatePricing(100.00, 8.00);

        // Assert
        $this->assertTrue($result['is_valid']);
    }

    /**
     * Test price at exact upper bound is valid.
     */
    public function test_price_at_upper_bound_is_valid(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.10,
            'tolerance_percent' => 20,
            'is_active' => true,
        ]);

        // Act - TND 100 -> Expected EUR 10.00, Upper bound 12.00
        $result = $this->service->validatePricing(100.00, 12.00);

        // Assert
        $this->assertTrue($result['is_valid']);
    }

    /**
     * Test isWithinTolerance method returns correct boolean.
     */
    public function test_is_within_tolerance_returns_correct_boolean(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.10,
            'tolerance_percent' => 20,
            'is_active' => true,
        ]);

        // Act & Assert
        $this->assertTrue($this->service->isWithinTolerance(100.00, 10.00));
        $this->assertTrue($this->service->isWithinTolerance(100.00, 11.00));
        $this->assertFalse($this->service->isWithinTolerance(100.00, 20.00));
        $this->assertFalse($this->service->isWithinTolerance(100.00, 5.00));
    }

    /**
     * Test getParityRatio returns configured ratio.
     */
    public function test_get_parity_ratio_returns_configured_ratio(): void
    {
        // Arrange
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.125,
            'is_active' => true,
        ]);

        // Act
        $ratio = $this->service->getParityRatio('TND', 'EUR');

        // Assert
        $this->assertEquals(0.125, $ratio);
    }

    /**
     * Test getParityRatio returns null when no config exists.
     */
    public function test_get_parity_ratio_returns_null_when_no_config(): void
    {
        // Act
        $ratio = $this->service->getParityRatio('USD', 'GBP');

        // Assert
        $this->assertNull($ratio);
    }

    /**
     * Test only active configs are used.
     */
    public function test_only_active_configs_are_used(): void
    {
        // Arrange - Create inactive config
        IncomePricingConfig::factory()->create([
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'parity_ratio' => 0.50, // High ratio
            'is_active' => false,
        ]);

        // Act - Should use default since active config doesn't exist
        $eurPrice = $this->service->calculateExpectedPrice(100.00);

        // Assert - Should not use 0.50, should use default 0.1286
        $this->assertEquals(12.86, $eurPrice);
    }

    /**
     * Test validation with no config provides suggestion.
     */
    public function test_validation_with_no_config_provides_suggestion(): void
    {
        // Act
        $result = $this->service->validatePricing(100.00, 15.00);

        // Assert
        $this->assertTrue($result['is_valid']); // No config means auto-valid
        $this->assertStringContainsString('No income parity configuration', $result['message']);
        $this->assertEquals(12.86, $result['suggested_eur']); // Default calculation
    }
}
