<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Mail\MagicLoginMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sending magic link via API.
     */
    public function test_send_magic_link_via_api(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/send', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);

        Mail::assertQueued(MagicLoginMail::class);
    }

    /**
     * Test sending magic link with invalid email format.
     */
    public function test_send_magic_link_with_invalid_email(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/send', [
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test verifying valid magic link.
     */
    public function test_verify_valid_magic_link(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-magic-token';

        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->addMinutes(15),
            'magic_token_used_at' => null,
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'test@example.com',
            'token' => $token,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token', // Auth token
            ]);

        // Verify user is authenticated
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test verifying expired magic link.
     */
    public function test_verify_expired_magic_link(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-magic-token';

        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->subMinutes(1),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'test@example.com',
            'token' => $token,
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJsonFragment([
                'message' => 'Token has expired',
            ]);
    }

    /**
     * Test verifying invalid magic link token.
     */
    public function test_verify_invalid_magic_link_token(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);

        $user->update([
            'magic_token_hash' => Hash::make('correct-token'),
            'magic_token_expires_at' => now()->addMinutes(15),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'test@example.com',
            'token' => 'wrong-token',
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJsonFragment([
                'message' => 'Invalid token',
            ]);
    }

    /**
     * Test magic link can only be used once.
     */
    public function test_magic_link_can_only_be_used_once(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-magic-token';

        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->addMinutes(15),
        ]);

        // Act - First use
        $response1 = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'test@example.com',
            'token' => $token,
        ]);

        // Second use (should fail)
        $response2 = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'test@example.com',
            'token' => $token,
        ]);

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(401)
            ->assertJsonFragment([
                'message' => 'Token has already been used',
            ]);
    }

    /**
     * Test rate limiting for magic link requests.
     */
    public function test_rate_limiting_for_magic_link_requests(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act - Send multiple requests (up to limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/v1/auth/magic-link/send', [
                'email' => 'test@example.com',
            ]);
            $response->assertStatus(200);
        }

        // One more should still work but not send email (rate limited)
        $response = $this->postJson('/api/v1/auth/magic-link/send', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        // Only 3 emails sent
        Mail::assertQueuedCount(3);
    }

    /**
     * Test passwordless registration flow.
     */
    public function test_passwordless_registration(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/passwordless/register', [
            'email' => 'newuser@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Verify no password was set
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNull($user->password);
    }

    /**
     * Test passwordless registration with duplicate email.
     */
    public function test_passwordless_registration_with_duplicate_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->postJson('/api/v1/auth/passwordless/register', [
            'email' => 'existing@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test magic link login creates user session.
     */
    public function test_magic_link_login_creates_session(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-magic-token';

        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->addMinutes(15),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/magic-link/verify', [
            'email' => 'test@example.com',
            'token' => $token,
        ]);

        // Assert
        $response->assertStatus(200);
        $authToken = $response->json('token');
        $this->assertNotNull($authToken);

        // Verify we can use the token
        $meResponse = $this->withHeader('Authorization', "Bearer {$authToken}")
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }
}
