<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Partner;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected string $apiKey;
    protected string $apiSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test partner
        $this->partner = Partner::factory()->create([
            'is_active' => true,
            'permissions' => ['listings:read', 'bookings:read', 'bookings:create'],
        ]);

        $this->apiKey = $this->partner->api_key;
        $this->apiSecret = $this->partner->api_secret;
    }

    /**
     * Test partner can authenticate with valid credentials.
     */
    public function test_partner_can_authenticate_with_valid_credentials(): void
    {
        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test authentication fails with invalid API key.
     */
    public function test_authentication_fails_with_invalid_api_key(): void
    {
        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => 'invalid-key',
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test authentication fails with invalid API secret.
     */
    public function test_authentication_fails_with_invalid_api_secret(): void
    {
        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => 'invalid-secret',
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test inactive partner cannot authenticate.
     */
    public function test_inactive_partner_cannot_authenticate(): void
    {
        // Arrange
        $this->partner->update(['is_active' => false]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test partner can list available listings.
     */
    public function test_partner_can_list_listings(): void
    {
        // Arrange
        Listing::factory()->count(5)->create(['is_published' => true]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/listings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'listings' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'price',
                        'availability_count',
                    ],
                ],
            ])
            ->assertJsonCount(5, 'listings');
    }

    /**
     * Test partner can view single listing details.
     */
    public function test_partner_can_view_listing_details(): void
    {
        // Arrange
        $listing = Listing::factory()->create(['is_published' => true]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson("/api/partner/v1/listings/{$listing->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'listing' => [
                    'id',
                    'title',
                    'description',
                    'price',
                    'availability',
                ],
            ]);
    }

    /**
     * Test partner can search listings.
     */
    public function test_partner_can_search_listings(): void
    {
        // Arrange
        Listing::factory()->create([
            'title' => 'Hiking Adventure',
            'is_published' => true,
        ]);
        Listing::factory()->create([
            'title' => 'Beach Tour',
            'is_published' => true,
        ]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/search?q=hiking');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'listings')
            ->assertJsonFragment(['title' => 'Hiking Adventure']);
    }

    /**
     * Test partner can create booking.
     */
    public function test_partner_can_create_booking(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $slot = \App\Models\AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'booked_count' => 0,
        ]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->postJson('/api/partner/v1/bookings', [
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_type_breakdown' => [
                'adults' => 2,
            ],
            'traveler_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
            ],
            'billing_contact' => [
                'email' => 'john@example.com',
            ],
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'booking' => [
                    'id',
                    'booking_number',
                    'status',
                    'total_amount',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'listing_id' => $listing->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    /**
     * Test partner cannot create booking without permission.
     */
    public function test_partner_cannot_create_booking_without_permission(): void
    {
        // Arrange
        $this->partner->update(['permissions' => ['listings:read']]);
        $listing = Listing::factory()->create();

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->postJson('/api/partner/v1/bookings', [
            'listing_id' => $listing->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test partner can view their bookings.
     */
    public function test_partner_can_view_their_bookings(): void
    {
        // Arrange
        Booking::factory()->count(3)->create([
            'partner_id' => $this->partner->id,
        ]);

        // Create booking for different partner (should not be returned)
        Booking::factory()->create([
            'partner_id' => Partner::factory()->create()->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/bookings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'bookings');
    }

    /**
     * Test partner API requests are rate limited.
     */
    public function test_partner_api_requests_are_rate_limited(): void
    {
        // Arrange
        $this->partner->update(['rate_limit' => 5]);

        // Act - Make 6 requests
        for ($i = 0; $i < 6; $i++) {
            $response = $this->withHeaders([
                'X-Partner-Key' => $this->apiKey,
                'X-Partner-Secret' => $this->apiSecret,
            ])->getJson('/api/partner/v1/dashboard');
        }

        // Assert - 6th request should be rate limited
        $response->assertStatus(429);
    }

    /**
     * Test partner API calls are audited.
     */
    public function test_partner_api_calls_are_audited(): void
    {
        // Act
        $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $this->assertDatabaseHas('partner_audit_logs', [
            'partner_id' => $this->partner->id,
            'endpoint' => '/api/partner/v1/dashboard',
            'method' => 'GET',
        ]);
    }

    /**
     * Test partner can view transaction history.
     */
    public function test_partner_can_view_transaction_history(): void
    {
        // Arrange
        \App\Models\PartnerTransaction::factory()->count(5)->create([
            'partner_id' => $this->partner->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/transactions');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'transactions' => [
                    '*' => [
                        'id',
                        'amount',
                        'type',
                        'status',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(5, 'transactions');
    }

    /**
     * Test partner webhook notifications.
     */
    public function test_partner_webhook_notifications(): void
    {
        // Arrange
        $this->partner->update([
            'webhook_url' => 'https://partner.example.com/webhook',
            'webhook_secret' => 'test-secret',
        ]);

        $booking = Booking::factory()->create([
            'partner_id' => $this->partner->id,
        ]);

        // Act - Trigger booking confirmed event
        // This would normally be handled by the webhook service
        $webhookPayload = [
            'event' => 'booking.confirmed',
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
        ];

        // Assert webhook was queued
        $this->assertDatabaseHas('partner_webhooks', [
            'partner_id' => $this->partner->id,
            'event' => 'booking.confirmed',
        ]);
    }

    /**
     * Test partner dashboard statistics.
     */
    public function test_partner_dashboard_statistics(): void
    {
        // Arrange
        Booking::factory()->count(10)->create([
            'partner_id' => $this->partner->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'total_bookings',
                    'total_revenue',
                    'pending_bookings',
                    'confirmed_bookings',
                ],
            ]);
    }

    /**
     * Test partner IP whitelist enforcement.
     */
    public function test_partner_ip_whitelist_enforcement(): void
    {
        // Arrange
        $this->partner->update([
            'ip_whitelist' => ['192.168.1.1', '10.0.0.1'],
        ]);

        // Act - Request from non-whitelisted IP
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
            'X-Forwarded-For' => '203.0.113.1',
        ])->getJson('/api/partner/v1/dashboard');

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'IP address not whitelisted',
            ]);
    }

    /**
     * Test partner can filter listings by date range.
     */
    public function test_partner_can_filter_listings_by_date_range(): void
    {
        // Arrange
        $listing = Listing::factory()->create(['is_published' => true]);
        
        \App\Models\AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(5),
        ]);

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(10)->format('Y-m-d');

        // Act
        $response = $this->withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'X-Partner-Secret' => $this->apiSecret,
        ])->getJson("/api/partner/v1/listings?start_date={$startDate}&end_date={$endDate}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'listings' => [
                    '*' => [
                        'id',
                        'title',
                        'available_slots',
                    ],
                ],
            ]);
    }
}
