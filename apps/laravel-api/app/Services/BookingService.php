<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Mail\BookingCancellationMail;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingService
{
    /**
     * Create a booking from a hold.
     * Supports both authenticated users and guest checkout via session_id.
     *
     * @param  BookingHold  $hold  The hold to convert to a booking
     * @param  array  $travelerInfo  Traveler information
     * @param  array  $extras  Selected extras
     * @param  int|null  $authenticatedUserId  The authenticated user's ID (if logged in during checkout)
     */
    public function createFromHold(
        BookingHold $hold,
        array $travelerInfo,
        array $extras = [],
        ?int $authenticatedUserId = null
    ): Booking {
        return DB::transaction(function () use ($hold, $travelerInfo, $extras, $authenticatedUserId) {
            // Use authenticated user's ID if available, otherwise use hold's user_id
            $userId = $authenticatedUserId ?? $hold->user_id;

            // Create the booking (copy session_id from hold for guest checkout)
            $booking = Booking::create([
                'booking_number' => $this->generateBookingNumber(),
                'user_id' => $userId,
                'session_id' => $hold->session_id,
                'listing_id' => $hold->listing_id,
                'availability_slot_id' => $hold->slot_id,
                'quantity' => $hold->quantity,
                'total_amount' => $this->calculateTotalAmount($hold, $extras),
                'currency' => $hold->slot?->currency ?? 'EUR',
                'status' => BookingStatus::PENDING_PAYMENT,
                'traveler_info' => $travelerInfo,
                'extras' => $extras,
            ]);

            // Convert the hold (mark as converted, don't delete for audit trail)
            $hold->convert();

            return $booking;
        });
    }

    /**
     * Confirm a booking after successful payment.
     */
    public function confirmPayment(Booking $booking, PaymentIntent $intent): Booking
    {
        if ($intent->status !== PaymentStatus::SUCCEEDED) {
            throw new \RuntimeException('Payment intent must be successful to confirm booking.');
        }

        $booking->update([
            'status' => BookingStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        // Send confirmation email
        Mail::to($booking->traveler_info['email'])
            ->queue(new BookingConfirmationMail($booking));

        return $booking->fresh();
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        if (! $booking->canBeCancelled()) {
            throw new \RuntimeException('This booking cannot be cancelled.');
        }

        $booking->update([
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Send cancellation email
        Mail::to($booking->traveler_info['email'])
            ->queue(new BookingCancellationMail($booking));

        return $booking->fresh();
    }

    /**
     * Generate a unique booking number.
     */
    public function generateBookingNumber(): string
    {
        do {
            $prefix = 'GA';
            $date = now()->format('Ym');
            $random = strtoupper(Str::random(5));
            $bookingNumber = "{$prefix}-{$date}-{$random}";
        } while (Booking::where('booking_number', $bookingNumber)->exists());

        return $bookingNumber;
    }

    /**
     * Calculate total amount including extras.
     */
    private function calculateTotalAmount(BookingHold $hold, array $extras): float
    {
        $baseAmount = (float) $hold->price_per_unit * $hold->quantity;
        $extrasAmount = 0;

        foreach ($extras as $extra) {
            $extrasAmount += (float) ($extra['price'] ?? 0) * (int) ($extra['quantity'] ?? 1);
        }

        return $baseAmount + $extrasAmount;
    }

    /**
     * Mark booking as completed.
     */
    public function complete(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::CONFIRMED) {
            throw new \RuntimeException('Only confirmed bookings can be marked as completed.');
        }

        $booking->update([
            'status' => BookingStatus::COMPLETED,
        ]);

        return $booking->fresh();
    }

    /**
     * Mark booking as no-show.
     */
    public function markAsNoShow(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::CONFIRMED) {
            throw new \RuntimeException('Only confirmed bookings can be marked as no-show.');
        }

        $booking->update([
            'status' => BookingStatus::NO_SHOW,
        ]);

        return $booking->fresh();
    }
}
