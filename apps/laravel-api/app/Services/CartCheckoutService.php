<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartPayment;
use App\Models\User;
use App\Services\Payment\PaymentGatewayManager;
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
        protected ?EmailLogService $emailLogService = null
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
            PaymentMethod::CARD,
            PaymentMethod::CLICK_TO_PAY,
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
        ]);

        // Convert the hold
        $hold->convert();

        return $booking;
    }

    /**
     * Send confirmation emails for all bookings.
     */
    protected function sendConfirmationEmails(array $bookings): void
    {
        foreach ($bookings as $booking) {
            $email = $booking->getPrimaryEmail();

            if ($email) {
                if ($this->emailLogService) {
                    $this->emailLogService->queue(
                        $email,
                        new BookingConfirmationMail($booking),
                        $booking
                    );
                } else {
                    // Fallback for backward compatibility
                    \Illuminate\Support\Facades\Mail::to($email)->queue(new BookingConfirmationMail($booking));
                }
            }
        }
    }

    /**
     * Get gateway name for payment method.
     */
    protected function getGatewayForMethod(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::CARD,
            PaymentMethod::CLICK_TO_PAY,
            PaymentMethod::MOCK,
            PaymentMethod::FREE => 'mock',
            PaymentMethod::BANK_TRANSFER,
            PaymentMethod::CASH_ON_ARRIVAL,
            PaymentMethod::OFFLINE => 'offline',
        };
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
