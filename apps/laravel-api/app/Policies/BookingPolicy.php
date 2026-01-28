<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Check if user owns the booking via user_id OR email match.
     */
    private function userOwnsBooking(User $user, Booking $booking): bool
    {
        // Direct ownership (user_id matches)
        if ($user->id === $booking->user_id) {
            return true;
        }

        // Email-based access (guest bookings with matching email)
        $billingEmail = $booking->billing_contact['email'] ?? null;
        if ($billingEmail && strtolower($user->email) === strtolower($billingEmail)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $this->userOwnsBooking($user, $booking);
    }

    /**
     * Determine if the user can update booking participants.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $this->userOwnsBooking($user, $booking);
    }

    /**
     * Determine if the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $this->userOwnsBooking($user, $booking) && $booking->canBeCancelled();
    }

    /**
     * Determine if the user can pay for the booking.
     */
    public function pay(User $user, Booking $booking): bool
    {
        return $this->userOwnsBooking($user, $booking) && ! $booking->isConfirmed() && ! $booking->isCancelled();
    }
}
