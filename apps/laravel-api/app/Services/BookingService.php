<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Mail\BookingCancellationMail;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingParticipant;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly ?ExtrasService $extrasService = null
    ) {}

    /**
     * Create a booking from a hold.
     * Supports both authenticated users and guest checkout via session_id.
     * Accepts either single traveler info (legacy) or array of travelers.
     *
     * @param  BookingHold  $hold  The hold to convert to a booking
     * @param  array  $travelers  Array of traveler information (or single traveler for backward compatibility)
     * @param  array  $extras  Selected extras
     * @param  int|null  $authenticatedUserId  The authenticated user's ID (if logged in during checkout)
     */
    public function createFromHold(
        BookingHold $hold,
        array $travelers,
        array $extras = [],
        ?int $authenticatedUserId = null
    ): Booking {
        return DB::transaction(function () use ($hold, $travelers, $extras, $authenticatedUserId) {
            // Use authenticated user's ID if available, otherwise use hold's user_id
            $userId = $authenticatedUserId ?? $hold->user_id;

            // Normalize travelers: ensure it's an array of travelers
            $normalizedTravelers = $this->normalizeTravelers($travelers);
            $primaryTraveler = $normalizedTravelers[0] ?? [];

            // Extract billing contact from primary traveler
            $billingContact = [
                'first_name' => $primaryTraveler['first_name'] ?? null,
                'last_name' => $primaryTraveler['last_name'] ?? null,
                'email' => $primaryTraveler['email'] ?? null,
                'phone' => $primaryTraveler['phone'] ?? null,
            ];

            // Create the booking (copy session_id from hold for guest checkout)
            $booking = Booking::create([
                'booking_number' => $this->generateBookingNumber(),
                'user_id' => $userId,
                'session_id' => $hold->session_id,
                'magic_token' => Str::random(64),
                'magic_token_expires_at' => now()->addDays(30),
                'listing_id' => $hold->listing_id,
                'availability_slot_id' => $hold->slot_id,
                'quantity' => $hold->quantity,
                'person_type_breakdown' => $hold->person_type_breakdown,
                'total_amount' => $this->calculateTotalAmount($hold, $extras),
                'currency' => $hold->slot?->currency ?? 'EUR',
                'status' => BookingStatus::PENDING_PAYMENT,
                'traveler_info' => $primaryTraveler, // Backward compatibility
                'travelers' => $normalizedTravelers, // Full travelers array
                'extras' => $extras,
                'billing_contact' => $billingContact,
            ]);

            // Create participant records based on quantity and person type breakdown
            $this->createParticipantRecords($booking, $hold, $billingContact);

            // Create booking extras records (if extras service is available and extras are selected)
            if ($this->extrasService && !empty($extras)) {
                $personTypeBreakdown = $hold->person_type_breakdown ?? [];
                $currency = $hold->slot?->currency ?? 'EUR';

                $this->extrasService->createBookingExtras(
                    $booking,
                    $extras,
                    $personTypeBreakdown,
                    $currency,
                    reserveInventory: false // Don't reserve until payment confirmed
                );
            }

            // Convert the hold (mark as converted, don't delete for audit trail)
            $hold->convert();

            return $booking;
        });
    }

    /**
     * Create participant records for a booking.
     * Pre-populates the first participant with billing contact data.
     */
    private function createParticipantRecords(Booking $booking, BookingHold $hold, array $billingContact): void
    {
        $breakdown = $hold->person_type_breakdown ?? [];
        $participantIndex = 0;

        // If breakdown is available, create participants with person types
        if (!empty($breakdown)) {
            foreach ($breakdown as $personType => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $participantData = [
                        'booking_id' => $booking->id,
                        'person_type' => $personType,
                    ];

                    // Pre-populate first participant with billing contact
                    if ($participantIndex === 0) {
                        $participantData = array_merge($participantData, [
                            'first_name' => $billingContact['first_name'],
                            'last_name' => $billingContact['last_name'],
                            'email' => $billingContact['email'],
                            'phone' => $billingContact['phone'],
                        ]);
                    }

                    BookingParticipant::create($participantData);
                    $participantIndex++;
                }
            }
        } else {
            // No breakdown, create based on quantity only
            for ($i = 0; $i < $booking->quantity; $i++) {
                $participantData = [
                    'booking_id' => $booking->id,
                ];

                // Pre-populate first participant with billing contact
                if ($i === 0) {
                    $participantData = array_merge($participantData, [
                        'first_name' => $billingContact['first_name'],
                        'last_name' => $billingContact['last_name'],
                        'email' => $billingContact['email'],
                        'phone' => $billingContact['phone'],
                    ]);
                }

                BookingParticipant::create($participantData);
            }
        }
    }

    /**
     * Normalize travelers input to ensure consistent array format.
     * Handles both legacy single traveler and new array format.
     */
    private function normalizeTravelers(array $travelers): array
    {
        // Check if it's a single traveler (has first_name or firstName key)
        if (isset($travelers['first_name']) || isset($travelers['firstName'])) {
            return [$this->normalizeTravelerKeys($travelers)];
        }

        // It's already an array of travelers
        return array_map(fn($t) => $this->normalizeTravelerKeys($t), $travelers);
    }

    /**
     * Normalize traveler keys to snake_case for consistency.
     */
    private function normalizeTravelerKeys(array $traveler): array
    {
        $normalized = [];

        // Map camelCase to snake_case
        $keyMap = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'personType' => 'person_type',
            'specialRequests' => 'special_requests',
        ];

        foreach ($traveler as $key => $value) {
            $normalizedKey = $keyMap[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
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

        // Reserve inventory for extras now that payment is confirmed
        if ($this->extrasService) {
            foreach ($booking->bookingExtras as $bookingExtra) {
                if (!$bookingExtra->inventory_reserved && $bookingExtra->extra?->track_inventory) {
                    $reserved = $bookingExtra->extra->reserveInventory(
                        $bookingExtra->quantity,
                        $booking
                    );
                    if ($reserved) {
                        $bookingExtra->update(['inventory_reserved' => true]);
                    }
                }
            }
        }

        // Send confirmation email to primary traveler
        $email = $booking->getPrimaryEmail();
        if ($email) {
            Mail::to($email)->queue(new BookingConfirmationMail($booking));
        }

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

        // Release inventory for any reserved extras
        if ($this->extrasService) {
            $this->extrasService->releaseBookingExtrasInventory($booking);
        }

        // Send cancellation email to primary traveler
        $email = $booking->getPrimaryEmail();
        if ($email) {
            Mail::to($email)->queue(new BookingCancellationMail($booking));
        }

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
        // Get price from slot (base_price is stored in the availability_slot)
        $pricePerUnit = (float) ($hold->slot?->base_price ?? 0);
        $baseAmount = $pricePerUnit * $hold->quantity;
        $extrasAmount = 0;

        // Use ExtrasService for proper calculation if available
        if ($this->extrasService && !empty($extras)) {
            $currency = $hold->slot?->currency ?? 'EUR';
            $personTypeBreakdown = $hold->person_type_breakdown ?? [];

            $calculation = $this->extrasService->calculateExtrasTotal(
                $extras,
                $personTypeBreakdown,
                $currency
            );
            $extrasAmount = $calculation['subtotal'] ?? 0;
        } else {
            // Fallback for backward compatibility
            foreach ($extras as $extra) {
                $extrasAmount += (float) ($extra['price'] ?? 0) * (int) ($extra['quantity'] ?? 1);
            }
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

    /**
     * Find a booking by its magic token.
     */
    public function findByMagicToken(string $token): ?Booking
    {
        return Booking::where('magic_token', $token)->first();
    }

    /**
     * Validate a magic token and return the booking if valid.
     * Returns null if token doesn't exist or is expired.
     */
    public function validateMagicToken(string $token): ?Booking
    {
        $booking = $this->findByMagicToken($token);

        if (!$booking || !$booking->hasMagicTokenValid()) {
            return null;
        }

        return $booking;
    }

    /**
     * Regenerate magic token for a booking.
     * Used when the token has expired or user requests a new link.
     */
    public function regenerateMagicToken(Booking $booking): Booking
    {
        $booking->update([
            'magic_token' => Str::random(64),
            'magic_token_expires_at' => now()->addDays(30),
        ]);

        return $booking->fresh();
    }

    /**
     * Find booking by email and booking number for magic link recovery.
     */
    public function findByEmailAndBookingNumber(string $email, string $bookingNumber): ?Booking
    {
        return Booking::where('booking_number', $bookingNumber)
            ->whereJsonContains('billing_contact->email', $email)
            ->first();
    }
}
