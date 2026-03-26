<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmationMail;
use App\Models\CartPayment;
use App\Models\PaymentIntent;
use App\Services\BookingService;
use App\Services\EmailLogService;
use App\Services\Payment\ClickToPayPaymentGateway;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Handle Clictopay payment callbacks.
 *
 * This controller handles the redirect from Clictopay after the user
 * completes (or cancels) their payment on the Clictopay hosted page.
 */
class ClictopayCallbackController extends Controller
{
    public function __construct(
        private readonly ClickToPayPaymentGateway $gateway,
        private readonly BookingService $bookingService,
        private readonly ?EmailLogService $emailLogService = null
    ) {}

    /**
     * Handle Clictopay redirect callback.
     *
     * This endpoint is called when Clictopay redirects the user back
     * after payment completion or cancellation.
     *
     * Flow:
     * 1. Check idempotency (prevent duplicate processing)
     * 2. Verify the payment status with Clictopay API
     * 3. If successful, confirm the booking(s) atomically
     * 4. Redirect user to appropriate frontend page
     */
    public function callback(Request $request, PaymentIntent $intent): RedirectResponse
    {
        Log::info('Clictopay callback received', [
            'intent_id' => $intent->id,
            'booking_id' => $intent->booking_id,
            'query_params' => $request->query(),
        ]);

        // Get frontend URL from config
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        try {
            // IDEMPOTENCY CHECK: If payment already succeeded, just redirect without reprocessing
            if ($intent->status === PaymentStatus::SUCCEEDED) {
                Log::info('ClicToPay callback already processed (idempotent)', [
                    'intent_id' => $intent->id,
                ]);

                $cartPaymentId = $intent->metadata['cart_payment_id'] ?? null;

                if ($cartPaymentId) {
                    $cartPayment = CartPayment::find($cartPaymentId);

                    if ($cartPayment) {
                        $bookingIds = $cartPayment->bookings->pluck('id')->join(',');
                        $bookingNumbers = $cartPayment->bookings->pluck('booking_number')->join(',');

                        return redirect()->away(
                            $frontendUrl . '/checkout/success?' . http_build_query([
                                'bookings' => $bookingIds,
                                'booking_numbers' => $bookingNumbers,
                                'status' => 'confirmed',
                                'type' => 'cart',
                            ])
                        );
                    }
                }

                return redirect()->away(
                    $frontendUrl . '/checkout/success?' . http_build_query([
                        'booking' => $intent->booking->booking_number ?? '',
                        'status' => 'confirmed',
                    ])
                );
            }

            // Process payment (verify status with Clictopay API)
            $intent = $this->gateway->processPayment($intent, $request->all());

            // If payment succeeded, confirm the booking(s)
            if ($intent->isSuccessful()) {
                // Check if this is a cart payment (has cart_payment_id in metadata)
                $cartPaymentId = $intent->metadata['cart_payment_id'] ?? null;

                if ($cartPaymentId) {
                    // Cart payment flow - confirm all cart bookings with TRANSACTION PROTECTION
                    $cartPayment = CartPayment::where('id', $cartPaymentId)
                        ->lockForUpdate()  // Pessimistic lock to prevent race conditions
                        ->first();

                    // Only process if cart payment exists and not already succeeded
                    if ($cartPayment && $cartPayment->status !== PaymentStatus::SUCCEEDED) {
                        $confirmedBookings = [];

                        DB::transaction(function () use ($cartPayment, $intent, &$confirmedBookings) {
                            // Mark payment as successful
                            $cartPayment->markAsSucceeded($intent->gateway_id);

                            // Confirm all pending bookings
                            foreach ($cartPayment->bookings as $booking) {
                                if ($booking->status === BookingStatus::PENDING_PAYMENT) {
                                    $booking->update([
                                        'status' => BookingStatus::CONFIRMED,
                                        'confirmed_at' => now(),
                                    ]);
                                    $confirmedBookings[] = $booking;
                                }
                            }

                            // Complete cart
                            $cartPayment->cart->complete();
                        });

                        // Send emails and notifications AFTER transaction commits
                        if (! empty($confirmedBookings)) {
                            $this->sendConfirmationEmailsForBookings($confirmedBookings);
                            $this->notifyVendorsOfConfirmedBookings($confirmedBookings);
                        }

                        // Collect all booking IDs and numbers for the success page
                        $bookingIds = $cartPayment->bookings->pluck('id')->join(',');
                        $bookingNumbers = $cartPayment->bookings->pluck('booking_number')->join(',');

                        Log::info('Clictopay cart payment successful, all bookings confirmed', [
                            'intent_id' => $intent->id,
                            'cart_payment_id' => $cartPaymentId,
                            'booking_count' => $cartPayment->bookings->count(),
                            'booking_numbers' => $bookingNumbers,
                        ]);

                        // Redirect to cart success page with all booking info
                        return redirect()->away(
                            $frontendUrl . '/checkout/success?' . http_build_query([
                                'bookings' => $bookingIds,
                                'booking_numbers' => $bookingNumbers,
                                'status' => 'confirmed',
                                'type' => 'cart',
                            ])
                        );
                    } elseif ($cartPayment) {
                        // Cart payment already processed, just redirect
                        $bookingIds = $cartPayment->bookings->pluck('id')->join(',');
                        $bookingNumbers = $cartPayment->bookings->pluck('booking_number')->join(',');

                        return redirect()->away(
                            $frontendUrl . '/checkout/success?' . http_build_query([
                                'bookings' => $bookingIds,
                                'booking_numbers' => $bookingNumbers,
                                'status' => 'confirmed',
                                'type' => 'cart',
                            ])
                        );
                    }
                }

                // Single booking flow (existing behavior)
                $booking = $intent->booking;

                // Confirm the booking (sends confirmation email, etc.)
                $this->bookingService->confirmPayment($booking, $intent);

                Log::info('Clictopay payment successful, booking confirmed', [
                    'intent_id' => $intent->id,
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                ]);

                // Redirect to success page
                return redirect()->away(
                    $frontendUrl . '/checkout/success?' . http_build_query([
                        'booking' => $booking->booking_number,
                        'status' => 'confirmed',
                    ])
                );
            }

            // Payment failed or pending
            $reason = $this->getFailureReason($intent);

            Log::warning('Clictopay payment not successful', [
                'intent_id' => $intent->id,
                'status' => $intent->status->value,
                'reason' => $reason,
            ]);

            return redirect()->away(
                $frontendUrl . '/checkout/failure?' . http_build_query([
                    'intent' => $intent->id,
                    'reason' => $reason,
                    'booking' => $intent->booking->booking_number ?? null,
                ])
            );
        } catch (\Exception $e) {
            Log::error('Clictopay callback processing failed', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->away(
                $frontendUrl . '/checkout/failure?' . http_build_query([
                    'intent' => $intent->id,
                    'reason' => 'processing_error',
                    'booking' => $intent->booking->booking_number ?? null,
                ])
            );
        }
    }

    /**
     * Get a user-friendly failure reason from the payment intent.
     *
     * Uses hybrid matching:
     * 1. Check action_code for known integer codes (ISO 8583)
     * 2. Check action_code_description for keywords (handles unknown codes)
     * 3. Fall back to order_status_code generic mapping
     */
    private function getFailureReason(PaymentIntent $intent): string
    {
        $metadata = $intent->metadata ?? [];

        // PRIORITY 1: Check for specific action_code
        if (isset($metadata['action_code'])) {
            $actionCode = (int) $metadata['action_code'];
            $specificReason = $this->mapActionCodeToReason($actionCode);
            if ($specificReason !== null) {
                return $specificReason;
            }
        }

        // PRIORITY 2: Check action_code_description for keywords (handles unknown action codes)
        if (isset($metadata['action_code_description'])) {
            $description = strtolower($metadata['action_code_description']);
            $keywordReason = $this->mapDescriptionToReason($description);
            if ($keywordReason !== null) {
                return $keywordReason;
            }
        }

        // PRIORITY 3: Fall back to order status code mapping
        if (isset($metadata['order_status_code'])) {
            return match ($metadata['order_status_code']) {
                0 => 'payment_not_completed',
                3 => 'payment_reversed',
                4 => 'payment_refunded',
                6 => 'payment_declined',  // Status 6 is declined
                default => 'payment_failed',
            };
        }

        // PRIORITY 4: Check for other error types
        if (isset($metadata['registration_error'])) {
            return $metadata['registration_error_reason'] ?? 'gateway_error';
        }

        if (isset($metadata['verification_error'])) {
            return 'verification_error';
        }

        return 'payment_failed';
    }

    /**
     * Map ClicToPay action codes to user-friendly reason codes.
     *
     * Uses ISO 8583 standard response codes plus ClicToPay-specific codes.
     *
     * @see https://en.wikipedia.org/wiki/ISO_8583#Response_codes
     *
     * Known ClicToPay-specific codes: 0 (success), -1 (generic decline), -2007 (bank decline)
     * Discovered from production: 76 (rejected authorization)
     */
    private function mapActionCodeToReason(int $actionCode): ?string
    {
        $reason = match ($actionCode) {
            // Success - should not reach here
            0 => null,

            // ISO 8583 standard codes - Insufficient funds
            51 => 'insufficient_funds',     // Not sufficient funds
            52 => 'insufficient_funds',     // No checking account
            53 => 'insufficient_funds',     // No savings account

            // Expired card
            54 => 'expired_card',           // Expired card
            33 => 'expired_card',           // Expired card (pickup)

            // Invalid card
            14 => 'invalid_card',           // Invalid card number
            56 => 'invalid_card',           // No card record

            // Blocked/Restricted card
            41 => 'card_blocked',           // Lost card
            43 => 'card_blocked',           // Stolen card
            36 => 'card_blocked',           // Restricted card
            62 => 'card_blocked',           // Restricted card (serviced area)

            // Do not honor / Transaction not permitted
            5 => 'do_not_honor',            // Do not honor
            57 => 'do_not_honor',           // Transaction not permitted to cardholder
            58 => 'do_not_honor',           // Transaction not permitted to terminal
            59 => 'do_not_honor',           // Suspected fraud
            61 => 'do_not_honor',           // Exceeds withdrawal amount limit
            65 => 'do_not_honor',           // Exceeds withdrawal frequency limit

            // ClicToPay specific codes
            -2007 => 'payment_declined',    // Declined by issuing bank
            -1 => 'payment_declined',       // Generic decline
            76 => 'do_not_honor',           // Rejected Authorization - Issuer bank not able to process

            default => null,  // Unknown - try keyword matching
        };

        // Log unknown non-zero action codes for future mapping
        if ($reason === null && $actionCode !== 0) {
            Log::info('Unknown ClicToPay action code - add to mapping', [
                'action_code' => $actionCode,
            ]);
        }

        return $reason;
    }

    /**
     * Map ClicToPay action code description to reason via keyword matching.
     *
     * Handles cases where action_code is unknown but description contains keywords.
     * Keywords checked in French and English for robustness.
     */
    private function mapDescriptionToReason(string $description): ?string
    {
        // Insufficient funds
        if (str_contains($description, 'insuffi') ||
            str_contains($description, 'solde') ||
            str_contains($description, 'funds')) {
            return 'insufficient_funds';
        }

        // Invalid card
        if (str_contains($description, 'invalid') ||
            str_contains($description, 'invalide') ||
            str_contains($description, 'non valide')) {
            return 'invalid_card';
        }

        // Expired card
        if (str_contains($description, 'expir') ||
            str_contains($description, 'validité') ||
            str_contains($description, 'validity')) {
            return 'expired_card';
        }

        // Lost/Stolen card
        if (str_contains($description, 'lost') ||
            str_contains($description, 'perdu') ||
            str_contains($description, 'stolen') ||
            str_contains($description, 'volé')) {
            return 'card_blocked';
        }

        // Do not honor / Restricted / Rejected
        if (str_contains($description, 'honor') ||
            str_contains($description, 'restrict') ||
            str_contains($description, 'restreint') ||
            str_contains($description, 'rejected') ||
            str_contains($description, 'refusé')) {
            return 'do_not_honor';
        }

        // Log unmatched descriptions for future keyword additions
        Log::info('Unmatched ClicToPay action description - consider adding keywords', [
            'description' => $description,
        ]);

        return null;
    }

    /**
     * Send confirmation emails for a list of bookings.
     */
    private function sendConfirmationEmailsForBookings(array $bookings): void
    {
        foreach ($bookings as $booking) {
            $email = $booking->getPrimaryEmail();

            if ($email) {
                try {
                    if ($this->emailLogService) {
                        $this->emailLogService->queue($email, new BookingConfirmationMail($booking), $booking);
                    } else {
                        Mail::to($email)->queue(new BookingConfirmationMail($booking));
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to send booking confirmation email', [
                        'booking_id' => $booking->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Notify vendors of confirmed bookings from ClicToPay cart payments.
     *
     * Groups bookings by vendor to avoid spamming the same vendor with multiple notifications.
     */
    private function notifyVendorsOfConfirmedBookings(array $bookings): void
    {
        $vendorBookings = [];

        foreach ($bookings as $booking) {
            $vendorId = $booking->listing?->vendor_id;

            if ($vendorId) {
                $vendorBookings[$vendorId][] = $booking;
            }
        }

        foreach ($vendorBookings as $vendorId => $vendorBookingGroup) {
            try {
                $vendor = $vendorBookingGroup[0]->listing->vendor;

                if (! $vendor) {
                    continue;
                }

                $count = count($vendorBookingGroup);

                if ($count === 1) {
                    $booking = $vendorBookingGroup[0];
                    $listingTitle = $booking->listing->getTranslation('title', 'en') ?: $booking->listing->getTranslation('title', 'fr') ?: 'Untitled';

                    if (is_array($listingTitle)) {
                        $listingTitle = reset($listingTitle) ?: 'Untitled';
                    }
                    $vendor->notifications()->create([
                        'id' => Str::uuid()->toString(),
                        'type' => \Filament\Notifications\DatabaseNotification::class,
                        'data' => Notification::make()
                            ->title('Nouvelle réservation confirmée')
                            ->icon('heroicon-o-check-circle')
                            ->body("Réservation {$booking->booking_number} pour \"{$listingTitle}\" confirmée via ClicToPay.")
                            ->actions([
                                NotificationAction::make('view')
                                    ->label('Voir la réservation')
                                    ->url("/vendor/bookings/{$booking->id}")
                                    ->button(),
                            ])
                            ->getDatabaseMessage(),
                    ]);
                } else {
                    $bookingNumbers = collect($vendorBookingGroup)->pluck('booking_number')->join(', ');
                    $vendor->notifications()->create([
                        'id' => Str::uuid()->toString(),
                        'type' => \Filament\Notifications\DatabaseNotification::class,
                        'data' => Notification::make()
                            ->title("Nouvelle commande panier: {$count} réservations confirmées")
                            ->icon('heroicon-o-shopping-cart')
                            ->body("Réservations {$bookingNumbers} confirmées via ClicToPay.")
                            ->actions([
                                NotificationAction::make('view')
                                    ->label('Voir les réservations')
                                    ->url('/vendor/bookings')
                                    ->button(),
                            ])
                            ->getDatabaseMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to send booking notification to vendor', [
                    'vendor_id' => $vendorId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
