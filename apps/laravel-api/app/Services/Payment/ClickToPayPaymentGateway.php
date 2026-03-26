<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clictopay SMT Payment Gateway for Tunisia.
 *
 * Integrates with Monétique Tunisie's Clictopay payment processor.
 * Supports both local Tunisian cards (CIB) and international Visa/Mastercard.
 *
 * @see https://mczen-technologies.github.io/clictopay-api/
 */
class ClickToPayPaymentGateway implements PaymentGateway
{
    private const TEST_BASE_URL = 'https://test.clictopay.com/payment/rest';

    private const PROD_BASE_URL = 'https://ipay.clictopay.com/payment/rest';

    private const TND_CURRENCY_CODE = 788; // ISO 4217 numeric code for TND

    /**
     * Configuration loaded from database.
     */
    private ?array $config = null;

    /**
     * Create a payment intent and register with Clictopay.
     *
     * This method:
     * 1. Creates a local PaymentIntent record
     * 2. Calls Clictopay's register.do API
     * 3. Stores the formUrl for frontend redirect
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent
    {
        $this->loadConfiguration();

        // Use cart_total if provided (for multi-booking cart payments), else use booking's payable amount
        $amount = $options['cart_total'] ?? $booking->getPayableAmount();

        // Convert amount to millimes (1 TND = 1000 millimes)
        $amountInMillimes = (int) round($amount * 1000);

        // Generate unique order number
        $orderNumber = $this->generateOrderNumber($booking);

        // Create pending payment intent
        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'currency' => $booking->currency ?? 'TND',
            'payment_method' => $options['payment_method'] ?? 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => null, // Will be set after register.do call
            'metadata' => [
                'order_number' => $orderNumber,
                'amount_in_millimes' => $amountInMillimes,
                'test_mode' => $this->isTestMode(),
                'created_at' => now()->toIso8601String(),
                'cart_payment_id' => $options['cart_payment_id'] ?? null,
                'all_booking_numbers' => $options['all_booking_numbers'] ?? [$booking->booking_number],
            ],
        ]);

        // Determine page view type (MOBILE or DESKTOP)
        $pageView = strtoupper($options['page_view'] ?? 'DESKTOP');

        // Call Clictopay register.do API
        try {
            $response = $this->registerPayment($intent, $orderNumber, $amountInMillimes, $pageView, $options);

            if (isset($response['orderId']) && isset($response['formUrl'])) {
                // Registration successful
                $intent->update([
                    'gateway_id' => $response['orderId'],
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'form_url' => $response['formUrl'],
                        'clictopay_order_id' => $response['orderId'],
                        'registered_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::info('Clictopay payment registered successfully', [
                    'intent_id' => $intent->id,
                    'booking_id' => $booking->id,
                    'clictopay_order_id' => $response['orderId'],
                    'amount' => $amount,
                    'test_mode' => $this->isTestMode(),
                ]);
            } else {
                // Registration failed
                $errorMessage = $response['errorMessage'] ?? 'Unknown error from Clictopay';
                $errorCode = $response['errorCode'] ?? null;
                $reason = $this->mapRegistrationErrorToReason($errorCode, $errorMessage);

                $intent->update([
                    'status' => PaymentStatus::FAILED,
                    'failed_at' => now(),
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'registration_error' => $errorMessage,
                        'error_code' => $errorCode,
                        'registration_error_reason' => $reason,
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::error('Clictopay registration failed', [
                    'intent_id' => $intent->id,
                    'booking_id' => $booking->id,
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
                    'error_reason' => $reason,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Clictopay registration exception', [
                'intent_id' => $intent->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $intent->update([
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
                'metadata' => array_merge($intent->metadata ?? [], [
                    'registration_error' => $e->getMessage(),
                    'registration_error_reason' => 'gateway_system_error',
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        return $intent->fresh();
    }

    /**
     * Process payment - verify status after redirect from Clictopay.
     *
     * Called when user is redirected back from Clictopay payment page.
     * Verifies the payment status with Clictopay API.
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent
    {
        $this->loadConfiguration();

        // Get order ID from intent or callback data
        $orderId = $intent->gateway_id ?? $data['orderId'] ?? null;

        if (! $orderId) {
            $intent->update([
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
                'metadata' => array_merge($intent->metadata ?? [], [
                    'error' => 'No Clictopay order ID available for verification',
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::error('Clictopay verification failed - no order ID', [
                'intent_id' => $intent->id,
            ]);

            return $intent->fresh();
        }

        try {
            $statusResponse = $this->getOrderStatusExtended($orderId);

            // Parse Clictopay orderStatus (per Manuel IntégrationV2.2.pdf):
            // 0 = Registered (payment not started)
            // 1 = Pre-authorized only (two-phase payment)
            // 2 = Deposited/Paid (SUCCESS)
            // 3 = Authorization cancelled (Reversed)
            // 4 = Transaction refunded
            // 5 = ACS authorization initiated (3DS in progress) - NOT a failure!
            // 6 = Authorization declined (FAILED)
            $orderStatus = $statusResponse['orderStatus'] ?? -1;
            $cardAuthInfo = $statusResponse['cardAuthInfo'] ?? [];

            Log::info('Clictopay extended status check', [
                'intent_id' => $intent->id,
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'action_code' => $statusResponse['actionCode'] ?? null,
            ]);

            if ($orderStatus === 2) {
                // Payment successful (deposited)
                $intent->update([
                    'status' => PaymentStatus::SUCCEEDED,
                    'paid_at' => now(),
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'verified_at' => now()->toIso8601String(),
                        'clictopay_status' => $statusResponse,
                        'auth_code' => $statusResponse['authCode'] ?? null,
                        'card_pan' => $cardAuthInfo['pan'] ?? ($statusResponse['Pan'] ?? null),
                        'cardholder_name' => $cardAuthInfo['cardholderName'] ?? null,
                        'card_expiration' => $cardAuthInfo['expiration'] ?? null,
                        'approval_code' => $statusResponse['approvalCode'] ?? null,
                        'action_code' => $statusResponse['actionCode'] ?? null,
                        'action_code_description' => $statusResponse['actionCodeDescription'] ?? null,
                    ]),
                ]);

                Log::info('Clictopay payment verified as successful', [
                    'intent_id' => $intent->id,
                    'booking_id' => $intent->booking_id,
                    'auth_code' => $statusResponse['authCode'] ?? null,
                    'card_pan' => $cardAuthInfo['pan'] ?? null,
                ]);
            } elseif ($orderStatus === 1) {
                // Pre-authorized only - payment in progress (two-phase)
                $intent->update([
                    'status' => PaymentStatus::PROCESSING,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'preauthorized_at' => now()->toIso8601String(),
                        'clictopay_status' => $statusResponse,
                    ]),
                ]);

                Log::info('Clictopay payment pre-authorized', [
                    'intent_id' => $intent->id,
                ]);
            } elseif ($orderStatus === 5) {
                // 3D Secure authentication in progress - user at bank's ACS page
                // This is NOT a failure - the user is authenticating with their bank
                $intent->update([
                    'status' => PaymentStatus::PROCESSING,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        '3ds_in_progress' => true,
                        '3ds_initiated_at' => now()->toIso8601String(),
                        'clictopay_status' => $statusResponse,
                    ]),
                ]);

                Log::info('Clictopay 3DS authentication in progress', [
                    'intent_id' => $intent->id,
                ]);
            } elseif ($orderStatus === 0) {
                // Still registered but not paid - user might have cancelled
                $intent->update([
                    'status' => PaymentStatus::PENDING,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'pending_reason' => 'Payment not completed by user',
                        'clictopay_status' => $statusResponse,
                    ]),
                ]);

                Log::info('Clictopay payment still pending (not completed)', [
                    'intent_id' => $intent->id,
                ]);
            } elseif ($orderStatus === 3) {
                // Authorization cancelled/reversed
                $intent->update([
                    'status' => PaymentStatus::REFUNDED,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'reversed_at' => now()->toIso8601String(),
                        'clictopay_status' => $statusResponse,
                        'order_status_code' => $orderStatus,
                    ]),
                ]);

                Log::info('Clictopay payment reversed/cancelled', [
                    'intent_id' => $intent->id,
                ]);
            } elseif ($orderStatus === 4) {
                // Transaction refunded
                $intent->update([
                    'status' => PaymentStatus::REFUNDED,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'refunded_at' => now()->toIso8601String(),
                        'clictopay_status' => $statusResponse,
                        'order_status_code' => $orderStatus,
                    ]),
                ]);

                Log::info('Clictopay payment refunded', [
                    'intent_id' => $intent->id,
                ]);
            } else {
                // Status 6 (declined) or other non-success status
                $intent->update([
                    'status' => PaymentStatus::FAILED,
                    'failed_at' => now(),
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'verification_failed_at' => now()->toIso8601String(),
                        'clictopay_status' => $statusResponse,
                        'order_status_code' => $orderStatus,
                        'action_code' => $statusResponse['actionCode'] ?? null,
                        'action_code_description' => $statusResponse['actionCodeDescription'] ?? null,
                    ]),
                ]);

                Log::warning('Clictopay payment failed or declined', [
                    'intent_id' => $intent->id,
                    'order_status' => $orderStatus,
                    'action_code' => $statusResponse['actionCode'] ?? null,
                    'action_code_description' => $statusResponse['actionCodeDescription'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Clictopay status verification exception', [
                'intent_id' => $intent->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            $intent->update([
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
                'metadata' => array_merge($intent->metadata ?? [], [
                    'verification_error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        return $intent->fresh();
    }

    /**
     * Refund a payment intent via Clictopay refund.do API.
     *
     * Falls back to manual processing if the API call fails.
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        $this->loadConfiguration();

        $refundAmountInMillimes = $amount ?? (int) round($intent->amount * 1000);
        $totalAmountInMillimes = (int) round($intent->amount * 1000);
        $isPartial = $refundAmountInMillimes !== $totalAmountInMillimes;
        $orderId = $intent->gateway_id;

        if (! $orderId) {
            return $this->markRefundAsManual($intent, $refundAmountInMillimes, $isPartial, 'No gateway order ID');
        }

        try {
            $response = $this->httpClient()
                ->asForm()
                ->post($this->getBaseUrl() . '/refund.do', [
                    'userName' => $this->config['username'] ?? '',
                    'password' => $this->config['password'] ?? '',
                    'orderId' => $orderId,
                    'amount' => $refundAmountInMillimes,
                ]);

            if ($response->failed()) {
                throw new \Exception('Clictopay refund.do HTTP error: ' . $response->status());
            }

            $data = $response->json() ?? [];
            $errorCode = $data['errorCode'] ?? -1;

            if ($errorCode === 0 || $errorCode === '0') {
                $newStatus = $isPartial ? PaymentStatus::PARTIALLY_REFUNDED : PaymentStatus::REFUNDED;

                $intent->update([
                    'status' => $newStatus,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'refund_completed_at' => now()->toIso8601String(),
                        'refund_amount_millimes' => $refundAmountInMillimes,
                        'refund_amount' => $refundAmountInMillimes / 1000,
                        'refund_type' => $isPartial ? 'partial' : 'full',
                        'refund_api_response' => $data,
                    ]),
                ]);

                Log::info('Clictopay refund successful', [
                    'intent_id' => $intent->id,
                    'booking_id' => $intent->booking_id,
                    'refund_amount' => $refundAmountInMillimes / 1000,
                    'partial' => $isPartial,
                ]);

                return $intent->fresh();
            }

            // API returned an error
            Log::warning('Clictopay refund.do API error', [
                'intent_id' => $intent->id,
                'error_code' => $errorCode,
                'error_message' => $data['errorMessage'] ?? 'Unknown',
            ]);

            return $this->markRefundAsManual($intent, $refundAmountInMillimes, $isPartial, $data['errorMessage'] ?? 'API error');
        } catch (\Exception $e) {
            Log::error('Clictopay refund exception', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            return $this->markRefundAsManual($intent, $refundAmountInMillimes, $isPartial, $e->getMessage());
        }
    }

    /**
     * Reverse (cancel) a payment via Clictopay reverse.do API.
     *
     * Used when a booking is cancelled after successful payment.
     * Falls back gracefully if the API call fails.
     */
    public function reverse(PaymentIntent $intent): PaymentIntent
    {
        $this->loadConfiguration();

        $orderId = $intent->gateway_id;

        if (! $orderId) {
            Log::warning('Cannot reverse Clictopay payment - no gateway order ID', [
                'intent_id' => $intent->id,
            ]);

            $intent->update([
                'metadata' => array_merge($intent->metadata ?? [], [
                    'reversal_error' => 'No gateway order ID',
                    'reversal_requested_at' => now()->toIso8601String(),
                ]),
            ]);

            return $intent->fresh();
        }

        try {
            $response = $this->httpClient()
                ->asForm()
                ->post($this->getBaseUrl() . '/reverse.do', [
                    'userName' => $this->config['username'] ?? '',
                    'password' => $this->config['password'] ?? '',
                    'orderId' => $orderId,
                    'language' => $this->config['language'] ?? 'fr',
                ]);

            if ($response->failed()) {
                throw new \Exception('Clictopay reverse.do HTTP error: ' . $response->status());
            }

            $data = $response->json() ?? [];
            $errorCode = $data['errorCode'] ?? -1;

            if ($errorCode === 0 || $errorCode === '0') {
                $intent->update([
                    'status' => PaymentStatus::REFUNDED,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'reversed_at' => now()->toIso8601String(),
                        'reverse_api_response' => $data,
                    ]),
                ]);

                Log::info('Clictopay reversal successful', [
                    'intent_id' => $intent->id,
                    'booking_id' => $intent->booking_id,
                ]);

                return $intent->fresh();
            }

            // API returned an error - keep current status
            Log::warning('Clictopay reverse.do API error', [
                'intent_id' => $intent->id,
                'error_code' => $errorCode,
                'error_message' => $data['errorMessage'] ?? 'Unknown',
            ]);

            $intent->update([
                'metadata' => array_merge($intent->metadata ?? [], [
                    'reverse_error' => $data['errorMessage'] ?? 'API error',
                    'reverse_error_code' => $errorCode,
                    'reversal_requested_at' => now()->toIso8601String(),
                ]),
            ]);

            return $intent->fresh();
        } catch (\Exception $e) {
            Log::error('Clictopay reversal exception', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            $intent->update([
                'metadata' => array_merge($intent->metadata ?? [], [
                    'reversal_error' => $e->getMessage(),
                    'reversal_requested_at' => now()->toIso8601String(),
                ]),
            ]);

            return $intent->fresh();
        }
    }

    /**
     * Mark a refund as pending manual processing (fallback).
     */
    private function markRefundAsManual(PaymentIntent $intent, int $amountMillimes, bool $isPartial, string $reason): PaymentIntent
    {
        $intent->update([
            'status' => PaymentStatus::PENDING,
            'metadata' => array_merge($intent->metadata ?? [], [
                'refund_requested_at' => now()->toIso8601String(),
                'refund_amount_millimes' => $amountMillimes,
                'refund_amount' => $amountMillimes / 1000,
                'refund_status' => 'pending_manual_processing',
                'refund_type' => $isPartial ? 'partial' : 'full',
                'refund_error' => $reason,
            ]),
        ]);

        Log::warning('Clictopay refund requires manual processing', [
            'intent_id' => $intent->id,
            'booking_id' => $intent->booking_id,
            'refund_amount' => $amountMillimes / 1000,
            'reason' => $reason,
        ]);

        return $intent->fresh();
    }

    /**
     * Get the current status of a payment intent.
     *
     * Optionally refreshes status from Clictopay API for pending payments.
     */
    public function getStatus(PaymentIntent $intent): PaymentStatus
    {
        $this->loadConfiguration();

        // For pending payments with a gateway ID, refresh from Clictopay
        if ($intent->gateway_id && $intent->status === PaymentStatus::PENDING) {
            try {
                $statusResponse = $this->getOrderStatusExtended($intent->gateway_id);
                $orderStatus = $statusResponse['orderStatus'] ?? -1;

                // Map Clictopay status codes (per Manuel IntégrationV2.2.pdf):
                // 0 = Registered (PENDING)
                // 1 = Pre-authorized (PROCESSING)
                // 2 = Deposited (SUCCEEDED)
                // 3 = Reversed (REFUNDED)
                // 4 = Refunded (REFUNDED)
                // 5 = ACS initiated - 3DS in progress (PROCESSING) - NOT a failure!
                // 6 = Declined (FAILED)
                if ($orderStatus === 2) {
                    return PaymentStatus::SUCCEEDED;
                } elseif ($orderStatus === 1 || $orderStatus === 5) {
                    // Status 5 = 3DS authentication in progress, NOT failed
                    return PaymentStatus::PROCESSING;
                } elseif (in_array($orderStatus, [3, 4], true)) {
                    return PaymentStatus::REFUNDED;
                } elseif ($orderStatus === 6) {
                    return PaymentStatus::FAILED;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to refresh Clictopay status', [
                    'intent_id' => $intent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $intent->status;
    }

    /**
     * Get the payment form URL for frontend redirect.
     *
     * Returns the Clictopay hosted payment page URL where the user
     * should be redirected to complete their payment.
     */
    public function getPaymentUrl(PaymentIntent $intent): ?string
    {
        return $intent->metadata['form_url'] ?? null;
    }

    /**
     * Register a payment with Clictopay API.
     *
     * Calls the register.do endpoint to create a payment session.
     *
     * @param  PaymentIntent  $intent  The payment intent
     * @param  string  $orderNumber  Unique order reference
     * @param  int  $amount  Amount in millimes
     * @param  string  $pageView  Page view type (DESKTOP or MOBILE)
     * @param  array  $options  Additional options (all_booking_numbers for cart payments)
     * @return array API response
     *
     * @throws \Exception If API call fails
     */
    private function registerPayment(PaymentIntent $intent, string $orderNumber, int $amount, string $pageView = 'DESKTOP', array $options = []): array
    {
        $returnUrl = route('payment.clictopay.callback', ['intent' => $intent->id]);
        $failUrl = route('payment.clictopay.callback', ['intent' => $intent->id, 'status' => 'failed']);

        $booking = $intent->booking;

        // Build description using all booking numbers for cart payments
        $allBookingNumbers = $options['all_booking_numbers'] ?? null;

        if ($allBookingNumbers && count($allBookingNumbers) > 1) {
            $description = $this->buildCartOrderDescription($allBookingNumbers);
        } else {
            $description = $this->buildOrderDescription($booking);
        }

        $jsonParams = $this->buildJsonParams($booking);

        $params = [
            'userName' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
            'orderNumber' => $orderNumber,
            'amount' => $amount,
            'currency' => self::TND_CURRENCY_CODE,
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            'description' => $description,
            'language' => $this->config['language'] ?? 'fr',
            'pageView' => $pageView,
            'sessionTimeoutSecs' => 900,
        ];

        if ($jsonParams) {
            $params['jsonParams'] = $jsonParams;
        }

        Log::debug('Clictopay register.do request', [
            'url' => $this->getBaseUrl() . '/register.do',
            'order_number' => $orderNumber,
            'amount' => $amount,
            'return_url' => $returnUrl,
            'fail_url' => $failUrl,
        ]);

        $response = $this->httpClient()
            ->asForm()
            ->post($this->getBaseUrl() . '/register.do', $params);

        Log::debug('Clictopay register.do response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \Exception('Clictopay API request failed: HTTP ' . $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Get extended order status from Clictopay API.
     *
     * Uses getOrderStatusExtended.do for richer response data including
     * actionCode, actionCodeDescription, and cardAuthInfo.
     *
     * @param  string  $orderId  Clictopay order ID
     * @return array API response
     *
     * @throws \Exception If API call fails
     */
    private function getOrderStatusExtended(string $orderId): array
    {
        $params = [
            'userName' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
            'orderId' => $orderId,
            'language' => $this->config['language'] ?? 'fr',
        ];

        Log::debug('Clictopay getOrderStatusExtended.do request', [
            'url' => $this->getBaseUrl() . '/getOrderStatusExtended.do',
            'order_id' => $orderId,
        ]);

        $response = $this->httpClient()
            ->get($this->getBaseUrl() . '/getOrderStatusExtended.do', $params);

        if ($response->failed()) {
            throw new \Exception('Clictopay status check failed: HTTP ' . $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Build order description from booking details (max 512 chars per SMT spec).
     */
    private function buildOrderDescription(Booking $booking): string
    {
        $listingTitle = '';

        if ($booking->listing) {
            $listingTitle = $booking->listing->getTranslation('title', 'fr')
                ?: ($booking->listing->getTranslation('title', 'en') ?: '');

            if (is_array($listingTitle)) {
                $listingTitle = reset($listingTitle) ?: '';
            }
        }

        $description = $listingTitle
            ? "Réservation {$booking->booking_number} - {$listingTitle}"
            : "Réservation {$booking->booking_number}";

        return mb_substr($description, 0, 512);
    }

    /**
     * Build order description for cart payments with multiple bookings.
     *
     * @param  array  $bookingNumbers  Array of booking numbers
     * @return string Description (max 512 chars per SMT spec)
     */
    private function buildCartOrderDescription(array $bookingNumbers): string
    {
        $count = count($bookingNumbers);

        if ($count === 1) {
            return "Réservation {$bookingNumbers[0]}";
        }

        // For multiple bookings, show count and list booking numbers
        $numbersList = implode(', ', $bookingNumbers);
        $description = "Réservations ({$count}): {$numbersList}";

        return mb_substr($description, 0, 512);
    }

    /**
     * Build jsonParams for register.do with customer email for ClicToPay notifications.
     */
    private function buildJsonParams(Booking $booking): ?string
    {
        $email = $booking->getPrimaryEmail();

        if (! $email) {
            return null;
        }

        return json_encode(['email' => $email]);
    }

    /**
     * Get the base URL for API calls based on test mode.
     */
    private function getBaseUrl(): string
    {
        return $this->isTestMode() ? self::TEST_BASE_URL : self::PROD_BASE_URL;
    }

    /**
     * Generate a unique order number for Clictopay.
     *
     * Format: DF-{booking_number}-{timestamp}
     * Max 32 characters as per Clictopay spec.
     */
    private function generateOrderNumber(Booking $booking): string
    {
        $base = 'DF-' . substr($booking->booking_number, -8) . '-' . time();

        return substr($base, 0, 32);
    }

    /**
     * Load configuration from the database PaymentGateway model.
     */
    private function loadConfiguration(): void
    {
        if ($this->config === null) {
            $gateway = \App\Models\PaymentGateway::where('driver', 'clicktopay')->first();
            $this->config = $gateway?->configuration ?? [];
        }
    }

    /**
     * Check if gateway is in test mode.
     */
    private function isTestMode(): bool
    {
        $gateway = \App\Models\PaymentGateway::where('driver', 'clicktopay')->first();

        return $gateway?->test_mode ?? true;
    }

    /**
     * Get configured HTTP client (disables SSL verification in test mode for containers without CA certs).
     */
    private function httpClient(): PendingRequest
    {
        $client = Http::timeout(30);

        // Disable SSL verification in test mode (for Docker containers without CA certificates)
        if ($this->isTestMode()) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Map registration error codes from register.do to user-friendly reason codes.
     *
     * Error codes per SMT ClicToPay Integration Manual v2.2:
     * - 0: No error (success)
     * - 1: Duplicate order number
     * - 3: Unknown currency
     * - 4: Required parameter missing
     * - 5: Wrong parameter value / Access denied
     * - 7: System error
     *
     * @param  string|int|null  $errorCode  The error code from ClicToPay
     * @param  string|null  $errorMessage  The error message from ClicToPay
     * @return string A user-friendly reason code for the frontend
     */
    private function mapRegistrationErrorToReason(string|int|null $errorCode, ?string $errorMessage): string
    {
        // Map by error code first (per SMT documentation)
        $reason = match ((int) $errorCode) {
            1 => 'duplicate_order',
            3 => 'invalid_currency',
            4, 5 => 'gateway_configuration_error',
            7 => 'gateway_system_error',
            default => null,
        };

        if ($reason !== null) {
            return $reason;
        }

        // Keyword fallback for unknown or unmapped codes
        $message = strtolower($errorMessage ?? '');

        if (str_contains($message, 'duplicate') || str_contains($message, 'déjà') || str_contains($message, 'already')) {
            return 'duplicate_order';
        }

        if (str_contains($message, 'currency') || str_contains($message, 'monnaie')) {
            return 'invalid_currency';
        }

        if (str_contains($message, 'system') || str_contains($message, 'système')) {
            return 'gateway_system_error';
        }

        return 'gateway_error';
    }

    /**
     * Verify webhook signature from Clictopay (if implemented).
     *
     * Note: Clictopay primarily uses redirect-based callbacks rather than webhooks.
     * This method is provided for potential future webhook support.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $this->loadConfiguration();

        $sharedSecret = $this->config['shared_secret'] ?? '';

        if (empty($sharedSecret)) {
            Log::warning('Clictopay shared secret not configured for webhook verification');

            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $sharedSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
