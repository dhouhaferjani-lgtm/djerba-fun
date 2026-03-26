<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentIntent;
use App\Services\Payment\ClickToPayPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * BDD tests for ClickToPayPaymentGateway.
 *
 * Tests cover:
 * - Enhanced register.do parameters (failUrl, description, jsonParams, sessionTimeoutSecs, pageView)
 * - Registration error mapping (SMT error codes)
 * - Actual refund.do API call
 * - reverse.do cancellation API call
 * - getOrderStatusExtended.do endpoint
 * - Status code mapping (including 3DS status 5)
 * - Mobile page support (pageView parameter)
 * - Cart payment support (cart_total, all_booking_numbers)
 */
class ClickToPayGatewayTest extends TestCase
{
    use RefreshDatabase;

    private ClickToPayPaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the Clictopay gateway configuration in DB
        PaymentGatewayModel::create([
            'name' => 'ClicToPay',
            'driver' => 'clicktopay',
            'slug' => 'clicktopay',
            'display_name' => 'Carte bancaire',
            'description' => 'Paiement par carte bancaire via ClicToPay',
            'is_enabled' => true,
            'is_default' => false,
            'test_mode' => true,
            'priority' => 1,
            'configuration' => [
                'username' => 'test_merchant',
                'password' => 'test_password',
                'language' => 'fr',
            ],
        ]);

        $this->gateway = new ClickToPayPaymentGateway;
    }

    // =========================================================================
    // Enhanced register.do parameters tests
    // =========================================================================

    public function test_register_do_sends_fail_url_parameter(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-def',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=abc-123-def',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking);

        Http::assertSent(function ($request) {
            // failUrl should be present in the register.do request
            return str_contains($request->url(), 'register.do')
                && ! empty($request->data()['failUrl'] ?? null);
        });
    }

    public function test_register_do_sends_description_parameter(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-def',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=abc-123-def',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'register.do')
                && ! empty($request->data()['description'] ?? null);
        });
    }

    public function test_register_do_sends_json_params_with_email(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-def',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=abc-123-def',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
            'traveler_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+21612345678',
            ],
        ]);

        $this->gateway->createIntent($booking);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'register.do')) {
                return false;
            }

            $jsonParams = $request->data()['jsonParams'] ?? null;

            if (! $jsonParams) {
                return false;
            }

            $decoded = json_decode($jsonParams, true);

            return isset($decoded['email']) && $decoded['email'] === 'john@example.com';
        });
    }

    public function test_register_do_sends_session_timeout_secs(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-def',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=abc-123-def',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'register.do')
                && ($request->data()['sessionTimeoutSecs'] ?? null) == 900;
        });
    }

    public function test_register_do_still_sends_required_params(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-def',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=abc-123-def',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 75.500,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'register.do')) {
                return false;
            }

            $data = $request->data();

            // All required params must still be present
            return isset($data['userName'])
                && isset($data['password'])
                && isset($data['orderNumber'])
                && isset($data['amount'])
                && isset($data['currency'])
                && isset($data['returnUrl'])
                && isset($data['language'])
                && $data['userName'] === 'test_merchant'
                && $data['password'] === 'test_password'
                && $data['amount'] == 75500 // 75.5 TND = 75500 millimes
                && $data['currency'] == 788;
        });
    }

    public function test_register_do_creates_intent_with_correct_metadata_on_success(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'order-uuid-123',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=order-uuid-123',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::PENDING, $intent->status);
        $this->assertEquals('order-uuid-123', $intent->gateway_id);
        $this->assertNotNull($intent->metadata['form_url']);
        $this->assertEquals('clicktopay', $intent->gateway);
    }

    public function test_register_do_marks_intent_failed_on_error_response(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '1',
                'errorMessage' => 'Order number already used',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertNotNull($intent->metadata['registration_error']);
    }

    // =========================================================================
    // Registration Error Reason Mapping Tests (SMT error codes 0-7)
    // =========================================================================

    public function test_registration_error_code_1_maps_to_duplicate_order(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '1',
                'errorMessage' => 'Order number already used',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('duplicate_order', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_error_code_3_maps_to_invalid_currency(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '3',
                'errorMessage' => 'Unknown currency',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('invalid_currency', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_error_code_4_maps_to_gateway_configuration_error(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '4',
                'errorMessage' => 'Required parameter missing',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('gateway_configuration_error', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_error_code_5_maps_to_gateway_configuration_error(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '5',
                'errorMessage' => 'Access denied',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('gateway_configuration_error', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_error_code_7_maps_to_gateway_system_error(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '7',
                'errorMessage' => 'System error',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('gateway_system_error', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_error_unknown_code_with_duplicate_keyword_maps_to_duplicate_order(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '99',
                'errorMessage' => 'This order has already been processed',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('duplicate_order', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_error_unknown_code_no_keywords_returns_gateway_error(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'errorCode' => '99',
                'errorMessage' => 'Something went wrong',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('gateway_error', $intent->metadata['registration_error_reason']);
    }

    public function test_registration_exception_maps_to_gateway_system_error(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response(null, 504),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertEquals('gateway_system_error', $intent->metadata['registration_error_reason']);
    }

    // =========================================================================
    // Actual refund.do API call tests
    // =========================================================================

    public function test_refund_calls_refund_do_api_with_correct_params(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/refund.do*' => Http::response([
                'errorCode' => 0,
                'errorMessage' => 'Success',
            ]),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'total_amount' => 150.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 150.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-id-456',
            'paid_at' => now(),
            'metadata' => [
                'clictopay_order_id' => 'clictopay-order-id-456',
            ],
        ]);

        $result = $this->gateway->refund($intent);

        // Should call refund.do with correct parameters
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'refund.do')) {
                return false;
            }

            $data = $request->data();

            return $data['userName'] === 'test_merchant'
                && $data['password'] === 'test_password'
                && $data['orderId'] === 'clictopay-order-id-456'
                && $data['amount'] == 150000; // 150 TND = 150000 millimes
        });

        $this->assertEquals(PaymentStatus::REFUNDED, $result->status);
    }

    public function test_partial_refund_sends_correct_amount(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/refund.do*' => Http::response([
                'errorCode' => 0,
                'errorMessage' => 'Success',
            ]),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'total_amount' => 200.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 200.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-id-789',
            'paid_at' => now(),
            'metadata' => [],
        ]);

        // Partial refund of 50 TND (50000 millimes)
        $result = $this->gateway->refund($intent, 50000);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'refund.do')) {
                return false;
            }

            return $request->data()['amount'] == 50000;
        });

        $this->assertEquals(PaymentStatus::PARTIALLY_REFUNDED, $result->status);
    }

    public function test_refund_falls_back_to_manual_on_api_failure(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/refund.do*' => Http::response([
                'errorCode' => 7,
                'errorMessage' => 'System error',
            ]),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-fail',
            'paid_at' => now(),
            'metadata' => [],
        ]);

        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $result = $this->gateway->refund($intent);

        // On API failure, should mark as pending with manual processing note
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
        $this->assertEquals('pending_manual_processing', $result->metadata['refund_status']);
    }

    public function test_refund_falls_back_to_manual_on_http_exception(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/refund.do*' => Http::response('Server Error', 500),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-500',
            'paid_at' => now(),
            'metadata' => [],
        ]);

        $result = $this->gateway->refund($intent);

        // On HTTP exception, should still fallback gracefully
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
        $this->assertNotNull($result->metadata['refund_status']);
    }

    // =========================================================================
    // reverse.do cancellation API call tests
    // =========================================================================

    public function test_reverse_calls_reverse_do_api_with_correct_params(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/reverse.do*' => Http::response([
                'errorCode' => 0,
                'errorMessage' => 'Success',
            ]),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-reverse',
            'paid_at' => now(),
            'metadata' => [],
        ]);

        $result = $this->gateway->reverse($intent);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'reverse.do')) {
                return false;
            }

            $data = $request->data();

            return $data['userName'] === 'test_merchant'
                && $data['password'] === 'test_password'
                && $data['orderId'] === 'clictopay-order-reverse';
        });

        $this->assertEquals(PaymentStatus::REFUNDED, $result->status);
    }

    public function test_reverse_falls_back_gracefully_on_api_failure(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/reverse.do*' => Http::response([
                'errorCode' => 5,
                'errorMessage' => 'Order cannot be reversed',
            ]),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-no-reverse',
            'paid_at' => now(),
            'metadata' => [],
        ]);

        $result = $this->gateway->reverse($intent);

        // On failure, should keep succeeded status and note the failed reversal
        $this->assertNotEquals(PaymentStatus::REFUNDED, $result->status);
        $this->assertNotNull($result->metadata['reverse_error'] ?? $result->metadata['reversal_error'] ?? null);
    }

    // =========================================================================
    // getOrderStatusExtended.do tests
    // =========================================================================

    public function test_process_payment_uses_extended_status_endpoint(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2,
                'actionCode' => 0,
                'actionCodeDescription' => 'Success',
                'amount' => 100000,
                'currency' => '788',
                'authCode' => '123456',
                'cardAuthInfo' => [
                    'pan' => '411111**1111',
                    'cardholderName' => 'JOHN DOE',
                    'expiration' => '203012',
                ],
                'orderNumber' => 'DF-12345678-1234',
                'ip' => '192.168.1.1',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-extended',
            'metadata' => [
                'order_number' => 'DF-12345678-1234',
                'clictopay_order_id' => 'clictopay-order-extended',
            ],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        // Should call getOrderStatusExtended.do, NOT getOrderStatus.do
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'getOrderStatusExtended.do');
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'getOrderStatus.do')
                && ! str_contains($request->url(), 'Extended');
        });

        $this->assertEquals(PaymentStatus::SUCCEEDED, $result->status);
    }

    public function test_extended_status_stores_card_auth_info_in_metadata(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2,
                'actionCode' => 0,
                'actionCodeDescription' => 'Success',
                'amount' => 100000,
                'currency' => '788',
                'authCode' => '789012',
                'cardAuthInfo' => [
                    'pan' => '522222**2222',
                    'cardholderName' => 'JANE DOE',
                    'expiration' => '202612',
                ],
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-card-info',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::SUCCEEDED, $result->status);

        // Should store card auth info for admin panel display
        $metadata = $result->metadata;
        $this->assertNotNull($metadata['card_pan'] ?? null);
        $this->assertNotNull($metadata['cardholder_name'] ?? null);
    }

    public function test_extended_status_stores_action_code_on_failure(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -2007,
                'actionCodeDescription' => 'Declined by issuing bank',
                'amount' => 100000,
                'currency' => '788',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-declined',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::FAILED, $result->status);

        // Should store actionCode and description for debugging
        $metadata = $result->metadata;
        $this->assertEquals(-2007, $metadata['action_code'] ?? null);
        $this->assertNotNull($metadata['action_code_description'] ?? null);
    }

    public function test_get_status_uses_extended_endpoint_for_pending_payments(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2,
                'actionCode' => 0,
                'actionCodeDescription' => 'Success',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'clictopay-order-status-check',
            'metadata' => [],
        ]);

        $status = $this->gateway->getStatus($intent);

        $this->assertEquals(PaymentStatus::SUCCEEDED, $status);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'getOrderStatusExtended.do');
        });
    }

    // =========================================================================
    // Regression tests: ensure existing flows still work
    // =========================================================================

    public function test_create_intent_handles_network_exception_gracefully(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response('Connection timeout', 504),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = $this->gateway->createIntent($booking);

        $this->assertEquals(PaymentStatus::FAILED, $intent->status);
        $this->assertNotNull($intent->metadata['registration_error']);
    }

    public function test_process_payment_fails_gracefully_without_order_id(): void
    {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => null,
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::FAILED, $result->status);
    }

    public function test_get_payment_url_returns_form_url_from_metadata(): void
    {
        $intent = new PaymentIntent;
        $intent->metadata = ['form_url' => 'https://test.clictopay.com/pay?mdOrder=123'];

        $url = $this->gateway->getPaymentUrl($intent);

        $this->assertEquals('https://test.clictopay.com/pay?mdOrder=123', $url);
    }

    public function test_get_payment_url_returns_null_when_no_form_url(): void
    {
        $intent = new PaymentIntent;
        $intent->metadata = [];

        $url = $this->gateway->getPaymentUrl($intent);

        $this->assertNull($url);
    }

    // =========================================================================
    // Status Code Mapping Tests (per ClicToPay Manuel IntégrationV2.2.pdf)
    // =========================================================================

    public function test_status_0_maps_to_pending(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 0,
                'actionCode' => 0,
                'actionCodeDescription' => 'Order registered',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-0',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::PENDING, $result->status);
    }

    public function test_status_1_maps_to_processing_preauthorized(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 1,
                'actionCode' => 0,
                'actionCodeDescription' => 'Pre-authorized',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-1',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::PROCESSING, $result->status);
    }

    public function test_status_2_maps_to_succeeded(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2,
                'actionCode' => 0,
                'actionCodeDescription' => 'Deposited successfully',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-2',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::SUCCEEDED, $result->status);
    }

    public function test_status_3_maps_to_refunded_reversed(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 3,
                'actionCode' => 0,
                'actionCodeDescription' => 'Authorization cancelled',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-3',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        // Status 3 = Reversed/Cancelled - should map to REFUNDED
        $this->assertEquals(PaymentStatus::REFUNDED, $result->status);
    }

    public function test_status_4_maps_to_refunded(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 4,
                'actionCode' => 0,
                'actionCodeDescription' => 'Transaction refunded',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-4',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::REFUNDED, $result->status);
    }

    /**
     * CRITICAL BUG FIX: Status 5 = "Autorisation par ACS de l'émetteur initié"
     * This means 3D Secure authentication is IN PROGRESS, NOT a failure!
     * The user is being redirected to their bank's 3DS page.
     */
    public function test_status_5_maps_to_processing_3ds_in_progress(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 5,
                'actionCode' => 0,
                'actionCodeDescription' => 'ACS authorization initiated',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-5-3ds',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        // Status 5 = 3DS in progress -> should be PROCESSING, NOT FAILED!
        $this->assertEquals(PaymentStatus::PROCESSING, $result->status);
        $this->assertArrayHasKey('3ds_in_progress', $result->metadata);
    }

    /**
     * Status 6 = "Autorisation refusée" (Authorization declined)
     * This is the ACTUAL failure status.
     */
    public function test_status_6_maps_to_failed_declined(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -2007,
                'actionCodeDescription' => 'Authorization declined by issuer',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-status-6-declined',
            'metadata' => [],
        ]);

        $result = $this->gateway->processPayment($intent, []);

        $this->assertEquals(PaymentStatus::FAILED, $result->status);
    }

    public function test_get_status_correctly_handles_status_5_as_processing(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 5,
                'actionCode' => 0,
                'actionCodeDescription' => 'ACS authorization initiated',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-get-status-5',
            'metadata' => [],
        ]);

        $status = $this->gateway->getStatus($intent);

        // getStatus should also return PROCESSING for status 5 (3DS in progress)
        $this->assertEquals(PaymentStatus::PROCESSING, $status);
    }

    public function test_get_status_correctly_handles_status_6_as_failed(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -1,
                'actionCodeDescription' => 'Declined',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'order-get-status-6',
            'metadata' => [],
        ]);

        $status = $this->gateway->getStatus($intent);

        $this->assertEquals(PaymentStatus::FAILED, $status);
    }

    // =========================================================================
    // Mobile Page Support Tests (pageView parameter)
    // =========================================================================

    public function test_register_do_sends_page_view_desktop_by_default(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-def',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/payment_en.html?mdOrder=abc-123-def',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'register.do')
                && ($request->data()['pageView'] ?? null) === 'DESKTOP';
        });
    }

    public function test_register_do_sends_page_view_mobile_when_specified(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'abc-123-mobile',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/mobile_payment_en.html?mdOrder=abc-123-mobile',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking, ['page_view' => 'MOBILE']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'register.do')
                && ($request->data()['pageView'] ?? null) === 'MOBILE';
        });
    }

    // =========================================================================
    // Cart Payment Tests - Multiple bookings in a single cart payment
    // =========================================================================

    public function test_create_intent_uses_cart_total_when_provided(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'cart-order-123',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=cart-order-123',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 20.00,  // First booking is 20 TND
            'currency' => 'TND',
        ]);

        // Create intent with cart_total override (simulating 2 bookings: 20 + 30 = 50 TND)
        $intent = $this->gateway->createIntent($booking, [
            'cart_total' => 50.00,  // Total cart amount
            'all_booking_numbers' => ['DF-202603-AAA', 'DF-202603-BBB'],
        ]);

        // Intent amount should be cart_total, not booking amount
        $this->assertEquals(50.00, $intent->amount);

        // HTTP request should have cart total in millimes (50000)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'register.do')
                && $request->data()['amount'] == 50000;  // 50 TND = 50000 millimes
        });
    }

    public function test_create_intent_stores_all_booking_numbers_in_metadata(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'cart-order-456',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=cart-order-456',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 25.00,
            'currency' => 'TND',
        ]);

        $allBookingNumbers = ['DF-202603-XXX', 'DF-202603-YYY', 'DF-202603-ZZZ'];

        $intent = $this->gateway->createIntent($booking, [
            'cart_total' => 75.00,
            'all_booking_numbers' => $allBookingNumbers,
        ]);

        // Metadata should contain all booking numbers
        $this->assertArrayHasKey('all_booking_numbers', $intent->metadata);
        $this->assertEquals($allBookingNumbers, $intent->metadata['all_booking_numbers']);
    }

    public function test_register_do_sends_cart_description_for_multiple_bookings(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'cart-multi-789',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=cart-multi-789',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 40.00,
            'currency' => 'TND',
        ]);

        $this->gateway->createIntent($booking, [
            'cart_total' => 80.00,
            'all_booking_numbers' => ['DF-202603-AB1', 'DF-202603-CD2'],
        ]);

        // Description should include both booking numbers
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'register.do')) {
                return false;
            }

            $description = $request->data()['description'] ?? '';

            // Should contain "Réservations" (plural) and both booking numbers
            return str_contains($description, 'Réservations')
                && str_contains($description, 'DF-202603-AB1')
                && str_contains($description, 'DF-202603-CD2');
        });
    }

    public function test_create_intent_defaults_to_booking_amount_when_no_cart_total(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'single-order-123',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=single-order-123',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 35.50,
            'currency' => 'TND',
        ]);

        // Create intent WITHOUT cart_total (single booking flow)
        $intent = $this->gateway->createIntent($booking, []);

        // Intent amount should be booking amount (backward compatible)
        $this->assertEquals(35.50, $intent->amount);

        // HTTP request should have booking amount in millimes (35500)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'register.do')
                && $request->data()['amount'] == 35500;  // 35.5 TND = 35500 millimes
        });
    }

    public function test_create_intent_defaults_to_single_booking_number_in_metadata(): void
    {
        Http::fake([
            'test.clictopay.com/*' => Http::response([
                'orderId' => 'single-booking-456',
                'formUrl' => 'https://test.clictopay.com/payment/merchants/pay.html?mdOrder=single-booking-456',
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 45.00,
            'currency' => 'TND',
            'booking_number' => 'DF-202603-SINGLE',
        ]);

        // Create intent WITHOUT all_booking_numbers (single booking flow)
        $intent = $this->gateway->createIntent($booking, []);

        // Metadata should default to single booking number
        $this->assertArrayHasKey('all_booking_numbers', $intent->metadata);
        $this->assertEquals(['DF-202603-SINGLE'], $intent->metadata['all_booking_numbers']);
    }
}
