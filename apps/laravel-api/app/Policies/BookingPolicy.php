<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine if the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    /**
     * Determine if the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id && $booking->canBeCancelled();
    }

    /**
     * Determine if the user can pay for the booking.
     */
    public function pay(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id && ! $booking->isConfirmed() && ! $booking->isCancelled();
    }
}
