<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Models\TravelerProfile;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create and authenticate a user with token.
     */
    protected function createAndAuthenticateUser(array $attributes = [], UserRole $role = UserRole::TRAVELER): array
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
        ], $attributes));

        if ($role === UserRole::TRAVELER) {
            TravelerProfile::factory()->create(['user_id' => $user->id]);
        } elseif ($role === UserRole::VENDOR) {
            VendorProfile::factory()->create(['user_id' => $user->id]);
        }

        $token = $user->createToken('test-token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * Create a test user with specific role.
     */
    protected function createUser(UserRole $role = UserRole::TRAVELER, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
        ], $attributes));

        if ($role === UserRole::TRAVELER) {
            TravelerProfile::factory()->create(['user_id' => $user->id]);
        } elseif ($role === UserRole::VENDOR) {
            VendorProfile::factory()->create(['user_id' => $user->id]);
        }

        return $user;
    }

    /**
     * Create a listing with availability slot.
     */
    protected function createListingWithAvailability(array $listingAttributes = [], array $slotAttributes = []): array
    {
        $listing = Listing::factory()->create($listingAttributes);

        $slot = AvailabilitySlot::factory()->create(array_merge([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'booked_count' => 0,
        ], $slotAttributes));

        return [$listing, $slot];
    }

    /**
     * Create a booking hold for testing.
     */
    protected function createHold(User $user, Listing $listing, AvailabilitySlot $slot, array $attributes = []): BookingHold
    {
        return BookingHold::factory()->create(array_merge([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
        ], $attributes));
    }

    /**
     * Create a confirmed booking for testing.
     */
    protected function createConfirmedBooking(User $user, array $attributes = []): Booking
    {
        return Booking::factory()->confirmed()->create(array_merge([
            'user_id' => $user->id,
        ], $attributes));
    }

    /**
     * Get authorization header with bearer token.
     */
    protected function withAuthToken(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    /**
     * Assert JSON validation errors for specific fields.
     */
    protected function assertValidationErrors($response, array $fields): void
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors($fields);
    }

    /**
     * Create standard traveler info for booking tests.
     */
    protected function getTravelerInfo(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ], $overrides);
    }

    /**
     * Create standard billing info for booking tests.
     */
    protected function getBillingInfo(array $overrides = []): array
    {
        return array_merge([
            'billing_contact' => [
                'email' => 'john@example.com',
                'phone' => '+1234567890',
            ],
            'billing_country_code' => 'CA',
            'billing_city' => 'Toronto',
            'billing_postal_code' => 'M5H 2N2',
            'billing_address_line1' => '123 Main St',
        ], $overrides);
    }
}
