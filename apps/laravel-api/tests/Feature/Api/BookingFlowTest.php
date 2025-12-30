<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can create a booking hold.
     */
    public function test_user_can_create_booking_hold(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'booked_count' => 0,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 2,
                'person_type_breakdown' => [
                    'adults' => 2,
                ],
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'hold' => [
                    'id',
                    'expires_at',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('booking_holds', [
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => $user->id,
            'status' => HoldStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test hold creation fails when capacity is exceeded.
     */
    public function test_hold_creation_fails_when_capacity_exceeded(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 5,
            'booked_count' => 5,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 2,
                'person_type_breakdown' => [
                    'adults' => 2,
                ],
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Not enough capacity available',
            ]);
    }

    /**
     * Test user can create booking from hold.
     */
    public function test_user_can_create_booking_from_hold(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
        ]);
        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => $user->id,
            'status' => HoldStatus::ACTIVE,
            'price_snapshot' => 150.00,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings', [
                'hold_id' => $hold->id,
                'traveler_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                ],
                'billing_contact' => [
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                ],
                'billing_country_code' => 'CA',
                'billing_city' => 'Toronto',
                'billing_postal_code' => 'M5H 2N2',
                'billing_address_line1' => '123 Main St',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'booking' => [
                    'id',
                    'booking_number',
                    'status',
                    'total_amount',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'availability_slot_id' => $slot->id,
            'status' => BookingStatus::PENDING_PAYMENT->value,
        ]);

        // Hold should be marked as completed
        $hold->refresh();
        $this->assertEquals(HoldStatus::COMPLETED, $hold->status);
    }

    /**
     * Test booking creation fails with expired hold.
     */
    public function test_booking_creation_fails_with_expired_hold(): void
    {
        // Arrange
        $user = User::factory()->create();
        $hold = BookingHold::factory()->expired()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings', [
                'hold_id' => $hold->id,
                'traveler_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                ],
                'billing_contact' => [
                    'email' => 'john@example.com',
                ],
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Hold has expired',
            ]);
    }

    /**
     * Test user can pay for booking and confirm it.
     */
    public function test_user_can_pay_for_booking(): void
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
        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => BookingStatus::CONFIRMED->value,
            ]);

        $booking->refresh();
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);
        $this->assertNotNull($booking->confirmed_at);
    }

    /**
     * Test user can cancel confirmed booking.
     */
    public function test_user_can_cancel_booking(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/cancel", [
                'reason' => 'Change of plans',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => BookingStatus::CANCELLED->value,
            ]);

        $booking->refresh();
        $this->assertEquals(BookingStatus::CANCELLED, $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertEquals('Change of plans', $booking->cancellation_reason);
    }

    /**
     * Test user can only cancel their own booking.
     */
    public function test_user_can_only_cancel_own_booking(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user1->id,
        ]);

        // Act
        $response = $this->actingAs($user2)
            ->postJson("/api/v1/bookings/{$booking->id}/cancel", [
                'reason' => 'Change of plans',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test user can view their bookings.
     */
    public function test_user_can_view_their_bookings(): void
    {
        // Arrange
        $user = User::factory()->create();
        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Create booking for another user (should not be returned)
        Booking::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/bookings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'bookings');
    }

    /**
     * Test user can view single booking details.
     */
    public function test_user_can_view_booking_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/v1/bookings/{$booking->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'booking' => [
                    'id',
                    'booking_number',
                    'status',
                    'total_amount',
                    'listing',
                    'availability_slot',
                ],
            ]);
    }

    /**
     * Test guest can access booking via magic token.
     */
    public function test_guest_can_access_booking_via_magic_token(): void
    {
        // Arrange
        $booking = Booking::factory()->confirmed()->withMagicToken()->create([
            'user_id' => null,
            'session_id' => 'guest-session',
        ]);

        // Act
        $response = $this->getJson("/api/v1/bookings/{$booking->booking_number}?token={$booking->magic_token}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'booking' => [
                    'id',
                    'booking_number',
                    'status',
                ],
            ]);
    }

    /**
     * Test guest cannot access booking with invalid magic token.
     */
    public function test_guest_cannot_access_booking_with_invalid_token(): void
    {
        // Arrange
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => null,
        ]);

        // Act
        $response = $this->getJson("/api/v1/bookings/{$booking->booking_number}?token=invalid-token");

        // Assert
        $response->assertStatus(403);
    }
}
