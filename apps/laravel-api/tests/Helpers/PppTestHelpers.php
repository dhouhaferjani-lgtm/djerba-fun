<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\HoldStatus;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Helper trait for PPP (Purchasing Power Parity) pricing tests.
 *
 * Provides reusable methods for creating test data and mocking external services
 * used in PPP pricing tests.
 */
trait PppTestHelpers
{
    /**
     * Create a listing with dual currency pricing (TND and EUR).
     *
     * @param  array  $overrides  Optional overrides for listing attributes
     * @return Listing
     */
    protected function createDualPricedListing(array $overrides = []): Listing
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $location = Location::factory()->create();

        return Listing::factory()->create(array_merge([
            'vendor_id' => $vendor->id,
            'location_id' => $location->id,
            'title' => 'Test Dual-Priced Tour',
            'highlights' => [],
            'included' => [],
            'not_included' => [],
            'requirements' => [],
            'meeting_point' => [],
            'cancellation_policy' => [],
            'pricing' => [
                'currency' => 'TND', // Default display currency
                'tnd_price' => 150, // Tunisia price
                'eur_price' => 50, // International price
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
                        'tnd_price' => 150,
                        'eur_price' => 50,
                        'minAge' => 18,
                        'maxAge' => null,
                    ],
                    [
                        'key' => 'child',
                        'label' => ['en' => 'Child', 'fr' => 'Enfant'],
                        'tnd_price' => 75,
                        'eur_price' => 25,
                        'minAge' => 4,
                        'maxAge' => 17,
                    ],
                    [
                        'key' => 'infant',
                        'label' => ['en' => 'Infant', 'fr' => 'Bébé'],
                        'tnd_price' => 0,
                        'eur_price' => 0,
                        'minAge' => 0,
                        'maxAge' => 3,
                    ],
                ],
                'group_discount' => [
                    'min_size' => 6,
                    'discount_percent' => 10,
                ],
            ],
        ], $overrides));
    }

    /**
     * Create a booking hold with TND currency (Tunisia pricing).
     *
     * @param  Listing|null  $listing  Optional listing (creates one if not provided)
     * @param  array  $overrides  Optional overrides for hold attributes
     * @return BookingHold
     */
    protected function createTunisiaHold(?Listing $listing = null, array $overrides = []): BookingHold
    {
        if (! $listing) {
            $listing = $this->createDualPricedListing();
        }

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'base_price' => 150,
            'currency' => 'TND',
        ]);

        return BookingHold::create(array_merge([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => null,
            'session_id' => 'test-session-123',
            'quantity' => 2,
            'person_type_breakdown' => [
                'adult' => 2,
            ],
            'currency' => 'TND',
            'price_snapshot' => 300, // 2 adults × 150 TND
            'pricing_country_code' => 'TN',
            'pricing_source' => 'ip_geo',
            'expires_at' => now()->addMinutes(15),
            'status' => HoldStatus::ACTIVE,
        ], $overrides));
    }

    /**
     * Create a booking hold with EUR currency (international pricing).
     *
     * @param  Listing|null  $listing  Optional listing (creates one if not provided)
     * @param  array  $overrides  Optional overrides for hold attributes
     * @return BookingHold
     */
    protected function createEURHold(?Listing $listing = null, array $overrides = []): BookingHold
    {
        if (! $listing) {
            $listing = $this->createDualPricedListing();
        }

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'base_price' => 50,
            'currency' => 'EUR',
        ]);

        return BookingHold::create(array_merge([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => null,
            'session_id' => 'test-session-456',
            'quantity' => 2,
            'person_type_breakdown' => [
                'adult' => 2,
            ],
            'currency' => 'EUR',
            'price_snapshot' => 100, // 2 adults × 50 EUR
            'pricing_country_code' => 'FR',
            'pricing_source' => 'ip_geo',
            'expires_at' => now()->addMinutes(15),
            'status' => HoldStatus::ACTIVE,
        ], $overrides));
    }

    /**
     * Mock IP geolocation API to return Tunisia.
     *
     * @param  string  $ip  IP address to mock (defaults to 41.230.62.1)
     * @return void
     */
    protected function mockTunisiaIP(string $ip = '41.230.62.1'): void
    {
        Http::fake([
            "ip-api.com/json/{$ip}*" => Http::response([
                'status' => 'success',
                'countryCode' => 'TN',
                'country' => 'Tunisia',
            ], 200),
        ]);
    }

    /**
     * Mock IP geolocation API to return France.
     *
     * @param  string  $ip  IP address to mock (defaults to 78.193.67.1)
     * @return void
     */
    protected function mockFranceIP(string $ip = '78.193.67.1'): void
    {
        Http::fake([
            "ip-api.com/json/{$ip}*" => Http::response([
                'status' => 'success',
                'countryCode' => 'FR',
                'country' => 'France',
            ], 200),
        ]);
    }

    /**
     * Mock IP geolocation API to return a specific country.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @param  string  $countryName  Full country name
     * @param  string  $ip  IP address to mock
     * @return void
     */
    protected function mockIPGeolocation(string $countryCode, string $countryName, string $ip = '1.2.3.4'): void
    {
        Http::fake([
            "ip-api.com/json/{$ip}*" => Http::response([
                'status' => 'success',
                'countryCode' => $countryCode,
                'country' => $countryName,
            ], 200),
        ]);
    }

    /**
     * Mock IP geolocation API to fail.
     *
     * @param  string  $ip  IP address to mock
     * @return void
     */
    protected function mockIPGeolocationFailure(string $ip = '1.2.3.4'): void
    {
        Http::fake([
            "ip-api.com/json/{$ip}*" => Http::response([
                'status' => 'fail',
                'message' => 'Invalid query',
            ], 200),
        ]);
    }

    /**
     * Assert that a pricing snapshot has the required structure.
     *
     * Validates that a pricing_snapshot field contains all necessary
     * fields for PPP pricing transparency and audit purposes.
     *
     * @param  array  $snapshot  The pricing snapshot to validate
     * @param  string|null  $message  Optional assertion message
     * @return void
     */
    protected function assertPricingSnapshot(array $snapshot, ?string $message = null): void
    {
        $message = $message ?? 'Pricing snapshot is missing required fields';

        // Required fields for transparency
        $this->assertArrayHasKey('browse_currency', $snapshot, $message);
        $this->assertArrayHasKey('browse_price', $snapshot, $message);
        $this->assertArrayHasKey('browse_country', $snapshot, $message);
        $this->assertArrayHasKey('browse_source', $snapshot, $message);
        $this->assertArrayHasKey('final_currency', $snapshot, $message);
        $this->assertArrayHasKey('final_price', $snapshot, $message);
        $this->assertArrayHasKey('final_country', $snapshot, $message);
        $this->assertArrayHasKey('price_changed', $snapshot, $message);
        $this->assertArrayHasKey('timestamp', $snapshot, $message);

        // Validate data types
        $this->assertIsString($snapshot['browse_currency']);
        $this->assertIsNumeric($snapshot['browse_price']);
        $this->assertIsString($snapshot['final_currency']);
        $this->assertIsNumeric($snapshot['final_price']);
        $this->assertIsBool($snapshot['price_changed']);
        $this->assertIsString($snapshot['timestamp']);
    }

    /**
     * Create a user with billing country set in their profile.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @param  array  $overrides  Optional user attribute overrides
     * @return User
     */
    protected function createUserWithBillingCountry(string $countryCode, array $overrides = []): User
    {
        $user = User::factory()->create($overrides);

        // Create traveler profile with billing country
        $user->travelerProfile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'preferences' => [
                'billing_country' => $countryCode,
            ],
        ]);

        return $user;
    }
}
