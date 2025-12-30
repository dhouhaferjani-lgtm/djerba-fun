<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can validate a valid coupon.
     */
    public function test_user_can_validate_valid_coupon(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'SUMMER2025',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addWeek(),
        ]);

        $listing = Listing::factory()->create();

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SUMMER2025',
            'listing_id' => $listing->id,
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'coupon' => [
                    'code',
                    'discount_type',
                    'discount_value',
                    'discount_amount',
                ],
            ])
            ->assertJsonFragment([
                'discount_amount' => 20.00,
            ]);
    }

    /**
     * Test validation fails for invalid coupon code.
     */
    public function test_validation_fails_for_invalid_coupon_code(): void
    {
        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'INVALID',
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Coupon not found',
            ]);
    }

    /**
     * Test validation fails for expired coupon.
     */
    public function test_validation_fails_for_expired_coupon(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'EXPIRED',
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'EXPIRED',
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Coupon has expired',
            ]);
    }

    /**
     * Test validation fails for inactive coupon.
     */
    public function test_validation_fails_for_inactive_coupon(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'INACTIVE',
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Coupon is not active',
            ]);
    }

    /**
     * Test validation fails when usage limit reached.
     */
    public function test_validation_fails_when_usage_limit_reached(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'LIMITED',
            'is_active' => true,
            'max_uses' => 5,
            'used_count' => 5,
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'LIMITED',
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Coupon usage limit reached',
            ]);
    }

    /**
     * Test validation fails when user usage limit reached.
     */
    public function test_validation_fails_when_user_usage_limit_reached(): void
    {
        // Arrange
        $user = $this->createUser();
        $coupon = Coupon::factory()->create([
            'code' => 'ONCE',
            'is_active' => true,
            'max_uses_per_user' => 1,
        ]);

        // User has already used this coupon
        Booking::factory()->create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/coupons/validate', [
                'code' => 'ONCE',
                'total_amount' => 100.00,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You have already used this coupon',
            ]);
    }

    /**
     * Test percentage discount calculation.
     */
    public function test_percentage_discount_calculation(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'PERCENT',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'PERCENT',
            'total_amount' => 200.00,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'discount_amount' => 50.00, // 25% of 200
            ]);
    }

    /**
     * Test fixed amount discount calculation.
     */
    public function test_fixed_amount_discount_calculation(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'FIXED',
            'discount_type' => 'fixed',
            'discount_value' => 30,
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'FIXED',
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'discount_amount' => 30.00,
            ]);
    }

    /**
     * Test validation fails for amount below minimum.
     */
    public function test_validation_fails_for_amount_below_minimum(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'MIN100',
            'is_active' => true,
            'minimum_purchase_amount' => 100.00,
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'MIN100',
            'total_amount' => 50.00,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Minimum purchase amount not met',
            ]);
    }

    /**
     * Test coupon specific to certain listings.
     */
    public function test_coupon_specific_to_certain_listings(): void
    {
        // Arrange
        $listing1 = Listing::factory()->create();
        $listing2 = Listing::factory()->create();

        $coupon = Coupon::factory()->create([
            'code' => 'SPECIFIC',
            'is_active' => true,
            'applicable_listing_ids' => [$listing1->id],
        ]);

        // Act - Valid listing
        $response1 = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SPECIFIC',
            'listing_id' => $listing1->id,
            'total_amount' => 100.00,
        ]);

        // Act - Invalid listing
        $response2 = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SPECIFIC',
            'listing_id' => $listing2->id,
            'total_amount' => 100.00,
        ]);

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Coupon not applicable to this listing',
            ]);
    }

    /**
     * Test coupon usage is tracked in booking.
     */
    public function test_coupon_usage_tracked_in_booking(): void
    {
        // Arrange
        $user = $this->createUser();
        $coupon = Coupon::factory()->create([
            'code' => 'TRACK',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'coupon_code' => 'TRACK',
            'discount_amount' => 10.00,
        ]);

        // Assert
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'coupon_id' => $coupon->id,
            'coupon_code' => 'TRACK',
            'discount_amount' => 10.00,
        ]);

        // Coupon usage count should increment
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
    }

    /**
     * Test validation fails when not started yet.
     */
    public function test_validation_fails_when_not_started_yet(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'FUTURE',
            'is_active' => true,
            'starts_at' => now()->addWeek(),
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'FUTURE',
            'total_amount' => 100.00,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Coupon not yet active',
            ]);
    }

    /**
     * Test max discount amount cap.
     */
    public function test_max_discount_amount_cap(): void
    {
        // Arrange
        $coupon = Coupon::factory()->create([
            'code' => 'CAPPED',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'max_discount_amount' => 25.00,
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'CAPPED',
            'total_amount' => 100.00, // 50% would be $50, but capped at $25
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'discount_amount' => 25.00,
            ]);
    }

    /**
     * Test first-time user only coupon.
     */
    public function test_first_time_user_only_coupon(): void
    {
        // Arrange
        $user = $this->createUser();
        $coupon = Coupon::factory()->create([
            'code' => 'FIRSTTIME',
            'is_active' => true,
            'first_time_user_only' => true,
        ]);

        // User has previous booking
        Booking::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/coupons/validate', [
                'code' => 'FIRSTTIME',
                'total_amount' => 100.00,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Coupon is for first-time users only',
            ]);
    }
}
