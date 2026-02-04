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
     * Check if user is the vendor who owns the listing for this booking.
     */
    private function vendorOwnsListing(User $user, Booking $booking): bool
    {
        return $booking->listing && $booking->listing->vendor_id === $user->id;
    }

    /**
     * Determine if the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Traveler who made the booking
        if ($this->userOwnsBooking($user, $booking)) {
            return true;
        }

        // Vendor who owns the listing
        if ($this->vendorOwnsListing($user, $booking)) {
            return true;
        }

        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update booking participants.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Traveler who made the booking
        if ($this->userOwnsBooking($user, $booking)) {
            return true;
        }

        // Vendor who owns the listing (can update booking status, mark as paid, etc.)
        if ($this->vendorOwnsListing($user, $booking)) {
            return true;
        }

        // Admin can update all
        if ($user->isAdmin()) {
            return true;
        }

        return false;
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
