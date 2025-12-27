<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\HoldStatus;
use App\Models\BookingHold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\PppTestHelpers;
use Tests\TestCase;

/**
 * Integration test suite for PPP (Purchasing Power Parity) pricing API.
 *
 * Tests the complete flow from listing browsing to booking creation,
 * validating currency detection, price changes, and billing verification.
 */
class PppPricingIntegrationTest extends TestCase
{
    use PppTestHelpers;
    use RefreshDatabase;

    /**
     * Test GET listing returns TND price for Tunisia IP.
     */
    public function test_get_listing_returns_tnd_price_for_tunisia_ip(): void
    {
        $listing = $this->createDualPricedListing();
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", [
            'REMOTE_ADDR' => $tunisiaIP,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.pricing.displayCurrency', 'TND');
        $response->assertJsonPath('data.pricing.displayPrice', 150);
    }

    /**
     * Test GET listing returns EUR price for France IP.
     */
    public function test_get_listing_returns_eur_price_for_france_ip(): void
    {
        $listing = $this->createDualPricedListing();
        $franceIP = '78.193.67.1';
        $this->mockFranceIP($franceIP);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", [
            'REMOTE_ADDR' => $franceIP,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.pricing.displayCurrency', 'EUR');
        $response->assertJsonPath('data.pricing.displayPrice', 50);
    }

    /**
     * Test POST verify-billing with Tunisia → Tunisia (no change).
     */
    public function test_verify_billing_tunisia_to_tunisia_no_change(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'currency' => 'TND',
            'price_snapshot' => 300,
            'pricing_country_code' => 'TN',
        ]);

        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $hold->id,
            'billing_address' => [
                'country_code' => 'TN',
                'city' => 'Tunis',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pricing.price_changed', false);
        $response->assertJsonPath('pricing.original_currency', 'TND');
        $response->assertJsonPath('pricing.final_currency', 'TND');
        $response->assertJsonPath('pricing.browse_country', 'TN');
        $response->assertJsonPath('pricing.billing_country', 'TN');
        $response->assertJsonPath('pricing.disclosure_required', false);
        $response->assertJsonPath('pricing.disclosure_message', null);
    }

    /**
     * Test POST verify-billing with Tunisia → France (price change).
     */
    public function test_verify_billing_tunisia_to_france_price_change(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'currency' => 'TND',
            'price_snapshot' => 300,
            'pricing_country_code' => 'TN',
        ]);

        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $hold->id,
            'billing_address' => [
                'country_code' => 'FR',
                'city' => 'Paris',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pricing.price_changed', true);
        $response->assertJsonPath('pricing.original_currency', 'TND');
        $response->assertJsonPath('pricing.final_currency', 'EUR');
        $response->assertJsonPath('pricing.browse_country', 'TN');
        $response->assertJsonPath('pricing.billing_country', 'FR');
        $response->assertJsonPath('pricing.disclosure_required', true);

        // Verify disclosure message exists
        $this->assertNotNull($response->json('pricing.disclosure_message'));
        $this->assertStringContainsString('based on your billing country', $response->json('pricing.disclosure_message'));
    }

    /**
     * Test POST verify-billing with France → Tunisia (price change).
     */
    public function test_verify_billing_france_to_tunisia_price_change(): void
    {
        $hold = $this->createEURHold(null, [
            'currency' => 'EUR',
            'price_snapshot' => 100,
            'pricing_country_code' => 'FR',
        ]);

        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $hold->id,
            'billing_address' => [
                'country_code' => 'TN',
                'city' => 'Tunis',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pricing.price_changed', true);
        $response->assertJsonPath('pricing.original_currency', 'EUR');
        $response->assertJsonPath('pricing.final_currency', 'TND');
        $response->assertJsonPath('pricing.browse_country', 'FR');
        $response->assertJsonPath('pricing.billing_country', 'TN');
        $response->assertJsonPath('pricing.disclosure_required', true);
    }

    /**
     * Test POST verify-billing with expired hold returns 410 error.
     */
    public function test_verify_billing_with_expired_hold_returns_410(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'expires_at' => now()->subMinutes(5), // Expired 5 minutes ago
        ]);

        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $hold->id,
            'billing_address' => [
                'country_code' => 'FR',
                'city' => 'Paris',
            ],
        ]);

        $response->assertStatus(410);
        $response->assertJsonPath('error', 'Hold has expired');
        $response->assertJsonPath('code', 'HOLD_EXPIRED');
    }

    /**
     * Test POST hold creation stores currency correctly.
     */
    public function test_hold_creation_stores_currency_correctly(): void
    {
        $listing = $this->createDualPricedListing();
        $slot = \App\Models\AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'base_price' => 150,
            'currency' => 'TND',
        ]);

        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $response = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_type_breakdown' => [
                'adult' => 2,
            ],
            'session_id' => 'test-session-' . uniqid(),
        ], [
            'REMOTE_ADDR' => $tunisiaIP,
        ]);

        $response->assertCreated();

        $holdId = $response->json('data.id');
        $hold = BookingHold::find($holdId);

        $this->assertEquals('TND', $hold->currency, 'Hold should store TND currency for Tunisia IP');
        $this->assertEquals('TN', $hold->pricing_country_code);
        $this->assertNotNull($hold->price_snapshot);
    }

    /**
     * Test POST booking with billing address stores all fields.
     */
    public function test_booking_with_billing_address_stores_all_fields(): void
    {
        $hold = $this->createTunisiaHold();

        $response = $this->postJson('/api/v1/bookings', [
            'hold_id' => $hold->id,
            'session_id' => $hold->session_id,
            'travelers' => [
                [
                    'email' => 'test@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'billing_address' => [
                        'country_code' => 'TN',
                        'city' => 'Tunis',
                        'postal_code' => '1000',
                        'address_line1' => '123 Main St',
                        'address_line2' => 'Apt 5',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();

        $bookingId = $response->json('data.id');
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'billing_country_code' => 'TN',
            'billing_city' => 'Tunis',
            'billing_postal_code' => '1000',
            'billing_address_line1' => '123 Main St',
            'billing_address_line2' => 'Apt 5',
            'currency' => 'TND',
        ]);
    }

    /**
     * Test complete flow: Tunisia user end-to-end.
     *
     * 1. Browse listing with Tunisia IP → sees TND price
     * 2. Create hold → captures TND currency
     * 3. Enter Tunisia billing → no price change
     * 4. Create booking → stores TND currency and billing info
     */
    public function test_complete_flow_tunisia_user_end_to_end(): void
    {
        $listing = $this->createDualPricedListing();
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        // Step 1: Browse listing
        $browseResponse = $this->getJson("/api/v1/listings/{$listing->slug}", [
            'REMOTE_ADDR' => $tunisiaIP,
        ]);
        $browseResponse->assertOk();
        $browseResponse->assertJsonPath('data.pricing.displayCurrency', 'TND');
        $browseResponse->assertJsonPath('data.pricing.displayPrice', 150);

        // Step 2: Create hold
        $slot = \App\Models\AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'base_price' => 150,
            'currency' => 'TND',
        ]);

        $sessionId = 'test-session-' . uniqid();
        $holdResponse = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_type_breakdown' => ['adult' => 2],
            'session_id' => $sessionId,
        ], [
            'REMOTE_ADDR' => $tunisiaIP,
        ]);
        $holdResponse->assertCreated();
        $holdId = $holdResponse->json('data.id');

        // Step 3: Verify billing (same country)
        $verifyResponse = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $holdId,
            'billing_address' => [
                'country_code' => 'TN',
                'city' => 'Tunis',
            ],
        ]);
        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('pricing.price_changed', false);

        // Step 4: Create booking
        $bookingResponse = $this->postJson('/api/v1/bookings', [
            'hold_id' => $holdId,
            'session_id' => $sessionId,
            'travelers' => [
                [
                    'email' => 'tunisia-user@example.com',
                    'first_name' => 'Amina',
                    'last_name' => 'Ben Ali',
                    'billing_address' => [
                        'country_code' => 'TN',
                        'city' => 'Tunis',
                    ],
                ],
            ],
        ]);
        $bookingResponse->assertCreated();

        $bookingId = $bookingResponse->json('data.id');
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'currency' => 'TND',
            'billing_country_code' => 'TN',
        ]);
    }

    /**
     * Test complete flow: VPN user (Tunisia IP, France billing).
     *
     * User browses with Tunisia IP (sees TND price) but enters France billing
     * address (price changes to EUR).
     */
    public function test_complete_flow_vpn_user_tunisia_ip_france_billing(): void
    {
        $listing = $this->createDualPricedListing();
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        // Step 1: Browse with Tunisia IP
        $browseResponse = $this->getJson("/api/v1/listings/{$listing->slug}", [
            'REMOTE_ADDR' => $tunisiaIP,
        ]);
        $browseResponse->assertOk();
        $browseResponse->assertJsonPath('data.pricing.displayCurrency', 'TND');

        // Step 2: Create hold with Tunisia pricing
        $slot = \App\Models\AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'base_price' => 150,
            'currency' => 'TND',
        ]);

        $sessionId = 'test-session-' . uniqid();
        $holdResponse = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_type_breakdown' => ['adult' => 2],
            'session_id' => $sessionId,
        ], [
            'REMOTE_ADDR' => $tunisiaIP,
        ]);
        $holdId = $holdResponse->json('data.id');

        // Step 3: Verify with France billing (price change!)
        $verifyResponse = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $holdId,
            'billing_address' => [
                'country_code' => 'FR',
                'city' => 'Paris',
            ],
        ]);
        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('pricing.price_changed', true);
        $verifyResponse->assertJsonPath('pricing.disclosure_required', true);

        // Step 4: Create booking with France billing
        $bookingResponse = $this->postJson('/api/v1/bookings', [
            'hold_id' => $holdId,
            'session_id' => $sessionId,
            'travelers' => [
                [
                    'email' => 'vpn-user@example.com',
                    'first_name' => 'Pierre',
                    'last_name' => 'Dupont',
                    'billing_address' => [
                        'country_code' => 'FR',
                        'city' => 'Paris',
                    ],
                ],
            ],
        ]);
        $bookingResponse->assertCreated();

        $bookingId = $bookingResponse->json('data.id');
        $booking = \App\Models\Booking::find($bookingId);

        // Verify pricing snapshot captured the change
        $this->assertNotNull($booking->pricing_snapshot);
        $this->assertTrue($booking->pricing_snapshot['price_changed']);
        $this->assertEquals('TN', $booking->pricing_snapshot['browse_country']);
        $this->assertEquals('FR', $booking->pricing_snapshot['final_country']);
    }

    /**
     * Test complete flow: Expat user (France IP, Tunisia billing).
     *
     * User browses with France IP (sees EUR price) but enters Tunisia billing
     * address (price changes to TND - likely a price reduction).
     */
    public function test_complete_flow_expat_user_france_ip_tunisia_billing(): void
    {
        $listing = $this->createDualPricedListing();
        $franceIP = '78.193.67.1';
        $this->mockFranceIP($franceIP);

        // Step 1: Browse with France IP
        $browseResponse = $this->getJson("/api/v1/listings/{$listing->slug}", [
            'REMOTE_ADDR' => $franceIP,
        ]);
        $browseResponse->assertOk();
        $browseResponse->assertJsonPath('data.pricing.displayCurrency', 'EUR');

        // Step 2: Create hold with EUR pricing
        $slot = \App\Models\AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'base_price' => 50,
            'currency' => 'EUR',
        ]);

        $sessionId = 'test-session-' . uniqid();
        $holdResponse = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_type_breakdown' => ['adult' => 2],
            'session_id' => $sessionId,
        ], [
            'REMOTE_ADDR' => $franceIP,
        ]);
        $holdId = $holdResponse->json('data.id');

        // Step 3: Verify with Tunisia billing (price change to TND)
        $verifyResponse = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $holdId,
            'billing_address' => [
                'country_code' => 'TN',
                'city' => 'Tunis',
            ],
        ]);
        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('pricing.price_changed', true);
        $verifyResponse->assertJsonPath('pricing.final_currency', 'TND');

        // Step 4: Create booking with Tunisia billing
        $bookingResponse = $this->postJson('/api/v1/bookings', [
            'hold_id' => $holdId,
            'session_id' => $sessionId,
            'travelers' => [
                [
                    'email' => 'expat@example.com',
                    'first_name' => 'Jean',
                    'last_name' => 'Martin',
                    'billing_address' => [
                        'country_code' => 'TN',
                        'city' => 'Tunis',
                    ],
                ],
            ],
        ]);
        $bookingResponse->assertCreated();

        $bookingId = $bookingResponse->json('data.id');
        $booking = \App\Models\Booking::find($bookingId);

        $this->assertEquals('TN', $booking->billing_country_code);
        $this->assertNotNull($booking->pricing_snapshot);
        $this->assertTrue($booking->pricing_snapshot['price_changed']);
    }

    /**
     * Test authenticated user with billing country set.
     */
    public function test_authenticated_user_with_billing_country(): void
    {
        $user = $this->createUserWithBillingCountry('TN');
        $this->actingAs($user, 'sanctum');

        $listing = $this->createDualPricedListing();
        $franceIP = '78.193.67.1'; // User is in France but has Tunisia billing
        $this->mockFranceIP($franceIP);

        $response = $this->getJson("/api/v1/listings/{$listing->slug}", [
            'REMOTE_ADDR' => $franceIP,
        ]);

        $response->assertOk();
        // Billing country should override IP detection
        $response->assertJsonPath('data.pricing.displayCurrency', 'TND');
    }

    /**
     * Test validation errors for verify-billing endpoint.
     */
    public function test_verify_billing_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['hold_id', 'billing_address']);
    }

    /**
     * Test verify-billing with invalid country code.
     */
    public function test_verify_billing_with_invalid_country_code(): void
    {
        $hold = $this->createTunisiaHold();

        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $hold->id,
            'billing_address' => [
                'country_code' => 'INVALID', // Should be 2 characters
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['billing_address.country_code']);
    }

    /**
     * Test that converted hold cannot be used for billing verification.
     */
    public function test_converted_hold_cannot_be_verified(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'status' => HoldStatus::CONVERTED,
        ]);

        $response = $this->postJson('/api/v1/checkout/verify-billing', [
            'hold_id' => $hold->id,
            'billing_address' => [
                'country_code' => 'FR',
            ],
        ]);

        // Converted holds should be treated as expired/invalid
        $response->assertStatus(410);
    }
}
