<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating payment intent for booking.
     */
    public function test_create_payment_intent_for_booking(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 150.00,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'booking' => [
                    'id',
                    'status',
                ],
                'payment' => [
                    'id',
                    'status',
                    'gateway',
                ],
            ]);

        $this->assertDatabaseHas('payment_intents', [
            'booking_id' => $booking->id,
            'gateway' => 'mock',
            'amount' => 150.00,
        ]);
    }

    /**
     * Test mock payment completes successfully.
     */
    public function test_mock_payment_completes_successfully(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 150.00,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'mock',
                'payment_details' => [
                    'card_number' => '4242424242424242',
                ],
            ]);

        // Assert
        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);
        $this->assertNotNull($booking->confirmed_at);
    }

    /**
     * Test payment fails for already paid booking.
     */
    public function test_payment_fails_for_already_paid_booking(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Booking is not in a payable state',
            ]);
    }

    /**
     * Test user can only pay for their own booking.
     */
    public function test_user_can_only_pay_for_own_booking(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user1->id,
            'status' => BookingStatus::PENDING_PAYMENT,
        ]);

        // Act
        $response = $this->actingAs($user2)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test offline payment creates pending payment intent.
     */
    public function test_offline_payment_creates_pending_intent(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 150.00,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'bank_transfer',
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('payment_intents', [
            'booking_id' => $booking->id,
            'gateway' => 'offline',
            'payment_method' => 'bank_transfer',
            'status' => PaymentStatus::PENDING->value,
        ]);

        // Booking should be in pending confirmation status
        $booking->refresh();
        $this->assertEquals(BookingStatus::PENDING_CONFIRMATION, $booking->status);
    }

    /**
     * Test retrieving payment intent details.
     */
    public function test_retrieve_payment_intent_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);
        $payment = PaymentIntent::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 150.00,
            'currency' => 'CAD',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/v1/payments/{$payment->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'payment' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'gateway',
                    'payment_method',
                ],
            ]);
    }

    /**
     * Test payment with coupon applied.
     */
    public function test_payment_with_coupon_applied(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 150.00,
            'discount_amount' => 20.00,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(200);

        // Payment should be for discounted amount
        $this->assertDatabaseHas('payment_intents', [
            'booking_id' => $booking->id,
            'amount' => 130.00, // 150 - 20
        ]);
    }

    /**
     * Test payment intent includes metadata.
     */
    public function test_payment_intent_includes_metadata(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::PENDING_PAYMENT,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'mock',
                'payment_details' => [
                    'card_brand' => 'visa',
                    'card_last4' => '4242',
                ],
            ]);

        // Assert
        $response->assertStatus(200);

        $payment = PaymentIntent::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment->metadata);
    }

    /**
     * Test payment refund for cancelled booking.
     */
    public function test_payment_refund_for_cancelled_booking(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'total_amount' => 150.00,
        ]);

        PaymentIntent::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 150.00,
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'mock',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/cancel", [
                'reason' => 'Change of plans',
            ]);

        // Assert
        $response->assertStatus(200);
        $booking->refresh();
        $this->assertEquals(BookingStatus::CANCELLED, $booking->status);

        // Check if refund was initiated
        $this->assertDatabaseHas('payment_intents', [
            'booking_id' => $booking->id,
            'status' => PaymentStatus::REFUNDED->value,
        ]);
    }

    /**
     * Test payment history retrieval.
     */
    public function test_payment_history_retrieval(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
        ]);

        PaymentIntent::factory()->count(3)->create([
            'booking_id' => $booking->id,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/v1/bookings/{$booking->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'booking' => [
                    'payment_intents' => [
                        '*' => [
                            'id',
                            'amount',
                            'status',
                            'gateway',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test guest can pay for booking with magic token.
     */
    public function test_guest_can_pay_with_magic_token(): void
    {
        // Arrange
        Mail::fake();
        $booking = Booking::factory()->withMagicToken()->create([
            'user_id' => null,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 150.00,
        ]);

        // Act
        $response = $this->postJson(
            "/api/v1/bookings/{$booking->booking_number}/pay?token={$booking->magic_token}",
            [
                'payment_method' => 'mock',
            ]
        );

        // Assert
        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);
    }

    /**
     * Test payment requires valid payment method.
     */
    public function test_payment_requires_valid_payment_method(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::PENDING_PAYMENT,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/pay", [
                'payment_method' => 'invalid_method',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }
}
