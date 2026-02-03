<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression Tests for BulkParticipantController
 *
 * Tests the bulk apply functionality for updating participant names
 * across multiple bookings (cart checkout feature).
 */
class BulkParticipantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can bulk apply participants to their bookings.
     */
    public function test_authenticated_user_can_bulk_apply_participants(): void
    {
        // Arrange: Create user with multiple confirmed bookings
        $user = User::factory()->create();
        $listing = Listing::factory()->create();

        // Create two bookings with participants
        $booking1 = Booking::factory()->create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 2,
        ]);

        $booking2 = Booking::factory()->create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 2,
        ]);

        // Create empty participants for each booking
        foreach ([$booking1, $booking2] as $booking) {
            for ($i = 0; $i < 2; $i++) {
                BookingParticipant::create([
                    'booking_id' => $booking->id,
                    'first_name' => null,
                    'last_name' => null,
                ]);
            }
        }

        $participants = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
                'phone' => '+0987654321',
            ],
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings/participants/bulk-apply', [
                'booking_ids' => [$booking1->id, $booking2->id],
                'participants' => $participants,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success_count' => 2,
                'failed_count' => 0,
            ]);

        // Verify participants were updated in both bookings
        foreach ([$booking1, $booking2] as $booking) {
            $bookingParticipants = $booking->fresh()->participants()->orderBy('id')->get();

            $this->assertEquals('John', $bookingParticipants[0]->first_name);
            $this->assertEquals('Doe', $bookingParticipants[0]->last_name);
            $this->assertEquals('john@example.com', $bookingParticipants[0]->email);

            $this->assertEquals('Jane', $bookingParticipants[1]->first_name);
            $this->assertEquals('Doe', $bookingParticipants[1]->last_name);
            $this->assertEquals('jane@example.com', $bookingParticipants[1]->email);
        }
    }

    /**
     * Test user cannot bulk apply to another user's bookings.
     */
    public function test_user_cannot_bulk_apply_to_other_users_bookings(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $listing = Listing::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 1,
        ]);

        BookingParticipant::create([
            'booking_id' => $booking->id,
            'first_name' => null,
            'last_name' => null,
        ]);

        $participants = [
            [
                'first_name' => 'Hacker',
                'last_name' => 'Test',
            ],
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings/participants/bulk-apply', [
                'booking_ids' => [$booking->id],
                'participants' => $participants,
            ]);

        // Assert - should fail due to authorization
        $response->assertStatus(200)
            ->assertJson([
                'success_count' => 0,
                'failed_count' => 1,
            ]);

        // Verify participant was NOT updated
        $participant = $booking->fresh()->participants->first();
        $this->assertNull($participant->first_name);
    }

    /**
     * Test validation errors for invalid request.
     */
    public function test_bulk_apply_validates_required_fields(): void
    {
        $user = User::factory()->create();

        // Missing booking_ids
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings/participants/bulk-apply', [
                'participants' => [
                    ['first_name' => 'John', 'last_name' => 'Doe'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_ids']);

        // Missing participants
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings/participants/bulk-apply', [
                'booking_ids' => [Str::uuid()->toString()],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['participants']);

        // Missing participant names
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings/participants/bulk-apply', [
                'booking_ids' => [Str::uuid()->toString()],
                'participants' => [
                    ['email' => 'test@example.com'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['participants.0.first_name', 'participants.0.last_name']);
    }

    /**
     * Test guest user can bulk apply with session_id.
     */
    public function test_guest_can_bulk_apply_with_session_id(): void
    {
        // Arrange
        $sessionId = 'test-session-' . Str::random(16);
        $listing = Listing::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => null,
            'session_id' => $sessionId,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 1,
        ]);

        BookingParticipant::create([
            'booking_id' => $booking->id,
            'first_name' => null,
            'last_name' => null,
        ]);

        $participants = [
            [
                'first_name' => 'Guest',
                'last_name' => 'User',
            ],
        ];

        // Act - use guest endpoint with session header
        $response = $this->postJson('/api/v1/bookings/participants/bulk-apply/guest', [
            'booking_ids' => [$booking->id],
            'participants' => $participants,
        ], [
            'X-Session-ID' => $sessionId,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success_count' => 1,
                'failed_count' => 0,
            ]);

        // Verify participant was updated
        $participant = $booking->fresh()->participants->first();
        $this->assertEquals('Guest', $participant->first_name);
        $this->assertEquals('User', $participant->last_name);
    }

    /**
     * Test guest cannot bulk apply to bookings with different session_id.
     */
    public function test_guest_cannot_bulk_apply_to_other_session_bookings(): void
    {
        // Arrange
        $sessionId1 = 'session-1-' . Str::random(16);
        $sessionId2 = 'session-2-' . Str::random(16);
        $listing = Listing::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => null,
            'session_id' => $sessionId1,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 1,
        ]);

        BookingParticipant::create([
            'booking_id' => $booking->id,
            'first_name' => null,
            'last_name' => null,
        ]);

        $participants = [
            [
                'first_name' => 'Hacker',
                'last_name' => 'Test',
            ],
        ];

        // Act - try to access with different session
        $response = $this->postJson('/api/v1/bookings/participants/bulk-apply/guest', [
            'booking_ids' => [$booking->id],
            'participants' => $participants,
        ], [
            'X-Session-ID' => $sessionId2,
        ]);

        // Assert - should fail
        $response->assertStatus(200)
            ->assertJson([
                'success_count' => 0,
                'failed_count' => 1,
            ]);

        // Verify participant was NOT updated
        $participant = $booking->fresh()->participants->first();
        $this->assertNull($participant->first_name);
    }

    /**
     * Test partial success when some bookings fail.
     */
    public function test_bulk_apply_handles_partial_success(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $listing = Listing::factory()->create();

        // User's booking - should succeed
        $booking1 = Booking::factory()->create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 1,
        ]);

        // Other user's booking - should fail
        $booking2 = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'listing_id' => $listing->id,
            'status' => BookingStatus::CONFIRMED,
            'quantity' => 1,
        ]);

        foreach ([$booking1, $booking2] as $booking) {
            BookingParticipant::create([
                'booking_id' => $booking->id,
                'first_name' => null,
                'last_name' => null,
            ]);
        }

        $participants = [
            [
                'first_name' => 'Test',
                'last_name' => 'User',
            ],
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings/participants/bulk-apply', [
                'booking_ids' => [$booking1->id, $booking2->id],
                'participants' => $participants,
            ]);

        // Assert - one success, one failure
        $response->assertStatus(200)
            ->assertJson([
                'success_count' => 1,
                'failed_count' => 1,
            ]);

        // Verify only user's booking was updated
        $this->assertEquals('Test', $booking1->fresh()->participants->first()->first_name);
        $this->assertNull($booking2->fresh()->participants->first()->first_name);
    }
}
