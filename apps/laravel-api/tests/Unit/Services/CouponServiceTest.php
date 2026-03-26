<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CouponService();
    }

    /**
     * Test coupon validation succeeds with valid coupon.
     */
    public function test_coupon_validation_succeeds_with_valid_coupon(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $coupon = Coupon::factory()->percentage(20)->create([
            'code' => 'SAVE20',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDays(30),
        ]);

        // Act
        $result = $this->service->validate(
            'SAVE20',
            (string) $listing->id,
            100.00
        );

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals($coupon->id, $result['coupon_id']);
    }

    /**
     * Test validation fails with non-existent coupon code.
     */
    public function test_validation_fails_with_nonexistent_code(): void
    {
        // Arrange
        $listing = Listing::factory()->create();

        // Act
        $result = $this->service->validate(
            'INVALID',
            (string) $listing->id,
            100.00
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    /**
     * Test validation fails with expired coupon.
     */
    public function test_validation_fails_with_expired_coupon(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $coupon = Coupon::factory()->expired()->create([
            'code' => 'EXPIRED',
        ]);

        // Act
        $result = $this->service->validate(
            'EXPIRED',
            (string) $listing->id,
            100.00
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('no longer valid', $result['message']);
    }

    /**
     * Test validation fails with inactive coupon.
     */
    public function test_validation_fails_with_inactive_coupon(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $coupon = Coupon::factory()->inactive()->create([
            'code' => 'INACTIVE',
        ]);

        // Act
        $result = $this->service->validate(
            'INACTIVE',
            (string) $listing->id,
            100.00
        );

        // Assert
        $this->assertFalse($result['valid']);
    }

    /**
     * Test validation fails when max uses reached.
     */
    public function test_validation_fails_when_max_uses_reached(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $coupon = Coupon::factory()->maxedOut()->create([
            'code' => 'MAXED',
        ]);

        // Act
        $result = $this->service->validate(
            'MAXED',
            (string) $listing->id,
            100.00
        );

        // Assert
        $this->assertFalse($result['valid']);
    }

    /**
     * Test validation fails when amount below minimum.
     */
    public function test_validation_fails_when_below_minimum(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $coupon = Coupon::factory()
            ->withMinPurchase(100.00)
            ->create([
                'code' => 'MIN100',
            ]);

        // Act
        $result = $this->service->validate(
            'MIN100',
            (string) $listing->id,
            50.00
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Minimum order', $result['message']);
    }

    /**
     * Test validation fails for specific listing when not applicable.
     */
    public function test_validation_fails_for_wrong_listing(): void
    {
        // Arrange
        $listing1 = Listing::factory()->create();
        $listing2 = Listing::factory()->create();
        $coupon = Coupon::factory()
            ->forListings([$listing1->id])
            ->create([
                'code' => 'SPECIFIC',
            ]);

        // Act
        $result = $this->service->validate(
            'SPECIFIC',
            $listing2->id,
            100.00
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not valid for the selected listing', $result['message']);
    }

    /**
     * Test percentage discount calculation.
     */
    public function test_percentage_discount_calculation(): void
    {
        // Arrange
        $coupon = Coupon::factory()->percentage(25)->create();

        // Act
        $discount = $this->service->getDiscount($coupon, 200.00);

        // Assert
        $this->assertEquals(50.00, $discount);
    }

    /**
     * Test fixed amount discount calculation.
     */
    public function test_fixed_amount_discount_calculation(): void
    {
        // Arrange
        $coupon = Coupon::factory()->fixedAmount(30.00)->create();

        // Act
        $discount = $this->service->getDiscount($coupon, 200.00);

        // Assert
        $this->assertEquals(30.00, $discount);
    }

    /**
     * Test percentage discount respects max discount.
     */
    public function test_percentage_discount_respects_max(): void
    {
        // Arrange
        $coupon = Coupon::factory()
            ->percentage(50)
            ->withMaxDiscount(50.00)
            ->create();

        // Act
        $discount = $this->service->getDiscount($coupon, 200.00);

        // Assert - 50% of 200 would be 100, but max is 50
        $this->assertEquals(50.00, $discount);
    }

    /**
     * Test applying coupon to booking.
     */
    public function test_applying_coupon_to_booking(): void
    {
        // Arrange
        $coupon = Coupon::factory()->percentage(20)->create([
            'usage_count' => 0,
        ]);
        $booking = Booking::factory()->create([
            'total_amount' => 100.00,
            'discount_amount' => 0,
        ]);

        // Act
        $this->service->apply($coupon, $booking);

        // Assert
        $booking->refresh();
        $this->assertEquals($coupon->id, $booking->coupon_id);
        $this->assertEquals(20.00, $booking->discount_amount);

        $coupon->refresh();
        $this->assertEquals(1, $coupon->usage_count);
    }

    /**
     * Test fixed discount doesn't exceed order amount.
     */
    public function test_fixed_discount_does_not_exceed_order_amount(): void
    {
        // Arrange
        $coupon = Coupon::factory()->fixedAmount(150.00)->create();

        // Act
        $discount = $this->service->getDiscount($coupon, 100.00);

        // Assert - Discount should not exceed order amount
        $this->assertLessThanOrEqual(100.00, $discount);
    }
}
