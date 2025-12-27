<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PppTestHelpers;
use Tests\TestCase;

/**
 * Test suite for BookingService with PPP pricing support.
 *
 * Validates that bookings correctly capture pricing snapshots, billing addresses,
 * and currency information for transparency and audit purposes.
 */
class BookingServiceTest extends TestCase
{
    use PppTestHelpers;
    use RefreshDatabase;

    protected BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);
    }

    /**
     * Test that booking stores currency from hold.
     */
    public function test_booking_stores_currency_from_hold(): void
    {
        $hold = $this->createTunisiaHold();

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertEquals('TND', $booking->currency, 'Booking should store currency from hold');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'currency' => 'TND',
        ]);
    }

    /**
     * Test that booking total amount is not zero.
     *
     * Ensures price calculation is working and booking captures a valid amount.
     */
    public function test_booking_total_amount_not_zero(): void
    {
        $hold = $this->createTunisiaHold();

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertGreaterThan(0, $booking->total_amount, 'Booking total amount should be greater than zero');
        $this->assertEquals(300, $booking->total_amount, 'Total should be 2 adults × 150 TND = 300');
    }

    /**
     * Test that capturePricingSnapshot creates correct structure.
     *
     * Validates that the pricing snapshot includes all required fields for
     * PPP pricing transparency.
     */
    public function test_capture_pricing_snapshot_creates_correct_structure(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'currency' => 'TND',
            'price_snapshot' => 300,
            'pricing_country_code' => 'TN',
            'pricing_source' => 'ip_geo',
        ]);

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'Alice',
                'last_name' => 'Johnson',
                'billing_address' => [
                    'country_code' => 'TN',
                    'city' => 'Tunis',
                    'postal_code' => '1000',
                    'address_line1' => '123 Main St',
                ],
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertNotNull($booking->pricing_snapshot, 'Pricing snapshot should be captured');
        $this->assertPricingSnapshot($booking->pricing_snapshot);

        // Verify specific values
        $snapshot = $booking->pricing_snapshot;
        $this->assertEquals('TND', $snapshot['browse_currency']);
        $this->assertEquals('TND', $snapshot['final_currency']);
        $this->assertEquals('TN', $snapshot['browse_country']);
        $this->assertEquals('TN', $snapshot['final_country']);
        $this->assertFalse($snapshot['price_changed'], 'Price should not change when countries match');
    }

    /**
     * Test that billing address is stored correctly.
     */
    public function test_billing_address_stored_correctly(): void
    {
        $hold = $this->createTunisiaHold();

        $billingAddress = [
            'country_code' => 'TN',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'address_line1' => '123 Avenue Habib Bourguiba',
            'address_line2' => 'Apt 5',
        ];

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'Bob',
                'last_name' => 'Wilson',
                'billing_address' => $billingAddress,
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertEquals('TN', $booking->billing_country_code);
        $this->assertEquals('Tunis', $booking->billing_city);
        $this->assertEquals('1000', $booking->billing_postal_code);
        $this->assertEquals('123 Avenue Habib Bourguiba', $booking->billing_address_line1);
        $this->assertEquals('Apt 5', $booking->billing_address_line2);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'billing_country_code' => 'TN',
            'billing_city' => 'Tunis',
        ]);
    }

    /**
     * Test that pricing_snapshot includes all required fields.
     */
    public function test_pricing_snapshot_includes_all_required_fields(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'currency' => 'TND',
            'price_snapshot' => 300,
            'pricing_country_code' => 'TN',
            'pricing_source' => 'ip_geo',
        ]);

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'Charlie',
                'last_name' => 'Brown',
                'billing_address' => [
                    'country_code' => 'FR',
                    'city' => 'Paris',
                ],
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $snapshot = $booking->pricing_snapshot;

        // Required fields for transparency
        $this->assertArrayHasKey('browse_currency', $snapshot);
        $this->assertArrayHasKey('browse_price', $snapshot);
        $this->assertArrayHasKey('browse_country', $snapshot);
        $this->assertArrayHasKey('browse_source', $snapshot);
        $this->assertArrayHasKey('final_currency', $snapshot);
        $this->assertArrayHasKey('final_price', $snapshot);
        $this->assertArrayHasKey('final_country', $snapshot);
        $this->assertArrayHasKey('price_changed', $snapshot);
        $this->assertArrayHasKey('timestamp', $snapshot);

        // Verify price change detection
        $this->assertTrue($snapshot['price_changed'], 'Price should change when billing country differs');
        $this->assertEquals('TN', $snapshot['browse_country']);
        $this->assertEquals('FR', $snapshot['final_country']);
    }

    /**
     * Test booking creation with EUR hold and EUR billing (no change).
     */
    public function test_booking_with_eur_hold_and_eur_billing(): void
    {
        $hold = $this->createEURHold(null, [
            'currency' => 'EUR',
            'price_snapshot' => 100,
            'pricing_country_code' => 'FR',
            'pricing_source' => 'ip_geo',
        ]);

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'David',
                'last_name' => 'Miller',
                'billing_address' => [
                    'country_code' => 'FR',
                    'city' => 'Lyon',
                ],
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertEquals('EUR', $booking->currency);
        $this->assertEquals('FR', $booking->billing_country_code);

        $snapshot = $booking->pricing_snapshot;
        $this->assertFalse($snapshot['price_changed'], 'Price should not change when currencies match');
        $this->assertEquals('EUR', $snapshot['browse_currency']);
        $this->assertEquals('EUR', $snapshot['final_currency']);
    }

    /**
     * Test booking creation detects price change (Tunisia IP, France billing).
     */
    public function test_booking_detects_price_change_tunisia_to_france(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'currency' => 'TND',
            'price_snapshot' => 300,
            'pricing_country_code' => 'TN',
            'pricing_source' => 'ip_geo',
        ]);

        $travelers = [
            [
                'email' => 'test@example.com',
                'first_name' => 'Emma',
                'last_name' => 'Davis',
                'billing_address' => [
                    'country_code' => 'FR',
                    'city' => 'Marseille',
                ],
            ],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $snapshot = $booking->pricing_snapshot;

        $this->assertTrue($snapshot['price_changed'], 'Price should change when country changes TN→FR');
        $this->assertEquals('TND', $snapshot['browse_currency']);
        $this->assertEquals('TN', $snapshot['browse_country']);
        $this->assertEquals('FR', $snapshot['final_country']);

        // Note: final_currency depends on recalculation with billing country
        // In this case, it might still be TND if the hold currency is preserved
        $this->assertNotNull($snapshot['final_currency']);
    }

    /**
     * Test booking with minimal traveler info (email only).
     */
    public function test_booking_with_minimal_traveler_info(): void
    {
        $hold = $this->createTunisiaHold();

        // Minimal travelers - only email required
        $travelers = [
            ['email' => 'minimal@example.com'],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('minimal@example.com', $booking->billing_contact['email']);
        $this->assertNull($booking->billing_contact['first_name'] ?? null);
    }

    /**
     * Test that session_id is copied from hold to booking for guest checkout.
     */
    public function test_session_id_copied_from_hold_to_booking(): void
    {
        $hold = $this->createTunisiaHold(null, [
            'session_id' => 'guest-session-xyz',
        ]);

        $travelers = [
            ['email' => 'guest@example.com', 'first_name' => 'Guest', 'last_name' => 'User'],
        ];

        $booking = $this->service->createFromHold($hold, $travelers);

        $this->assertEquals('guest-session-xyz', $booking->session_id, 'Session ID should be copied from hold');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'session_id' => 'guest-session-xyz',
        ]);
    }
}
