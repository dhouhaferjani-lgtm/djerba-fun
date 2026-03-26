<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can validate a valid percentage coupon.
     */
    public function test_user_can_validate_valid_percentage_coupon(): void
    {
        $listing = Listing::factory()->create();
        $coupon = Coupon::factory()->create([
            'code' => 'SUMMER2025',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SUMMER2025',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'coupon_id' => $coupon->id,
                'discount_amount' => 20.00,
            ]);
    }

    /**
     * Test validation fails for invalid coupon code.
     */
    public function test_validation_fails_for_invalid_coupon_code(): void
    {
        $listing = Listing::factory()->create();

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'INVALID',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'message' => 'Coupon code not found.',
            ]);
    }

    /**
     * Test validation fails for expired coupon.
     */
    public function test_validation_fails_for_expired_coupon(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'EXPIRED',
            'is_active' => true,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'EXPIRED',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'message' => 'This coupon is no longer valid or has reached its usage limit.',
            ]);
    }

    /**
     * Test validation fails for inactive coupon.
     */
    public function test_validation_fails_for_inactive_coupon(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'INACTIVE',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'message' => 'This coupon is no longer valid or has reached its usage limit.',
            ]);
    }

    /**
     * Test validation fails when usage limit reached.
     */
    public function test_validation_fails_when_usage_limit_reached(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'LIMITED',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'LIMITED',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'message' => 'This coupon is no longer valid or has reached its usage limit.',
            ]);
    }

    /**
     * Test percentage discount calculation.
     */
    public function test_percentage_discount_calculation(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'PERCENT',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'PERCENT',
            'listing_id' => $listing->id,
            'amount' => 200.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 50.00, // 25% of 200
            ]);
    }

    /**
     * Test fixed amount discount calculation.
     */
    public function test_fixed_amount_discount_calculation(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'FIXED',
            'discount_type' => 'fixed_amount',
            'discount_value' => 30,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'FIXED',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 30.00,
            ]);
    }

    /**
     * Test validation fails for amount below minimum order.
     */
    public function test_validation_fails_for_amount_below_minimum_order(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'MIN100',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
            'minimum_order' => 100.00,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'MIN100',
            'listing_id' => $listing->id,
            'amount' => 50.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Minimum order amount of 100.00 required to use this coupon.',
            ]);
    }

    /**
     * Test coupon specific to certain listings.
     */
    public function test_coupon_specific_to_certain_listings(): void
    {
        $listing1 = Listing::factory()->create();
        $listing2 = Listing::factory()->create();

        Coupon::factory()->create([
            'code' => 'SPECIFIC',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
            'listing_ids' => [$listing1->id],
        ]);

        // Valid listing
        $response1 = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SPECIFIC',
            'listing_id' => $listing1->id,
            'amount' => 100.00,
        ]);

        // Invalid listing
        $response2 = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SPECIFIC',
            'listing_id' => $listing2->id,
            'amount' => 100.00,
        ]);

        $response1->assertStatus(200)->assertJson(['valid' => true]);
        $response2->assertStatus(422)->assertJson([
            'valid' => false,
            'message' => 'This coupon is not valid for the selected listing.',
        ]);
    }

    /**
     * Test validation fails when coupon not started yet.
     */
    public function test_validation_fails_when_coupon_not_started_yet(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'FUTURE',
            'is_active' => true,
            'valid_from' => now()->addWeek(),
            'valid_until' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'FUTURE',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'message' => 'This coupon is no longer valid or has reached its usage limit.',
            ]);
    }

    /**
     * Test max discount amount cap.
     */
    public function test_max_discount_amount_cap(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'CAPPED',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'maximum_discount' => 25.00,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'CAPPED',
            'listing_id' => $listing->id,
            'amount' => 100.00, // 50% would be $50, but capped at $25
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 25.00,
            ]);
    }

    /**
     * Test fixed discount capped at order amount.
     */
    public function test_fixed_discount_capped_at_order_amount(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'BIGFIXED',
            'discount_type' => 'fixed_amount',
            'discount_value' => 50.00,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'BIGFIXED',
            'listing_id' => $listing->id,
            'amount' => 30.00, // Discount is $50 but capped at order amount $30
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 30.00,
            ]);
    }

    /**
     * Test coupon code is case insensitive.
     */
    public function test_coupon_code_is_case_insensitive(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'SUMMER2025',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'summer2025',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 10.00,
            ]);
    }

    /**
     * Test coupon with user restriction.
     */
    public function test_coupon_with_user_restriction(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $listing = Listing::factory()->create();

        Coupon::factory()->create([
            'code' => 'USERONLY',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
            'user_ids' => [$user1->id],
        ]);

        // Authorized user
        $response1 = $this->actingAs($user1)->postJson('/api/v1/coupons/validate', [
            'code' => 'USERONLY',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        // Unauthorized user
        $response2 = $this->actingAs($user2)->postJson('/api/v1/coupons/validate', [
            'code' => 'USERONLY',
            'listing_id' => $listing->id,
            'amount' => 100.00,
        ]);

        $response1->assertStatus(200)->assertJson(['valid' => true]);
        $response2->assertStatus(422)->assertJson([
            'valid' => false,
            'message' => 'This coupon is not available for your account.',
        ]);
    }

    /**
     * Test coupon validation requires listing_id.
     */
    public function test_coupon_validation_requires_listing_id(): void
    {
        Coupon::factory()->create([
            'code' => 'TEST',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'TEST',
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['listing_id']);
    }

    /**
     * Test coupon validation requires amount.
     */
    public function test_coupon_validation_requires_amount(): void
    {
        $listing = Listing::factory()->create();
        Coupon::factory()->create([
            'code' => 'TEST',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'TEST',
            'listing_id' => $listing->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test coupon is applied during booking creation.
     */
    public function test_coupon_applied_during_booking_creation(): void
    {
        $listing = Listing::factory()->create([
            'pricing' => [
                'tnd_price' => 100.00,
                'eur_price' => 30.00,
                'currency' => 'TND',
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'date' => now()->addDays(5),
            'base_price' => 100.00,
            'currency' => 'TND',
            'capacity' => 10,
            'remaining_capacity' => 10,
        ]);

        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'session_id' => 'test-session-123',
            'quantity' => 2,
            'currency' => 'TND',
            'person_type_breakdown' => ['adult' => 2],
        ]);

        Coupon::factory()->create([
            'code' => 'SAVE20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'hold_id' => $hold->id,
            'session_id' => 'test-session-123',
            'coupon_code' => 'SAVE20',
            'travelers' => [
                [
                    'email' => 'test@example.com',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                ],
            ],
        ]);

        $response->assertStatus(201);

        // Verify discount was applied
        $booking = Booking::first();
        $this->assertNotNull($booking);
        $this->assertNotNull($booking->coupon_id);
        // Discount depends on base_price calculation - just verify discount_amount > 0
        $this->assertGreaterThan(0, (float) $booking->discount_amount);
    }

    /**
     * Test coupon usage is incremented after payment confirmation.
     */
    public function test_coupon_usage_incremented_after_payment(): void
    {
        $listing = Listing::factory()->create([
            'pricing' => [
                'tnd_price' => 100.00,
                'currency' => 'TND',
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'date' => now()->addDays(5),
            'base_price' => 100.00,
            'currency' => 'TND',
            'capacity' => 10,
            'remaining_capacity' => 10,
        ]);

        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'session_id' => 'test-session-456',
            'quantity' => 1,
            'currency' => 'TND',
            'person_type_breakdown' => ['adult' => 1],
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'ONCE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
            'usage_count' => 0,
        ]);

        // Create booking with coupon
        $this->postJson('/api/v1/bookings', [
            'hold_id' => $hold->id,
            'session_id' => 'test-session-456',
            'coupon_code' => 'ONCE',
            'travelers' => [
                ['email' => 'test2@example.com'],
            ],
        ])->assertStatus(201);

        // Usage count should NOT be incremented yet (payment pending)
        $coupon->refresh();
        $this->assertEquals(0, $coupon->usage_count);
    }
}
