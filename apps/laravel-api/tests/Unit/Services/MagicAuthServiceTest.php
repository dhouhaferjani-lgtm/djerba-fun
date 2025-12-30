<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Mail\MagicLoginMail;
use App\Models\User;
use App\Services\MagicAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MagicAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MagicAuthService::class);
        Mail::fake();
    }

    /**
     * Test sending magic link to existing user.
     */
    public function test_send_magic_link_to_existing_user(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act
        $result = $this->service->sendMagicLink('test@example.com', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(
            'If an account exists with this email, a magic link has been sent.',
            $result['message']
        );

        Mail::assertQueued(MagicLoginMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Verify token was stored
        $user->refresh();
        $this->assertNotNull($user->magic_token_hash);
        $this->assertNotNull($user->magic_token_expires_at);
    }

    /**
     * Test sending magic link to non-existent user.
     */
    public function test_send_magic_link_to_nonexistent_user(): void
    {
        // Act
        $result = $this->service->sendMagicLink('nonexistent@example.com', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(
            'If an account exists with this email, a magic link has been sent.',
            $result['message']
        );

        // No email should be sent
        Mail::assertNothingQueued();
    }

    /**
     * Test rate limiting for magic link requests.
     */
    public function test_rate_limiting_for_magic_link(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act - Send 3 requests (max allowed)
        $this->service->sendMagicLink('test@example.com', '127.0.0.1');
        $this->service->sendMagicLink('test@example.com', '127.0.0.1');
        $this->service->sendMagicLink('test@example.com', '127.0.0.1');

        // 4th request should be rate limited
        $result = $this->service->sendMagicLink('test@example.com', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        // Should still return same message (enumeration protection)
        Mail::assertQueuedCount(3); // Only first 3 sent
    }

    /**
     * Test verifying valid magic token.
     */
    public function test_verify_valid_magic_token(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-token-12345';
        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->addMinutes(15),
            'magic_token_used_at' => null,
        ]);

        // Act
        $result = $this->service->verifyMagicToken('test@example.com', $token);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals($user->id, $result['user']->id);

        // Token should be marked as used
        $user->refresh();
        $this->assertNotNull($user->magic_token_used_at);
    }

    /**
     * Test verifying invalid magic token.
     */
    public function test_verify_invalid_magic_token(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $user->update([
            'magic_token_hash' => Hash::make('correct-token'),
            'magic_token_expires_at' => now()->addMinutes(15),
        ]);

        // Act
        $result = $this->service->verifyMagicToken('test@example.com', 'wrong-token');

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertNull($result['user']);
    }

    /**
     * Test verifying expired magic token.
     */
    public function test_verify_expired_magic_token(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-token';
        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->subMinutes(1), // Expired
        ]);

        // Act
        $result = $this->service->verifyMagicToken('test@example.com', $token);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Token has expired', $result['message']);
    }

    /**
     * Test verifying already used magic token.
     */
    public function test_verify_already_used_magic_token(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-token';
        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->addMinutes(15),
            'magic_token_used_at' => now()->subMinutes(5), // Already used
        ]);

        // Act
        $result = $this->service->verifyMagicToken('test@example.com', $token);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Token has already been used', $result['message']);
    }

    /**
     * Test token is invalidated after verification.
     */
    public function test_token_invalidated_after_verification(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-token';
        $user->update([
            'magic_token_hash' => Hash::make($token),
            'magic_token_expires_at' => now()->addMinutes(15),
        ]);

        // Act - First verification
        $result1 = $this->service->verifyMagicToken('test@example.com', $token);

        // Second verification (should fail)
        $result2 = $this->service->verifyMagicToken('test@example.com', $token);

        // Assert
        $this->assertTrue($result1['valid']);
        $this->assertFalse($result2['valid']);
        $this->assertEquals('Token has already been used', $result2['message']);
    }

    /**
     * Test different IPs have separate rate limits.
     */
    public function test_different_ips_have_separate_rate_limits(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act - Exhaust limit for first IP
        $this->service->sendMagicLink('test@example.com', '127.0.0.1');
        $this->service->sendMagicLink('test@example.com', '127.0.0.1');
        $this->service->sendMagicLink('test@example.com', '127.0.0.1');

        // Different IP should still work
        $result = $this->service->sendMagicLink('test@example.com', '192.168.1.1');

        // Assert
        $this->assertTrue($result['success']);
        Mail::assertQueuedCount(4); // All 4 sent (3 from first IP, 1 from second)
    }
}
