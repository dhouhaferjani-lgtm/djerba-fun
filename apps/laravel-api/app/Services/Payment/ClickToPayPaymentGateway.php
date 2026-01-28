<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // Convert amount to millimes (1 TND = 1000 millimes)
        $amountInMillimes = (int) round($booking->total_amount * 1000);

        // Generate unique order number
        $orderNumber = $this->generateOrderNumber($booking);

        // Create pending payment intent
        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
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
            ],
        ]);

        // Call Clictopay register.do API
        try {
            $response = $this->registerPayment($intent, $orderNumber, $amountInMillimes);

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
                    'amount' => $booking->total_amount,
                    'test_mode' => $this->isTestMode(),
                ]);
            } else {
                // Registration failed
                $errorMessage = $response['errorMessage'] ?? 'Unknown error from Clictopay';
                $errorCode = $response['errorCode'] ?? null;

                $intent->update([
                    'status' => PaymentStatus::FAILED,
                    'failed_at' => now(),
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'registration_error' => $errorMessage,
                        'error_code' => $errorCode,
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::error('Clictopay registration failed', [
                    'intent_id' => $intent->id,
                    'booking_id' => $booking->id,
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
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
            $statusResponse = $this->getOrderStatus($orderId);

            // Parse Clictopay orderStatus:
            // 0 = Registered (payment not started)
            // 1 = Pre-authorized only
            // 2 = Deposited/Paid (SUCCESS)
            // 3 = Reversed
            // 4 = Refunded
            // 5 = Declined
            // 6 = ACS pending
            $orderStatus = $statusResponse['orderStatus'] ?? -1;

            Log::info('Clictopay status check', [
                'intent_id' => $intent->id,
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'response' => $statusResponse,
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
                        'card_pan' => $statusResponse['Pan'] ?? null,
                        'approval_code' => $statusResponse['approvalCode'] ?? null,
                    ]),
                ]);

                Log::info('Clictopay payment verified as successful', [
                    'intent_id' => $intent->id,
                    'booking_id' => $intent->booking_id,
                    'auth_code' => $statusResponse['authCode'] ?? null,
                ]);
            } elseif ($orderStatus === 1) {
                // Pre-authorized only - payment in progress
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
            } else {
                // Failed, declined, or other non-success status
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
     * Refund a payment intent.
     *
     * Note: Clictopay refunds may require manual processing or contacting
     * Monétique Tunisie directly. This implementation marks the intent
     * as pending refund for manual follow-up.
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        $this->loadConfiguration();

        // Calculate refund amount in millimes
        $refundAmountInMillimes = $amount ?? (int) ($intent->amount * 1000);
        $totalAmountInMillimes = (int) ($intent->amount * 1000);

        // Mark as pending refund - requires manual processing with Clictopay
        $intent->update([
            'status' => PaymentStatus::PENDING,
            'metadata' => array_merge($intent->metadata ?? [], [
                'refund_requested_at' => now()->toIso8601String(),
                'refund_amount_millimes' => $refundAmountInMillimes,
                'refund_amount' => $refundAmountInMillimes / 1000,
                'refund_status' => 'pending_manual_processing',
                'refund_type' => $refundAmountInMillimes === $totalAmountInMillimes ? 'full' : 'partial',
            ]),
        ]);

        Log::info('Clictopay refund requested - requires manual processing', [
            'intent_id' => $intent->id,
            'booking_id' => $intent->booking_id,
            'refund_amount' => $refundAmountInMillimes / 1000,
            'currency' => $intent->currency,
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
        // For pending payments with a gateway ID, refresh from Clictopay
        if ($intent->gateway_id && $intent->status === PaymentStatus::PENDING) {
            try {
                $statusResponse = $this->getOrderStatus($intent->gateway_id);
                $orderStatus = $statusResponse['orderStatus'] ?? -1;

                if ($orderStatus === 2) {
                    return PaymentStatus::SUCCEEDED;
                } elseif ($orderStatus === 1) {
                    return PaymentStatus::PROCESSING;
                } elseif (in_array($orderStatus, [3, 4, 5], true)) {
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
     * @return array API response
     *
     * @throws \Exception If API call fails
     */
    private function registerPayment(PaymentIntent $intent, string $orderNumber, int $amount): array
    {
        $returnUrl = route('payment.clictopay.callback', ['intent' => $intent->id]);

        $params = [
            'userName' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
            'orderNumber' => $orderNumber,
            'amount' => $amount,
            'currency' => self::TND_CURRENCY_CODE,
            'returnUrl' => $returnUrl,
            'language' => $this->config['language'] ?? 'en',
        ];

        Log::debug('Clictopay register.do request', [
            'url' => $this->getBaseUrl() . '/register.do',
            'order_number' => $orderNumber,
            'amount' => $amount,
            'return_url' => $returnUrl,
        ]);

        $response = Http::timeout(30)
            ->asForm()
            ->post($this->getBaseUrl() . '/register.do', $params);

        if ($response->failed()) {
            throw new \Exception('Clictopay API request failed: HTTP ' . $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Get order status from Clictopay API.
     *
     * Calls the getOrderStatus.do endpoint to check payment status.
     *
     * @param  string  $orderId  Clictopay order ID
     * @return array API response
     *
     * @throws \Exception If API call fails
     */
    private function getOrderStatus(string $orderId): array
    {
        $params = [
            'userName' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
            'orderId' => $orderId,
            'language' => $this->config['language'] ?? 'en',
        ];

        Log::debug('Clictopay getOrderStatus.do request', [
            'url' => $this->getBaseUrl() . '/getOrderStatus.do',
            'order_id' => $orderId,
        ]);

        $response = Http::timeout(30)
            ->get($this->getBaseUrl() . '/getOrderStatus.do', $params);

        if ($response->failed()) {
            throw new \Exception('Clictopay status check failed: HTTP ' . $response->status());
        }

        return $response->json() ?? [];
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
     * Format: GA-{booking_number}-{timestamp}
     * Max 32 characters as per Clictopay spec.
     */
    private function generateOrderNumber(Booking $booking): string
    {
        $base = 'GA-' . substr($booking->booking_number, -8) . '-' . time();

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
