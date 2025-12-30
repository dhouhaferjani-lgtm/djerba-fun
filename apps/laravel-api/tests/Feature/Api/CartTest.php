<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AvailabilitySlot;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can create a cart.
     */
    public function test_user_can_create_cart(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart');

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'cart' => [
                    'id',
                    'status',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'status' => Cart::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test guest can create a cart with session.
     */
    public function test_guest_can_create_cart(): void
    {
        // Act
        $response = $this->postJson('/api/v1/cart');

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'cart' => [
                    'id',
                    'status',
                    'session_id',
                ],
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => null,
            'status' => Cart::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test user can add item to cart.
     */
    public function test_user_can_add_item_to_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/cart/{$cart->id}/items", [
                'listing_id' => $listing->id,
                'availability_slot_id' => $slot->id,
                'quantity' => 2,
                'person_type_breakdown' => [
                    'adults' => 2,
                ],
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'cart_item' => [
                    'id',
                    'listing',
                    'quantity',
                    'total_price',
                ],
            ]);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Test adding item to cart creates a hold.
     */
    public function test_adding_item_to_cart_creates_hold(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 10,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/cart/{$cart->id}/items", [
                'listing_id' => $listing->id,
                'availability_slot_id' => $slot->id,
                'quantity' => 2,
                'person_type_breakdown' => [
                    'adults' => 2,
                ],
            ]);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('booking_holds', [
            'cart_id' => $cart->id,
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
        ]);
    }

    /**
     * Test user can remove item from cart.
     */
    public function test_user_can_remove_item_from_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/cart/{$cart->id}/items/{$item->id}");

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    /**
     * Test user can update item quantity in cart.
     */
    public function test_user_can_update_item_quantity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'quantity' => 2,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patchJson("/api/v1/cart/{$cart->id}/items/{$item->id}", [
                'quantity' => 4,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'quantity' => 4,
            ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 4,
        ]);
    }

    /**
     * Test user can view cart details.
     */
    public function test_user_can_view_cart_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        CartItem::factory()->count(3)->create([
            'cart_id' => $cart->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/v1/cart/{$cart->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'cart' => [
                    'id',
                    'status',
                    'items',
                    'total_amount',
                ],
            ])
            ->assertJsonCount(3, 'cart.items');
    }

    /**
     * Test user can checkout cart.
     */
    public function test_user_can_checkout_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        CartItem::factory()->count(2)->withHold()->create([
            'cart_id' => $cart->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/cart/{$cart->id}/checkout", [
                'traveler_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                ],
                'billing_contact' => [
                    'email' => 'john@example.com',
                ],
                'billing_country_code' => 'CA',
                'billing_city' => 'Toronto',
                'billing_postal_code' => 'M5H 2N2',
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'bookings',
                'payment',
            ]);

        $cart->refresh();
        $this->assertEquals(Cart::STATUS_COMPLETED, $cart->status);
    }

    /**
     * Test checkout fails with expired cart.
     */
    public function test_checkout_fails_with_expired_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->expired()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/cart/{$cart->id}/checkout", [
                'traveler_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                ],
                'billing_contact' => [
                    'email' => 'john@example.com',
                ],
                'payment_method' => 'mock',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Cart has expired',
            ]);
    }

    /**
     * Test user can only access their own cart.
     */
    public function test_user_can_only_access_own_cart(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $cart = Cart::factory()->forUser($user1)->create();

        // Act
        $response = $this->actingAs($user2)
            ->getJson("/api/v1/cart/{$cart->id}");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test cart automatically extends expiration on activity.
     */
    public function test_cart_extends_expiration_on_activity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create([
            'expires_at' => now()->addMinutes(5),
        ]);
        $originalExpiration = $cart->expires_at;

        // Wait a moment to ensure time difference
        sleep(1);

        // Act
        $listing = Listing::factory()->create();
        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/cart/{$cart->id}/items", [
                'listing_id' => $listing->id,
                'availability_slot_id' => $slot->id,
                'quantity' => 1,
                'person_type_breakdown' => ['adults' => 1],
            ]);

        // Assert
        $cart->refresh();
        $this->assertGreaterThan($originalExpiration, $cart->expires_at);
    }
}
