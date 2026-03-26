<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Cart;
use App\Models\CartPayment;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentIntent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * BDD tests for ClictopayCallbackController.
 *
 * Tests transaction safety, idempotency, and error reason mapping
 * for ClicToPay callback handling.
 */
class ClictopayCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the Clictopay gateway configuration
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

        // Prevent actual emails
        Mail::fake();
    }

    /**
     * Already succeeded payment redirects without reprocessing (idempotency).
     */
    public function test_already_succeeded_payment_redirects_only(): void
    {
        // Create a booking with ALREADY SUCCEEDED payment intent
        $booking = Booking::factory()->create([
            'status' => BookingStatus::CONFIRMED,
            'total_amount' => 100.00,
            'currency' => 'TND',
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED, // Already succeeded
            'gateway' => 'clicktopay',
            'gateway_id' => 'already-done-123',
        ]);

        // No HTTP calls should be made to verify payment - it's already done
        Http::fake();

        // Call callback
        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        // Should redirect to success page
        $response->assertRedirect();
        $this->assertStringContainsString('success', $response->headers->get('Location'));

        // NO API calls should be made
        Http::assertNothingSent();
    }

    /**
     * Cart callback processes all bookings atomically.
     */
    public function test_callback_confirms_all_cart_bookings_atomically(): void
    {
        // Mock the ClicToPay API to return success
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2, // SUCCESS
                'orderNumber' => 'TEST-CART-123',
                'amount' => 200000, // 200 TND in millimes
            ]),
        ]);

        // Create cart with payment
        $cart = Cart::factory()->create(['status' => 'checking_out']);
        $cartPayment = CartPayment::create([
            'cart_id' => $cart->id,
            'amount' => 200.00,
            'currency' => 'TND',
            'status' => PaymentStatus::PENDING,
            'payment_method' => 'click_to_pay',
            'gateway' => 'clicktopay',
        ]);

        // Create 2 pending bookings
        $booking1 = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
            'cart_payment_id' => $cartPayment->id,
        ]);

        $booking2 = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'currency' => 'TND',
            'cart_payment_id' => $cartPayment->id,
        ]);

        $cartPayment->bookings()->attach($booking1->id, ['amount' => 100.00]);
        $cartPayment->bookings()->attach($booking2->id, ['amount' => 100.00]);

        // Create pending PaymentIntent for primary booking
        $intent = PaymentIntent::create([
            'booking_id' => $booking1->id,
            'amount' => 200.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'cart-atomic-test',
            'metadata' => [
                'cart_payment_id' => $cartPayment->id,
            ],
        ]);

        // Call callback
        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        // Should redirect to success page
        $response->assertRedirect();

        // Verify ALL bookings are now confirmed
        $booking1->refresh();
        $booking2->refresh();
        $cartPayment->refresh();
        $cart->refresh();

        $this->assertEquals(BookingStatus::CONFIRMED, $booking1->status);
        $this->assertEquals(BookingStatus::CONFIRMED, $booking2->status);
        $this->assertEquals(PaymentStatus::SUCCEEDED, $cartPayment->status);
        $this->assertEquals('completed', $cart->status);
    }

    /**
     * Duplicate callback on already succeeded cart payment is idempotent.
     */
    public function test_duplicate_cart_callback_is_idempotent(): void
    {
        // Create cart with ALREADY SUCCEEDED payment
        $cart = Cart::factory()->create(['status' => 'completed']);
        $cartPayment = CartPayment::create([
            'cart_id' => $cart->id,
            'amount' => 150.00,
            'currency' => 'TND',
            'status' => PaymentStatus::SUCCEEDED, // Already succeeded
            'payment_method' => 'click_to_pay',
            'gateway' => 'clicktopay',
            'gateway_transaction_id' => 'already-done-cart',
        ]);

        $booking1 = Booking::factory()->create([
            'status' => BookingStatus::CONFIRMED,
            'total_amount' => 75.00,
            'cart_payment_id' => $cartPayment->id,
            'confirmed_at' => now()->subMinutes(5),
        ]);

        $booking2 = Booking::factory()->create([
            'status' => BookingStatus::CONFIRMED,
            'total_amount' => 75.00,
            'cart_payment_id' => $cartPayment->id,
            'confirmed_at' => now()->subMinutes(5),
        ]);

        $cartPayment->bookings()->attach($booking1->id, ['amount' => 75.00]);
        $cartPayment->bookings()->attach($booking2->id, ['amount' => 75.00]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking1->id,
            'amount' => 150.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::SUCCEEDED,
            'gateway' => 'clicktopay',
            'gateway_id' => 'already-done-cart',
            'metadata' => [
                'cart_payment_id' => $cartPayment->id,
            ],
        ]);

        $originalConfirmedAt1 = $booking1->confirmed_at;
        $originalConfirmedAt2 = $booking2->confirmed_at;

        // No HTTP calls should be made
        Http::fake();

        // Call callback (simulating duplicate/retry)
        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        // Should redirect to success
        $response->assertRedirect();
        $this->assertStringContainsString('success', $response->headers->get('Location'));

        // Bookings should NOT be modified (confirmed_at unchanged)
        $booking1->refresh();
        $booking2->refresh();

        $this->assertEquals($originalConfirmedAt1->toDateTimeString(), $booking1->confirmed_at->toDateTimeString());
        $this->assertEquals($originalConfirmedAt2->toDateTimeString(), $booking2->confirmed_at->toDateTimeString());

        // No API calls made
        Http::assertNothingSent();
    }

    /**
     * Single booking callback still works (regression test).
     */
    public function test_single_booking_callback_works(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2, // SUCCESS
                'orderNumber' => 'SINGLE-123',
                'amount' => 50000, // 50 TND
            ]),
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 50.00,
            'currency' => 'TND',
            'cart_payment_id' => null, // Single booking, not cart
        ]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 50.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'single-booking-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();

        $booking->refresh();
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);
    }

    /**
     * Cart payment with pending status gets confirmed.
     */
    public function test_pending_cart_payment_gets_confirmed(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 2, // SUCCESS
                'orderNumber' => 'PENDING-CART',
                'amount' => 100000, // 100 TND
            ]),
        ]);

        $cart = Cart::factory()->create(['status' => 'checking_out']);
        $cartPayment = CartPayment::create([
            'cart_id' => $cart->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'status' => PaymentStatus::PENDING, // Still pending
            'payment_method' => 'click_to_pay',
            'gateway' => 'clicktopay',
        ]);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_amount' => 100.00,
            'cart_payment_id' => $cartPayment->id,
        ]);

        $cartPayment->bookings()->attach($booking->id, ['amount' => 100.00]);

        $intent = PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'currency' => 'TND',
            'payment_method' => 'click_to_pay',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'pending-cart-test',
            'metadata' => [
                'cart_payment_id' => $cartPayment->id,
            ],
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();

        $cartPayment->refresh();
        $booking->refresh();
        $cart->refresh();

        $this->assertEquals(PaymentStatus::SUCCEEDED, $cartPayment->status);
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);
        $this->assertEquals('completed', $cart->status);
    }

    /**
     * Insufficient funds description returns specific reason code.
     */
    public function test_insufficient_funds_description_returns_specific_reason(): void
    {
        // Mock ClicToPay API returning "Solde insuffisant"
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6, // DECLINED
                'actionCode' => -999, // Unknown code - should fall back to keyword matching
                'actionCodeDescription' => 'Solde insuffisant',
                'amount' => 100000,
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
            'gateway_id' => 'insufficient-funds-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=insufficient_funds', $response->headers->get('Location'));
    }

    /**
     * Invalid card description returns specific reason code.
     */
    public function test_invalid_card_description_returns_specific_reason(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -888,
                'actionCodeDescription' => 'Carte non valide',
                'amount' => 100000,
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
            'gateway_id' => 'invalid-card-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=invalid_card', $response->headers->get('Location'));
    }

    /**
     * Expired card description returns specific reason code.
     */
    public function test_expired_card_description_returns_specific_reason(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -777,
                'actionCodeDescription' => 'validité incorrecte',
                'amount' => 100000,
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
            'gateway_id' => 'expired-card-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=expired_card', $response->headers->get('Location'));
    }

    /**
     * Known action code -2007 returns payment_declined.
     */
    public function test_known_action_code_returns_payment_declined(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -2007, // Known code
                'actionCodeDescription' => 'Declined by issuing bank',
                'amount' => 100000,
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
            'gateway_id' => 'known-action-code-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=payment_declined', $response->headers->get('Location'));
    }

    /**
     * Unknown action code with no matching keywords falls back to payment_declined.
     */
    public function test_unknown_action_code_falls_back_to_payment_declined(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -12345, // Unknown code
                'actionCodeDescription' => 'Some random error message', // No keywords match
                'amount' => 100000,
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
            'gateway_id' => 'unknown-code-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        // Falls back to order_status_code mapping: 6 => payment_declined
        $this->assertStringContainsString('reason=payment_declined', $response->headers->get('Location'));
    }

    /**
     * Action code 76 (rejected authorization) returns do_not_honor.
     */
    public function test_action_code_76_rejected_authorization_returns_do_not_honor(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => 76, // Discovered from production testing
                'actionCodeDescription' => 'Rejected Authorization - Issuer bank is not able to process the transaction',
                'amount' => 100000,
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
            'gateway_id' => 'rejected-auth-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=do_not_honor', $response->headers->get('Location'));
    }

    /**
     * Rejected keyword in description returns do_not_honor.
     */
    public function test_rejected_keyword_in_description_returns_do_not_honor(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => -9999, // Unknown code
                'actionCodeDescription' => 'Transaction rejected by processor',
                'amount' => 100000,
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
            'gateway_id' => 'rejected-keyword-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=do_not_honor', $response->headers->get('Location'));
    }

    /**
     * ISO 8583 action code 51 returns insufficient_funds.
     */
    public function test_iso8583_action_code_51_returns_insufficient_funds(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => 51, // ISO 8583: Not sufficient funds
                'actionCodeDescription' => 'Not sufficient funds',
                'amount' => 100000,
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
            'gateway_id' => 'iso8583-51-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=insufficient_funds', $response->headers->get('Location'));
    }

    /**
     * ISO 8583 action code 54 returns expired_card.
     */
    public function test_iso8583_action_code_54_returns_expired_card(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => 54, // ISO 8583: Expired card
                'actionCodeDescription' => 'Expired card',
                'amount' => 100000,
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
            'gateway_id' => 'iso8583-54-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=expired_card', $response->headers->get('Location'));
    }

    /**
     * ISO 8583 action code 14 returns invalid_card.
     */
    public function test_iso8583_action_code_14_returns_invalid_card(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => 14, // ISO 8583: Invalid card number
                'actionCodeDescription' => 'Invalid card number',
                'amount' => 100000,
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
            'gateway_id' => 'iso8583-14-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=invalid_card', $response->headers->get('Location'));
    }

    /**
     * ISO 8583 action code 41 (lost card) returns card_blocked.
     */
    public function test_iso8583_action_code_41_returns_card_blocked(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => 41, // ISO 8583: Lost card
                'actionCodeDescription' => 'Lost card',
                'amount' => 100000,
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
            'gateway_id' => 'iso8583-41-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=card_blocked', $response->headers->get('Location'));
    }

    /**
     * ISO 8583 action code 5 (do not honor) returns do_not_honor.
     */
    public function test_iso8583_action_code_5_returns_do_not_honor(): void
    {
        Http::fake([
            'test.clictopay.com/payment/rest/getOrderStatusExtended.do*' => Http::response([
                'orderStatus' => 6,
                'actionCode' => 5, // ISO 8583: Do not honor
                'actionCodeDescription' => 'Do not honor',
                'amount' => 100000,
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
            'gateway_id' => 'iso8583-5-test',
        ]);

        $response = $this->get(route('payment.clictopay.callback', ['intent' => $intent->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('reason=do_not_honor', $response->headers->get('Location'));
    }
}
