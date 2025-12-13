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
     */
    public function createFromHold(
        BookingHold $hold,
        array $travelerInfo,
        array $extras = []
    ): Booking {
        return DB::transaction(function () use ($hold, $travelerInfo, $extras) {
            // Create the booking
            $booking = Booking::create([
                'booking_number' => $this->generateBookingNumber(),
                'user_id' => $hold->user_id,
                'listing_id' => $hold->listing_id,
                'availability_slot_id' => $hold->availability_slot_id,
                'quantity' => $hold->quantity,
                'total_amount' => $this->calculateTotalAmount($hold, $extras),
                'currency' => $hold->currency ?? 'USD',
                'status' => BookingStatus::PENDING_PAYMENT,
                'traveler_info' => $travelerInfo,
                'extras' => $extras,
            ]);

            // Release the hold (it's now converted to a booking)
            $hold->delete();

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
