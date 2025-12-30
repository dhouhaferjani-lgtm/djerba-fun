<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\TravelerProfile;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can register as traveler with valid data.
     */
    public function test_user_can_register_as_traveler(): void
    {
        // Arrange
        $userData = [
            'email' => 'traveler@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => UserRole::TRAVELER->value,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1234567890',
            'display_name' => 'John Doe',
            'preferred_locale' => 'en',
        ];

        // Act
        $response = $this->postJson('/api/v1/auth/register', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'role',
                    'traveler_profile',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'traveler@example.com',
            'role' => UserRole::TRAVELER->value,
        ]);

        $this->assertDatabaseHas('traveler_profiles', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * Test user can register as vendor with valid data.
     */
    public function test_user_can_register_as_vendor(): void
    {
        // Arrange
        $userData = [
            'email' => 'vendor@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => UserRole::VENDOR->value,
            'company_name' => 'Adventure Co.',
            'company_type' => 'tour_operator',
            'tax_id' => 'TAX123456',
            'display_name' => 'Adventure Co.',
        ];

        // Act
        $response = $this->postJson('/api/v1/auth/register', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'role',
                    'vendor_profile',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'vendor@example.com',
            'role' => UserRole::VENDOR->value,
        ]);

        $this->assertDatabaseHas('vendor_profiles', [
            'company_name' => 'Adventure Co.',
        ]);
    }

    /**
     * Test registration fails with duplicate email.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => UserRole::TRAVELER->value,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        // Act
        $response = $this->postJson('/api/v1/auth/register', $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE,
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'token',
            ]);
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * Test login fails for inactive user.
     */
    public function test_login_fails_for_inactive_user(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::SUSPENDED,
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token',
        ]);
    }

    /**
     * Test authenticated user can fetch their profile.
     */
    public function test_authenticated_user_can_fetch_profile(): void
    {
        // Arrange
        $user = User::factory()->create();
        TravelerProfile::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'role',
                    'traveler_profile',
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot fetch profile.
     */
    public function test_unauthenticated_user_cannot_fetch_profile(): void
    {
        // Act
        $response = $this->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test user can request magic link.
     */
    public function test_user_can_request_magic_link(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'magic@example.com',
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/send', [
            'email' => 'magic@example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Magic link sent to your email',
            ]);

        $user->refresh();
        $this->assertNotNull($user->magic_token);
        $this->assertNotNull($user->magic_token_expires_at);
    }

    /**
     * Test user can verify magic link and login.
     */
    public function test_user_can_verify_magic_link(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'magic@example.com',
        ]);
        $user->generateMagicToken();

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'magic@example.com',
            'token' => $user->magic_token,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    }

    /**
     * Test magic link verification fails with invalid token.
     */
    public function test_magic_link_verification_fails_with_invalid_token(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'magic@example.com',
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'magic@example.com',
            'token' => 'invalid-token',
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * Test magic link verification fails with expired token.
     */
    public function test_magic_link_verification_fails_with_expired_token(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'magic@example.com',
        ]);
        $user->magic_token = 'expired-token';
        $user->magic_token_expires_at = now()->subDay();
        $user->save();

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'magic@example.com',
            'token' => 'expired-token',
        ]);

        // Assert
        $response->assertStatus(422);
    }
}
