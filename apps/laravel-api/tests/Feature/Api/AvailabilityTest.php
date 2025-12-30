<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AvailabilitySlot;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching availability for a listing.
     */
    public function test_fetch_listing_availability(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        AvailabilitySlot::factory()->count(5)->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(1),
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/availability");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'slots' => [
                    '*' => [
                        'id',
                        'start_datetime',
                        'end_datetime',
                        'capacity',
                        'booked_count',
                        'available_count',
                    ],
                ],
            ]);
    }

    /**
     * Test filtering availability by date range.
     */
    public function test_filter_availability_by_date_range(): void
    {
        // Arrange
        $listing = Listing::factory()->create();

        // Create slots for different dates
        AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(5),
        ]);

        AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'start_datetime' => now()->addDays(15),
        ]);

        // Act - Query for slots in next 10 days
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/availability?" . http_build_query([
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(10)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('slots'));
    }

    /**
     * Test availability shows correct capacity information.
     */
    public function test_availability_shows_correct_capacity(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'booked_count' => 3,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/availability");

        // Assert
        $response->assertStatus(200);
        $slotData = collect($response->json('slots'))->firstWhere('id', $slot->id);

        $this->assertEquals(10, $slotData['capacity']);
        $this->assertEquals(3, $slotData['booked_count']);
        $this->assertEquals(7, $slotData['available_count']);
    }

    /**
     * Test creating a hold on available slot.
     */
    public function test_create_hold_on_available_slot(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
            'booked_count' => 0,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 2,
                'person_type_breakdown' => [
                    'adults' => 2,
                ],
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'hold' => [
                    'id',
                    'expires_at',
                    'status',
                    'price_snapshot',
                ],
            ]);

        $this->assertDatabaseHas('booking_holds', [
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => $user->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Test creating hold fails when capacity exceeded.
     */
    public function test_create_hold_fails_when_capacity_exceeded(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 5,
            'booked_count' => 5,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 1,
                'person_type_breakdown' => [
                    'adults' => 1,
                ],
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Not enough capacity available',
            ]);
    }

    /**
     * Test guest can create hold with session ID.
     */
    public function test_guest_can_create_hold_with_session(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
        ]);

        // Act
        $response = $this->postJson("/api/v1/listings/{$listing->slug}/holds", [
            'slot_id' => $slot->id,
            'quantity' => 2,
            'person_type_breakdown' => [
                'adults' => 2,
            ],
        ]);

        // Assert
        $response->assertStatus(201);
        $hold = $response->json('hold');

        $this->assertDatabaseHas('booking_holds', [
            'id' => $hold['id'],
            'user_id' => null,
        ]);

        // Session ID should be generated
        $this->assertNotNull($hold['session_id'] ?? null);
    }

    /**
     * Test hold expiration time is set correctly.
     */
    public function test_hold_expiration_time_set_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 1,
                'person_type_breakdown' => [
                    'adults' => 1,
                ],
            ]);

        // Assert
        $response->assertStatus(201);
        $expiresAt = $response->json('hold.expires_at');

        $this->assertNotNull($expiresAt);

        // Verify expiration is approximately 15 minutes from now
        $expirationTime = \Carbon\Carbon::parse($expiresAt);
        $expectedExpiration = now()->addMinutes(15);

        $this->assertTrue(
            $expirationTime->between(
                $expectedExpiration->copy()->subMinute(),
                $expectedExpiration->copy()->addMinute()
            )
        );
    }

    /**
     * Test cannot create duplicate holds for same slot.
     */
    public function test_cannot_create_duplicate_holds_for_same_slot(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
        ]);

        // Act - Create first hold
        $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 1,
                'person_type_breakdown' => ['adults' => 1],
            ]);

        // Try to create another hold for same slot
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 1,
                'person_type_breakdown' => ['adults' => 1],
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You already have an active hold for this slot',
            ]);
    }

    /**
     * Test hold includes price snapshot.
     */
    public function test_hold_includes_price_snapshot(): void
    {
        // Arrange
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'adult_price_tnd' => 100.00,
            'adult_price_eur' => 30.00,
        ]);
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/listings/{$listing->slug}/holds", [
                'slot_id' => $slot->id,
                'quantity' => 2,
                'person_type_breakdown' => [
                    'adults' => 2,
                ],
            ]);

        // Assert
        $response->assertStatus(201);
        $hold = $response->json('hold');

        $this->assertNotNull($hold['price_snapshot']);
        $this->assertGreaterThan(0, $hold['price_snapshot']);
    }
}
