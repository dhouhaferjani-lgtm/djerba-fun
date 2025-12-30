<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\BookingStatus;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test booking belongs to user.
     */
    public function test_booking_belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        // Act
        $bookingUser = $booking->user;

        // Assert
        $this->assertInstanceOf(User::class, $bookingUser);
        $this->assertEquals($user->id, $bookingUser->id);
    }

    /**
     * Test booking belongs to listing.
     */
    public function test_booking_belongs_to_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $booking = Booking::factory()->create(['listing_id' => $listing->id]);

        // Act
        $bookingListing = $booking->listing;

        // Assert
        $this->assertInstanceOf(Listing::class, $bookingListing);
        $this->assertEquals($listing->id, $bookingListing->id);
    }

    /**
     * Test booking belongs to availability slot.
     */
    public function test_booking_belongs_to_availability_slot(): void
    {
        // Arrange
        $slot = AvailabilitySlot::factory()->create();
        $booking = Booking::factory()->create(['availability_slot_id' => $slot->id]);

        // Act
        $bookingSlot = $booking->availabilitySlot;

        // Assert
        $this->assertInstanceOf(AvailabilitySlot::class, $bookingSlot);
        $this->assertEquals($slot->id, $bookingSlot->id);
    }

    /**
     * Test is confirmed scope.
     */
    public function test_is_confirmed_scope(): void
    {
        // Arrange
        Booking::factory()->confirmed()->count(3)->create();
        Booking::factory()->create(['status' => BookingStatus::PENDING_PAYMENT]);
        Booking::factory()->cancelled()->count(2)->create();

        // Act
        $confirmedBookings = Booking::confirmed()->get();

        // Assert
        $this->assertCount(3, $confirmedBookings);
    }

    /**
     * Test is pending scope.
     */
    public function test_is_pending_scope(): void
    {
        // Arrange
        Booking::factory()->count(2)->create(['status' => BookingStatus::PENDING_PAYMENT]);
        Booking::factory()->confirmed()->create();

        // Act
        $pendingBookings = Booking::pending()->get();

        // Assert
        $this->assertCount(2, $pendingBookings);
    }

    /**
     * Test is cancelled scope.
     */
    public function test_is_cancelled_scope(): void
    {
        // Arrange
        Booking::factory()->cancelled()->count(2)->create();
        Booking::factory()->confirmed()->create();

        // Act
        $cancelledBookings = Booking::cancelled()->get();

        // Assert
        $this->assertCount(2, $cancelledBookings);
    }

    /**
     * Test is confirmed accessor.
     */
    public function test_is_confirmed_accessor(): void
    {
        // Arrange
        $confirmedBooking = Booking::factory()->confirmed()->create();
        $pendingBooking = Booking::factory()->create(['status' => BookingStatus::PENDING_PAYMENT]);

        // Assert
        $this->assertTrue($confirmedBooking->isConfirmed);
        $this->assertFalse($pendingBooking->isConfirmed);
    }

    /**
     * Test can be cancelled accessor.
     */
    public function test_can_be_cancelled_accessor(): void
    {
        // Arrange
        $confirmedBooking = Booking::factory()->confirmed()->create();
        $cancelledBooking = Booking::factory()->cancelled()->create();

        // Assert
        $this->assertTrue($confirmedBooking->canBeCancelled);
        $this->assertFalse($cancelledBooking->canBeCancelled);
    }

    /**
     * Test total with discount calculation.
     */
    public function test_total_with_discount_calculation(): void
    {
        // Arrange
        $booking = Booking::factory()->create([
            'total_amount' => 150.00,
            'discount_amount' => 20.00,
        ]);

        // Act
        $finalTotal = $booking->total_amount - $booking->discount_amount;

        // Assert
        $this->assertEquals(130.00, $finalTotal);
    }

    /**
     * Test booking number is unique.
     */
    public function test_booking_number_is_unique(): void
    {
        // Arrange
        $booking1 = Booking::factory()->create();
        $booking2 = Booking::factory()->create();

        // Assert
        $this->assertNotEquals($booking1->booking_number, $booking2->booking_number);
        $this->assertStringStartsWith('BK-', $booking1->booking_number);
        $this->assertStringStartsWith('BK-', $booking2->booking_number);
    }

    /**
     * Test magic token expiration check.
     */
    public function test_magic_token_expiration_check(): void
    {
        // Arrange
        $validBooking = Booking::factory()->create([
            'magic_token' => 'valid-token',
            'magic_token_expires_at' => now()->addDay(),
        ]);

        $expiredBooking = Booking::factory()->create([
            'magic_token' => 'expired-token',
            'magic_token_expires_at' => now()->subDay(),
        ]);

        // Assert
        $this->assertTrue($validBooking->magic_token_expires_at->isFuture());
        $this->assertTrue($expiredBooking->magic_token_expires_at->isPast());
    }

    /**
     * Test person type breakdown is cast to array.
     */
    public function test_person_type_breakdown_is_cast_to_array(): void
    {
        // Arrange
        $booking = Booking::factory()->create([
            'person_type_breakdown' => [
                'adults' => 2,
                'children' => 1,
            ],
        ]);

        // Act
        $breakdown = $booking->person_type_breakdown;

        // Assert
        $this->assertIsArray($breakdown);
        $this->assertEquals(2, $breakdown['adults']);
        $this->assertEquals(1, $breakdown['children']);
    }

    /**
     * Test pricing snapshot is stored correctly.
     */
    public function test_pricing_snapshot_is_stored_correctly(): void
    {
        // Arrange
        $snapshot = [
            'browse_currency' => 'TND',
            'browse_price' => 300.00,
            'final_currency' => 'TND',
            'final_price' => 300.00,
            'price_changed' => false,
        ];

        $booking = Booking::factory()->create([
            'pricing_snapshot' => $snapshot,
        ]);

        // Act
        $storedSnapshot = $booking->pricing_snapshot;

        // Assert
        $this->assertIsArray($storedSnapshot);
        $this->assertEquals('TND', $storedSnapshot['browse_currency']);
        $this->assertEquals(300.00, $storedSnapshot['browse_price']);
        $this->assertFalse($storedSnapshot['price_changed']);
    }

    /**
     * Test booking with extras.
     */
    public function test_booking_with_extras(): void
    {
        // Arrange
        $booking = Booking::factory()->withExtras()->create();

        // Act
        $extras = $booking->extras;

        // Assert
        $this->assertIsArray($extras);
        $this->assertNotEmpty($extras);
        $this->assertArrayHasKey('extra_id', $extras[0]);
        $this->assertArrayHasKey('quantity', $extras[0]);
    }

    /**
     * Test traveler details status.
     */
    public function test_traveler_details_status(): void
    {
        // Arrange
        $pendingBooking = Booking::factory()->create([
            'traveler_details_status' => 'pending',
        ]);

        $completeBooking = Booking::factory()->withTravelerDetails()->create();

        // Assert
        $this->assertEquals('pending', $pendingBooking->traveler_details_status);
        $this->assertEquals('complete', $completeBooking->traveler_details_status);
        $this->assertNotNull($completeBooking->traveler_details_completed_at);
    }
}
