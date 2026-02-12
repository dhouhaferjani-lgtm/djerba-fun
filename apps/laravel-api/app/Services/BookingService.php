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
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly ExtrasService $extrasService,
        private readonly EmailLogService $emailLogService
    ) {}

    /**
     * Create a booking from a hold.
     * Supports both authenticated users and guest checkout via session_id.
     * SIMPLIFIED: Only requires email, traveler details collected post-payment.
     *
     * @param  BookingHold  $hold  The hold to convert to a booking
     * @param  array  $travelers  Array with minimal contact info (email required, name/phone optional)
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

            // FALLBACK: If no user_id but email matches an existing user, auto-link
            // This provides a safety net if OptionalAuth failed to set the user
            if ($userId === null && ! empty($normalizedTravelers)) {
                $primaryEmail = $normalizedTravelers[0]['email'] ?? null;

                if ($primaryEmail) {
                    $userByEmail = User::whereRaw('LOWER(email) = ?', [strtolower($primaryEmail)])->first();

                    if ($userByEmail) {
                        $userId = $userByEmail->id;
                        Log::info('BookingService: Auto-linked booking to user by email fallback', [
                            'user_id' => $userId,
                            'email' => $primaryEmail,
                        ]);
                    }
                }
            }

            // Validate that at least one traveler with email is provided
            if (empty($normalizedTravelers)) {
                throw new \InvalidArgumentException('At least one traveler with email is required');
            }

            $primaryTraveler = $normalizedTravelers[0];

            // Validate email presence (REQUIRED for all bookings)
            if (empty($primaryTraveler['email'])) {
                throw new \InvalidArgumentException('Email is required for booking');
            }

            // Extract minimal contact info (email is REQUIRED, everything else optional)
            $billingContact = [
                'email' => $primaryTraveler['email'], // REQUIRED
                'first_name' => $primaryTraveler['first_name'] ?? null,
                'last_name' => $primaryTraveler['last_name'] ?? null,
                'phone' => $primaryTraveler['phone'] ?? null,
            ];

            // Determine traveler details status based on listing configuration
            $listing = $hold->listing;
            $travelerDetailsStatus = $listing?->requiresTravelerNames()
                ? 'pending'
                : 'not_required';

            // Extract billing address if provided in travelers data
            $billingAddress = null;

            if (isset($primaryTraveler['billing_address'])) {
                $billingAddress = $primaryTraveler['billing_address'];
            }

            // Calculate final pricing with billing address context
            $pricing = $this->calculateTotalAmount($hold, $extras);

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
                'total_amount' => is_array($pricing) ? $pricing['total'] : $pricing,
                'currency' => $hold->currency ?? request()->attributes->get('user_currency', 'TND'),
                'locale' => auth()->user()?->preferred_locale ?? app()->getLocale(),
                'status' => BookingStatus::PENDING_PAYMENT,
                'traveler_info' => $primaryTraveler, // Backward compatibility
                'travelers' => $normalizedTravelers, // Full travelers array
                'extras' => $extras,
                'billing_contact' => $billingContact,
                'billing_country_code' => $billingAddress['country_code'] ?? null,
                'billing_city' => $billingAddress['city'] ?? null,
                'billing_postal_code' => $billingAddress['postal_code'] ?? null,
                'billing_address_line1' => $billingAddress['address_line1'] ?? null,
                'billing_address_line2' => $billingAddress['address_line2'] ?? null,
                'pricing_snapshot' => is_array($pricing)
                    ? $this->capturePricingSnapshot($hold, $billingAddress, $pricing)
                    : null,
                'traveler_details_status' => $travelerDetailsStatus,
            ]);

            // Create empty participant records (names to be filled post-payment)
            $this->createEmptyParticipantRecords($booking, $hold);

            // Create booking extras records if extras are selected
            if (! empty($extras)) {
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
     * Create empty participant records for a booking.
     * Participant names will be collected post-payment if required by the listing.
     */
    private function createEmptyParticipantRecords(Booking $booking, BookingHold $hold): void
    {
        $breakdown = $hold->person_type_breakdown ?? [];

        // If breakdown is available, create participants with person types
        if (! empty($breakdown)) {
            foreach ($breakdown as $personType => $count) {
                for ($i = 0; $i < $count; $i++) {
                    BookingParticipant::create([
                        'booking_id' => $booking->id,
                        'person_type' => $personType,
                        // Names, email, phone intentionally left null - to be filled post-payment
                    ]);
                }
            }
        } else {
            // No breakdown, create based on quantity only
            for ($i = 0; $i < $booking->quantity; $i++) {
                BookingParticipant::create([
                    'booking_id' => $booking->id,
                    // Names, email, phone intentionally left null - to be filled post-payment
                ]);
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
        return array_map(fn ($t) => $this->normalizeTravelerKeys($t), $travelers);
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
                if (! $bookingExtra->inventory_reserved && $bookingExtra->extra?->track_inventory) {
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
            $this->emailLogService->queue($email, new BookingConfirmationMail($booking), $booking);
        }

        // Notify vendor of new confirmed booking
        try {
            $vendor = $booking->listing?->vendor;
            if ($vendor) {
                $listingTitle = $booking->listing->getTranslation('title', 'en') ?: $booking->listing->getTranslation('title', 'fr') ?: 'Untitled';
                if (is_array($listingTitle)) {
                    $listingTitle = reset($listingTitle) ?: 'Untitled';
                }
                $vendor->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => \Filament\Notifications\DatabaseNotification::class,
                    'data' => Notification::make()
                        ->title('New Booking Confirmed')
                        ->icon('heroicon-o-check-circle')
                        ->body("Booking {$booking->booking_number} for \"{$listingTitle}\" has been confirmed.")
                        ->actions([
                            NotificationAction::make('view')
                                ->label('View Booking')
                                ->url("/vendor/bookings/{$booking->id}")
                                ->button(),
                        ])
                        ->getDatabaseMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send booking confirmed notification to vendor', ['error' => $e->getMessage()]);
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
            $this->emailLogService->queue($email, new BookingCancellationMail($booking), $booking);
        }

        // Notify vendor of booking cancellation
        try {
            $vendor = $booking->listing?->vendor;
            if ($vendor) {
                $listingTitle = $booking->listing->getTranslation('title', 'en') ?: $booking->listing->getTranslation('title', 'fr') ?: 'Untitled';
                if (is_array($listingTitle)) {
                    $listingTitle = reset($listingTitle) ?: 'Untitled';
                }
                $vendor->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => \Filament\Notifications\DatabaseNotification::class,
                    'data' => Notification::make()
                        ->title('Booking Cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->body("Booking {$booking->booking_number} for \"{$listingTitle}\" has been cancelled." . ($reason ? " Reason: {$reason}" : ''))
                        ->actions([
                            NotificationAction::make('view')
                                ->label('View Booking')
                                ->url("/vendor/bookings/{$booking->id}")
                                ->button(),
                        ])
                        ->getDatabaseMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send booking cancellation notification to vendor', ['error' => $e->getMessage()]);
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
     *
     * @return array{total: float, currency: string}
     */
    private function calculateTotalAmount(BookingHold $hold, array $extras): array
    {
        $baseAmount = 0;
        $listing = $hold->slot?->listing;
        $personTypeBreakdown = $hold->person_type_breakdown ?? [];

        // Try to calculate using person_type_breakdown (most accurate)
        if (! empty($personTypeBreakdown) && $listing) {
            $pricing = $listing->pricing ?? [];
            // Support both camelCase and snake_case (database uses snake_case)
            $personTypes = $pricing['person_types'] ?? $pricing['personTypes'] ?? [];

            // Get base price for fallback (support both camelCase and snake_case)
            $basePrice = $pricing['displayPrice']
                ?? $pricing['tndPrice']
                ?? $pricing['tnd_price']
                ?? $pricing['eur_price']
                ?? $hold->slot?->base_price
                ?? 0;
            $basePrice = (float) $basePrice;

            if (! empty($personTypes) && is_array($personTypes)) {
                // Calculate using person type prices
                foreach ($personTypeBreakdown as $personTypeKey => $quantity) {
                    // Find the person type definition
                    $personType = collect($personTypes)->firstWhere('key', $personTypeKey);

                    if ($personType) {
                        // Get price for the specific currency (respect hold's currency)
                        $holdCurrency = $hold->currency ?? 'TND';
                        if ($holdCurrency === 'TND') {
                            $price = $personType['tnd_price']
                                ?? $personType['tndPrice']
                                ?? $personType['displayPrice']
                                ?? $personType['price']
                                ?? $basePrice;
                        } else {
                            $price = $personType['eur_price']
                                ?? $personType['eurPrice']
                                ?? $personType['displayPrice']
                                ?? $personType['price']
                                ?? $basePrice;
                        }
                        $price = (float) $price;
                        $baseAmount += $price * (int) $quantity;
                    } else {
                        // Person type not found, use base price
                        $baseAmount += $basePrice * (int) $quantity;
                    }
                }
            } else {
                // No person types defined, use base price
                $baseAmount = $basePrice * $hold->quantity;
            }
        } else {
            // Fallback: use base_price from slot or listing (support both camelCase and snake_case)
            $pricePerUnit = (float) ($hold->slot?->base_price
                ?? $listing?->pricing['displayPrice']
                ?? $listing?->pricing['tndPrice']
                ?? $listing?->pricing['tnd_price']
                ?? $listing?->pricing['eur_price']
                ?? 0);
            $baseAmount = $pricePerUnit * $hold->quantity;
        }

        $extrasAmount = 0;

        // Use ExtrasService for proper calculation
        if (! empty($extras)) {
            // Get currency from hold or listing pricing
            $currency = $hold->currency ?? $listing?->pricing['currency'] ?? 'TND';

            $calculation = $this->extrasService->calculateExtrasTotal(
                $extras,
                $personTypeBreakdown,
                $currency
            );
            $extrasAmount = $calculation['subtotal'] ?? 0;
        }

        $total = $baseAmount + $extrasAmount;

        return [
            'total' => $total,
            'currency' => $hold->currency ?? 'TND',
        ];
    }

    /**
     * Mark booking as paid (for offline/manual payment confirmation).
     * Creates a successful payment intent and confirms the booking.
     */
    public function markAsPaid(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::PENDING_PAYMENT) {
            throw new \RuntimeException('Only pending payment bookings can be marked as paid.');
        }

        return DB::transaction(function () use ($booking) {
            // Create a payment intent for the manual payment
            $paymentIntent = $booking->paymentIntents()->create([
                'amount' => $booking->total_amount,
                'currency' => $booking->currency ?? 'TND',
                'payment_method' => PaymentMethod::OFFLINE,
                'status' => PaymentStatus::SUCCEEDED,
                'gateway' => 'offline',
                'metadata' => [
                    'type' => 'manual_confirmation',
                    'confirmed_by' => auth()->id(),
                    'confirmed_at' => now()->toDateTimeString(),
                ],
                'paid_at' => now(),
            ]);

            // Confirm the booking
            return $this->confirmPayment($booking, $paymentIntent);
        });
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

        if (! $booking || ! $booking->hasMagicTokenValid()) {
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

    /**
     * Determine currency from country code.
     * Simple mapping for PPP pricing support.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @return string Currency code (TND, EUR, or USD)
     */
    private function determineCurrencyFromCountry(string $countryCode): string
    {
        // Tunisia uses TND
        if ($countryCode === 'TN') {
            return 'TND';
        }

        // EU countries use EUR
        $eurCountries = ['FR', 'DE', 'IT', 'ES', 'BE', 'NL', 'PT', 'AT', 'GR', 'IE', 'FI', 'SE', 'DK'];

        if (in_array($countryCode, $eurCountries, true)) {
            return 'EUR';
        }

        // Default to EUR for other countries
        return 'EUR';
    }

    /**
     * Capture a snapshot of pricing information for transparency and audit purposes.
     *
     * This method records the complete pricing journey from browsing to final purchase,
     * including any price changes that may have occurred due to billing address differences.
     *
     * @param  BookingHold  $hold  The booking hold containing browse-time pricing
     * @param  array|null  $billingAddress  The billing address provided at checkout
     * @param  array  $finalPricing  The final calculated pricing
     * @return array Comprehensive pricing snapshot
     */
    private function capturePricingSnapshot(
        BookingHold $hold,
        ?array $billingAddress,
        array $finalPricing
    ): array {
        $browseCountry = $hold->pricing_country_code;
        $billingCountry = $billingAddress['country_code'] ?? null;

        $browseCurrency = $hold->currency;

        // Determine final currency from billing country (if provided)
        $finalCurrency = $billingCountry
            ? $this->determineCurrencyFromCountry($billingCountry)
            : $browseCurrency;

        // Price changed if currency changed (PPP pricing transparency)
        // We only care about currency changes, not minor amount differences
        $currencyChanged = $browseCurrency !== $finalCurrency;
        $priceChanged = $currencyChanged;

        return [
            'browse_currency' => $browseCurrency,
            'browse_price' => $hold->price_snapshot,
            'browse_country' => $browseCountry,
            'browse_source' => $hold->pricing_source,
            'final_currency' => $finalCurrency,
            'final_price' => $finalPricing['total'],
            'final_country' => $billingCountry ?? $browseCountry,
            'price_changed' => $priceChanged,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
