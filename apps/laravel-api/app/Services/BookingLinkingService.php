<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BookingAlreadyLinkedException;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\EmailMismatchException;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingLinkingService
{
    /**
     * Find all bookings that can be claimed by the user.
     * Matches by email in billing_contact or travelers array.
     *
     * @return Collection<int, Booking>
     */
    public function findClaimableBookings(User $user): Collection
    {
        return Booking::query()
            ->whereNull('user_id') // Only guest bookings
            ->where(function ($query) use ($user) {
                // Match by billing_contact email
                $query->whereJsonContains('billing_contact->email', $user->email)
                    // OR match by travelers email
                    ->orWhereJsonContains('travelers', [['email' => $user->email]]);
            })
            ->with(['listing', 'availabilitySlot'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Link selected bookings to the user by email verification.
     * Updates bookings with user_id and linking metadata.
     * Uses database transaction with row locking to prevent race conditions.
     *
     * @param  array  $bookingIds  Array of booking IDs to link (empty = link all matching)
     * @return array{linked: int, bookings: Collection}
     */
    public function linkBookingsByEmail(User $user, array $bookingIds = []): array
    {
        return DB::transaction(function () use ($user, $bookingIds) {
            $query = Booking::query()
                ->whereNull('user_id')
                ->where(function ($query) use ($user) {
                    $query->whereJsonContains('billing_contact->email', $user->email)
                        ->orWhereJsonContains('travelers', [['email' => $user->email]]);
                })
                ->lockForUpdate(); // Prevent concurrent modifications

            // If specific IDs provided, filter to those only
            if (! empty($bookingIds)) {
                $query->whereIn('id', $bookingIds);
            }

            $bookings = $query->get();
            $linkedCount = 0;

            // Link each booking
            foreach ($bookings as $booking) {
                // Verify still unlinked (could have changed while waiting for lock)
                if ($booking->user_id !== null) {
                    Log::warning('Booking already linked, skipping', [
                        'booking_id' => $booking->id,
                        'existing_user_id' => $booking->user_id,
                    ]);
                    continue;
                }

                $booking->update([
                    'user_id' => $user->id,
                    'linked_at' => now(),
                    'linked_method' => 'auto',
                ]);

                $linkedCount++;

                // Log the linking activity for audit trail
                Log::info('Booking automatically linked to user account', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'linked_method' => 'auto',
                ]);
            }

            return [
                'linked' => $linkedCount,
                'bookings' => $bookings->filter(fn ($b) => $b->user_id === $user->id),
            ];
        });
    }

    /**
     * Manually claim a booking by booking number.
     * Verifies that the user's email matches the booking email.
     *
     * @throws BookingNotFoundException
     * @throws BookingAlreadyLinkedException
     * @throws EmailMismatchException
     */
    public function linkBookingByNumber(User $user, string $bookingNumber): Booking
    {
        $booking = Booking::where('booking_number', $bookingNumber)->first();

        if (! $booking) {
            Log::warning('Booking claim failed: not found', [
                'user_id' => $user->id,
                'booking_number' => $bookingNumber,
            ]);

            throw new BookingNotFoundException;
        }

        // Check if booking is already linked to a user
        if ($booking->user_id) {
            Log::warning('Booking claim failed: already linked', [
                'user_id' => $user->id,
                'booking_number' => $bookingNumber,
                'existing_user_id' => $booking->user_id,
            ]);

            throw new BookingAlreadyLinkedException;
        }

        // Verify email match
        $emailMatches = $this->verifyEmailMatch($booking, $user->email);

        if (! $emailMatches) {
            // SECURITY: Log potential claim fraud attempts
            Log::warning('Booking claim failed: email mismatch', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'booking_number' => $bookingNumber,
                'booking_id' => $booking->id,
            ]);

            throw new EmailMismatchException;
        }

        // Link the booking
        $booking->update([
            'user_id' => $user->id,
            'linked_at' => now(),
            'linked_method' => 'claimed',
        ]);

        // Log the manual claim for audit trail
        Log::info('Booking manually claimed by user', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'linked_method' => 'claimed',
        ]);

        return $booking->fresh(['listing', 'availabilitySlot']);
    }

    /**
     * Verify if the user's email matches the booking email.
     */
    private function verifyEmailMatch(Booking $booking, string $email): bool
    {
        // Check billing_contact email
        if (isset($booking->billing_contact['email']) &&
            strtolower($booking->billing_contact['email']) === strtolower($email)) {
            return true;
        }

        // Check travelers array
        $travelers = $booking->travelers ?? [];

        foreach ($travelers as $traveler) {
            if (isset($traveler['email']) &&
                strtolower($traveler['email']) === strtolower($email)) {
                return true;
            }
        }

        // Check legacy traveler_info
        if (isset($booking->traveler_info['email']) &&
            strtolower($booking->traveler_info['email']) === strtolower($email)) {
            return true;
        }

        return false;
    }

    /**
     * Unlink a booking from a user (admin action).
     */
    public function unlinkBooking(Booking $booking): Booking
    {
        $previousUserId = $booking->user_id;

        $booking->update([
            'user_id' => null,
            'linked_at' => null,
            'linked_method' => null,
        ]);

        // Log the unlinking for audit trail
        Log::warning('Booking unlinked from user account', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'previous_user_id' => $previousUserId,
        ]);

        return $booking->fresh();
    }

    /**
     * Get statistics about linkable bookings for a user.
     *
     * @return array{total: int, by_status: array}
     */
    public function getLinkingStats(User $user): array
    {
        $claimableBookings = $this->findClaimableBookings($user);

        $stats = [
            'total' => $claimableBookings->count(),
            'by_status' => [],
        ];

        // Group by status
        foreach ($claimableBookings as $booking) {
            $status = $booking->status->value;

            if (! isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;
        }

        return $stats;
    }
}
