<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartPayment;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\User;
use App\Services\Payment\PaymentGatewayManager;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartCheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected BookingService $bookingService,
        protected PaymentGatewayManager $gatewayManager,
        protected PriceCalculationService $priceService,
        protected EmailLogService $emailLogService
    ) {}

    /**
     * Initiate checkout for a cart.
     * Creates CartPayment and prepares for payment processing.
     *
     * @param  Cart  $cart  The cart to checkout
     * @param  PaymentMethod  $paymentMethod  The payment method to use
     * @param  array  $metadata  Additional metadata
     * @return array{payment: CartPayment, valid: bool, errors: array}
     */
    public function initiateCheckout(
        Cart $cart,
        PaymentMethod $paymentMethod,
        array $metadata = []
    ): array {
        // Validate cart
        $validation = $this->cartService->validateForCheckout($cart);

        if (! $validation['valid']) {
            return [
                'payment' => null,
                'valid' => false,
                'errors' => $validation['errors'],
            ];
        }

        // Calculate totals
        $totals = $this->cartService->calculateTotals($cart);

        // Mark cart as checking out
        $this->cartService->startCheckout($cart);

        // Create cart payment
        $payment = CartPayment::create([
            'cart_id' => $cart->id,
            'amount' => $totals['total'],
            'currency' => $totals['currency'],
            'status' => PaymentStatus::PENDING,
            'payment_method' => $paymentMethod->value,
            'gateway' => $this->getGatewayForMethod($paymentMethod),
            'metadata' => $metadata,
        ]);

        return [
            'payment' => $payment,
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * Process payment and create bookings.
     *
     * @param  CartPayment  $payment  The cart payment to process
     * @param  array  $paymentData  Payment-specific data (card details, etc.)
     * @return array{success: bool, bookings: array, message: string}
     */
    public function processPayment(CartPayment $payment, array $paymentData = []): array
    {
        $cart = $payment->cart;
        $cart->load(['items.hold', 'items.listing']);

        // Get the appropriate gateway
        $gatewayName = $payment->gateway;

        // Process based on payment method
        $paymentMethod = PaymentMethod::from($payment->payment_method);

        return match ($paymentMethod) {
            PaymentMethod::CLICK_TO_PAY => $this->processClictopayPayment($payment, $cart, $paymentData),
            PaymentMethod::CARD,
            PaymentMethod::MOCK => $this->processMockPayment($payment, $cart, $paymentData),
            PaymentMethod::BANK_TRANSFER,
            PaymentMethod::CASH_ON_ARRIVAL,
            PaymentMethod::OFFLINE => $this->processOfflinePayment($payment, $cart),
            PaymentMethod::FREE => $this->processFreePayment($payment, $cart),
        };
    }

    /**
     * Process mock/card payment (immediate confirmation).
     */
    protected function processMockPayment(CartPayment $payment, Cart $cart, array $paymentData): array
    {
        $payment->markAsProcessing();

        // Simulate payment processing delay
        usleep(500000); // 0.5 seconds

        // Mock: always succeed
        $gatewayId = 'mock_' . Str::random(16);

        return DB::transaction(function () use ($payment, $cart, $gatewayId) {
            // Mark payment as successful
            $payment->markAsSucceeded($gatewayId);

            // Create bookings for each cart item
            $bookings = $this->createBookingsFromCart($cart, $payment);

            // Complete the cart
            $cart->complete();

            // Send confirmation emails
            $this->sendConfirmationEmails($bookings);

            // Notify vendors of confirmed bookings
            $this->notifyVendorsOfConfirmedBookings($bookings);

            return [
                'success' => true,
                'bookings' => $bookings,
                'message' => 'Payment successful. Bookings confirmed!',
            ];
        });
    }

    /**
     * Process offline payment (pending until manually confirmed).
     */
    protected function processOfflinePayment(CartPayment $payment, Cart $cart): array
    {
        return DB::transaction(function () use ($payment, $cart) {
            // Mark payment as pending (requires manual confirmation)
            $payment->update(['status' => PaymentStatus::PENDING]);

            // Create bookings with pending payment status
            $bookings = $this->createBookingsFromCart($cart, $payment, BookingStatus::PENDING_PAYMENT);

            // Cart remains in checking_out status until payment confirmed

            return [
                'success' => true,
                'bookings' => $bookings,
                'message' => 'Booking created. Awaiting payment confirmation.',
            ];
        });
    }

    /**
     * Process free booking (no payment required).
     */
    protected function processFreePayment(CartPayment $payment, Cart $cart): array
    {
        return DB::transaction(function () use ($payment, $cart) {
            // Mark as succeeded (free)
            $payment->markAsSucceeded('free_' . Str::random(8));

            // Create confirmed bookings
            $bookings = $this->createBookingsFromCart($cart, $payment);

            // Complete cart
            $cart->complete();

            // Send confirmations
            $this->sendConfirmationEmails($bookings);

            return [
                'success' => true,
                'bookings' => $bookings,
                'message' => 'Booking confirmed!',
            ];
        });
    }

    /**
     * Create bookings from cart items.
     *
     * @param  Cart  $cart  The cart
     * @param  CartPayment  $payment  The cart payment
     * @param  BookingStatus  $status  Initial booking status
     * @return array<Booking>
     */
    protected function createBookingsFromCart(
        Cart $cart,
        CartPayment $payment,
        BookingStatus $status = BookingStatus::CONFIRMED
    ): array {
        $bookings = [];

        foreach ($cart->items as $item) {
            $booking = $this->createBookingFromItem($item, $cart, $payment, $status);
            $bookings[] = $booking;

            // Link booking to payment via pivot table
            $itemTotal = $item->getTotal();
            $payment->bookings()->attach($booking->id, ['amount' => $itemTotal]);
        }

        return $bookings;
    }

    /**
     * Create a booking from a cart item.
     */
    protected function createBookingFromItem(
        CartItem $item,
        Cart $cart,
        CartPayment $payment,
        BookingStatus $status
    ): Booking {
        $hold = $item->hold;

        // Build travelers array from primary contact
        $primaryContact = $item->primary_contact ?? [];
        $travelers = [
            [
                'first_name' => $primaryContact['first_name'] ?? '',
                'last_name' => $primaryContact['last_name'] ?? '',
                'email' => $primaryContact['email'] ?? '',
                'phone' => $primaryContact['phone'] ?? '',
                'is_primary' => true,
            ],
        ];

        // Add guest names if present
        if (! empty($item->guest_names)) {
            foreach ($item->guest_names as $guest) {
                $travelers[] = [
                    'first_name' => $guest['first_name'] ?? '',
                    'last_name' => $guest['last_name'] ?? '',
                    'person_type' => $guest['person_type'] ?? null,
                    'is_primary' => false,
                ];
            }
        }

        // Resolve user_id: Use cart's user_id, or fallback to email-based matching
        $userId = $cart->user_id;

        // FALLBACK: If cart has no user_id but primary email matches a user, link them
        // This provides a safety net for edge cases where the cart was created before login
        if ($userId === null && ! empty($primaryContact['email'])) {
            $userByEmail = User::whereRaw('LOWER(email) = ?', [strtolower($primaryContact['email'])])->first();

            if ($userByEmail) {
                $userId = $userByEmail->id;
                Log::info('CartCheckoutService: Auto-linked booking to user by email fallback', [
                    'user_id' => $userId,
                    'email' => $primaryContact['email'],
                ]);
            }
        }

        // Diagnostic logging to track user linking
        Log::info('CartCheckoutService: Creating booking from cart item', [
            'cart_id' => $cart->id,
            'cart_user_id' => $cart->user_id,
            'resolved_user_id' => $userId,
            'primary_email' => $primaryContact['email'] ?? null,
            'session_id' => $cart->session_id,
        ]);

        // Create the booking with billing_contact for email matching in dashboard
        $booking = Booking::create([
            'booking_number' => $this->bookingService->generateBookingNumber(),
            'user_id' => $userId,
            'session_id' => $cart->session_id,
            'listing_id' => $item->listing_id,
            'availability_slot_id' => $hold->slot_id,
            'cart_payment_id' => $payment->id,
            'quantity' => $item->quantity,
            'person_type_breakdown' => $item->person_type_breakdown,
            'total_amount' => $item->getTotal(),
            'currency' => $item->currency,
            'locale' => app()->getLocale(),
            'status' => $status,
            'traveler_info' => $travelers[0] ?? null, // Backward compatibility
            'travelers' => $travelers,
            'extras' => $item->extras,
            'confirmed_at' => $status === BookingStatus::CONFIRMED ? now() : null,
            // Add billing_contact for email-based matching in BookingController::index
            'billing_contact' => [
                'email' => $primaryContact['email'] ?? null,
                'phone' => $primaryContact['phone'] ?? null,
                'first_name' => $primaryContact['first_name'] ?? null,
                'last_name' => $primaryContact['last_name'] ?? null,
            ],
            // Add traveler details status for post-payment participant name collection
            'traveler_details_status' => $item->listing?->require_traveler_names ? 'pending' : 'not_required',
            // Add magic token for guest booking access
            'magic_token' => Str::random(64),
            'magic_token_expires_at' => now()->addDays(30),
        ]);

        // Convert the hold
        $hold->convert();

        // Create empty participant records for post-payment name collection
        $this->createEmptyParticipantRecords($booking, $item);

        return $booking;
    }

    /**
     * Create empty participant records for a cart booking.
     * Participant names will be collected post-payment if required by the listing.
     * This mirrors the behavior in BookingService::createEmptyParticipantRecords().
     */
    protected function createEmptyParticipantRecords(Booking $booking, CartItem $item): void
    {
        $breakdown = $item->person_type_breakdown ?? [];

        // If breakdown is available, create participants with person types
        if (! empty($breakdown)) {
            foreach ($breakdown as $personType => $count) {
                for ($i = 0; $i < $count; $i++) {
                    BookingParticipant::create([
                        'booking_id' => $booking->id,
                        'person_type' => $personType,
                        // Names intentionally left null - to be filled post-payment
                    ]);
                }
            }
        } else {
            // No breakdown, create based on quantity only
            for ($i = 0; $i < $booking->quantity; $i++) {
                BookingParticipant::create([
                    'booking_id' => $booking->id,
                    // Names intentionally left null - to be filled post-payment
                ]);
            }
        }
    }

    /**
     * Send confirmation emails for all bookings.
     */
    protected function sendConfirmationEmails(array $bookings): void
    {
        foreach ($bookings as $booking) {
            $email = $booking->getPrimaryEmail();

            if ($email) {
                $this->emailLogService->queue(
                    $email,
                    new BookingConfirmationMail($booking),
                    $booking
                );
            }
        }
    }

    /**
     * Notify vendors of confirmed bookings from cart checkout.
     * Groups by vendor to avoid spamming the same vendor with multiple notifications.
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
                } else {
                    $vendor->notifications()->create([
                        'id' => Str::uuid()->toString(),
                        'type' => \Filament\Notifications\DatabaseNotification::class,
                        'data' => Notification::make()
                            ->title("{$count} New Bookings Confirmed")
                            ->icon('heroicon-o-check-circle')
                            ->body("{$count} bookings for your listings have been confirmed.")
                            ->getDatabaseMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to send cart booking notification to vendor', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Get gateway name for payment method.
     */
    protected function getGatewayForMethod(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::CLICK_TO_PAY => $this->isClictopayEnabled() ? 'clicktopay' : 'mock',
            PaymentMethod::CARD,
            PaymentMethod::MOCK,
            PaymentMethod::FREE => 'mock',
            PaymentMethod::BANK_TRANSFER,
            PaymentMethod::CASH_ON_ARRIVAL,
            PaymentMethod::OFFLINE => 'offline',
        };
    }

    /**
     * Check if Clictopay gateway is enabled and configured.
     */
    protected function isClictopayEnabled(): bool
    {
        $gateway = PaymentGatewayModel::where('driver', 'clicktopay')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return false;
        }

        $config = $gateway->configuration ?? [];

        return ! empty($config['username']) && ! empty($config['password']);
    }

    /**
     * Process Clictopay payment (redirect-based flow).
     * Creates bookings first in PENDING_PAYMENT status, then redirects to Clictopay.
     */
    protected function processClictopayPayment(CartPayment $payment, Cart $cart, array $paymentData): array
    {
        // Check if Clictopay is enabled
        if (! $this->isClictopayEnabled()) {
            Log::info('Clictopay not enabled, falling back to mock payment');

            return $this->processMockPayment($payment, $cart, $paymentData);
        }

        $payment->markAsProcessing();

        try {
            // Create bookings FIRST in PENDING_PAYMENT status (like offline flow)
            // This is required because ClickToPayPaymentGateway::createIntent() expects a Booking object
            $bookings = DB::transaction(function () use ($payment, $cart) {
                return $this->createBookingsFromCart($cart, $payment, BookingStatus::PENDING_PAYMENT);
            });

            if (empty($bookings)) {
                throw new \Exception('Failed to create bookings from cart');
            }

            // Use the first booking for Clictopay registration
            $primaryBooking = $bookings[0];

            $gateway = $this->gatewayManager->gateway('clicktopay');

            // Create PaymentIntent via Clictopay (registers with API, gets formUrl)
            $intent = $gateway->createIntent($primaryBooking, [
                'payment_method' => PaymentMethod::CLICK_TO_PAY->value,
                'cart_payment_id' => $payment->id,
            ]);

            $redirectUrl = $gateway->getPaymentUrl($intent);

            if ($redirectUrl && $intent->status !== PaymentStatus::FAILED) {
                Log::info('Clictopay cart payment registered successfully', [
                    'cart_payment_id' => $payment->id,
                    'booking_id' => $primaryBooking->id,
                    'redirect_url' => $redirectUrl,
                ]);

                return [
                    'success' => true,
                    'requires_redirect' => true,
                    'redirect_url' => $redirectUrl,
                    'payment_id' => $payment->id,
                    'message' => 'Redirect to payment gateway required.',
                ];
            }

            // Registration failed - cancel pending bookings
            Log::warning('Clictopay registration failed, cancelling pending bookings', [
                'cart_payment_id' => $payment->id,
            ]);

            foreach ($bookings as $booking) {
                $booking->update(['status' => BookingStatus::CANCELLED]);
            }
            $payment->markAsFailed('Payment gateway initialization failed');

            return [
                'success' => false,
                'message' => 'Payment gateway initialization failed. Please try again.',
            ];
        } catch (\Exception $e) {
            Log::error('Clictopay cart payment error', [
                'cart_payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment processing failed. Please try again.',
            ];
        }
    }

    /**
     * Confirm an offline payment manually.
     */
    public function confirmOfflinePayment(CartPayment $payment, string $reference): array
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            return [
                'success' => false,
                'message' => 'Payment cannot be confirmed',
            ];
        }

        return DB::transaction(function () use ($payment, $reference) {
            // Mark payment as successful
            $payment->markAsSucceeded($reference);

            // Confirm all bookings
            foreach ($payment->bookings as $booking) {
                if ($booking->status === BookingStatus::PENDING_PAYMENT) {
                    $booking->update([
                        'status' => BookingStatus::CONFIRMED,
                        'confirmed_at' => now(),
                    ]);
                }
            }

            // Complete cart
            $payment->cart->complete();

            // Send confirmation emails
            $this->sendConfirmationEmails($payment->bookings->toArray());

            // Notify vendors of confirmed bookings
            $this->notifyVendorsOfConfirmedBookings($payment->bookings->toArray());

            return [
                'success' => true,
                'message' => 'Payment confirmed. Bookings activated.',
            ];
        });
    }

    /**
     * Cancel a cart checkout (before payment completed).
     */
    public function cancelCheckout(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            // Fail any pending payment
            if ($cart->payment && $cart->payment->isPending()) {
                $cart->payment->markAsFailed('Checkout cancelled by user');
            }

            // Release all holds
            foreach ($cart->items as $item) {
                if ($item->hold && $item->hold->isActive()) {
                    $item->hold->expire();
                }
            }

            // Abandon cart
            $cart->abandon();
        });
    }
}
