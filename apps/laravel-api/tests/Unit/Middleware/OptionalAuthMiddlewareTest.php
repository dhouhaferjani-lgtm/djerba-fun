<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\OptionalAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class OptionalAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private OptionalAuth $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new OptionalAuth;
    }

    /**
     * Test middleware authenticates user with valid token.
     */
    public function test_authenticates_user_with_valid_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $plainToken = $token->plainTextToken;

        $request = Request::create('/api/v1/bookings', 'POST');
        $request->headers->set('Authorization', "Bearer {$plainToken}");

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['user_id' => $req->user()?->id]);
        });

        // Assert
        $content = json_decode($response->getContent(), true);
        $this->assertEquals($user->id, $content['user_id']);
    }

    /**
     * Test middleware allows request without token (guest).
     */
    public function test_allows_request_without_token(): void
    {
        // Arrange
        $request = Request::create('/api/v1/bookings', 'POST');

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['user_id' => $req->user()?->id]);
        });

        // Assert
        $content = json_decode($response->getContent(), true);
        $this->assertNull($content['user_id']);
    }

    /**
     * Test middleware allows request with invalid token (continues as guest).
     */
    public function test_allows_request_with_invalid_token(): void
    {
        // Arrange
        $request = Request::create('/api/v1/bookings', 'POST');
        $request->headers->set('Authorization', 'Bearer invalid-token-123');

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['user_id' => $req->user()?->id]);
        });

        // Assert - Should continue as guest, not throw error
        $content = json_decode($response->getContent(), true);
        $this->assertNull($content['user_id']);
    }

    /**
     * Test middleware allows request with expired token (continues as guest).
     */
    public function test_allows_request_with_expired_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        // Manually expire the token
        PersonalAccessToken::where('tokenable_id', $user->id)
            ->update(['expires_at' => now()->subDay()]);

        $request = Request::create('/api/v1/bookings', 'POST');
        $request->headers->set('Authorization', "Bearer {$token->plainTextToken}");

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['user_id' => $req->user()?->id]);
        });

        // Assert - Should continue as guest due to expired token
        $content = json_decode($response->getContent(), true);
        $this->assertNull($content['user_id']);
    }

    /**
     * Test middleware sets both auth guard and request user resolver.
     */
    public function test_sets_both_auth_guard_and_request_user_resolver(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $request = Request::create('/api/v1/bookings', 'POST');
        $request->headers->set('Authorization', "Bearer {$token->plainTextToken}");

        // Act
        $this->middleware->handle($request, function ($req) use ($user) {
            // Assert inside the closure (after middleware has run)
            // Check request->user() works
            $this->assertEquals($user->id, $req->user()?->id);

            // Check Auth::guard('sanctum')->user() also works
            $this->assertEquals($user->id, Auth::guard('sanctum')->user()?->id);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Test middleware updates token last_used_at.
     */
    public function test_updates_token_last_used_at(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $request = Request::create('/api/v1/bookings', 'POST');
        $request->headers->set('Authorization', "Bearer {$token->plainTextToken}");

        // Record initial state
        $initialLastUsed = PersonalAccessToken::where('tokenable_id', $user->id)->first()->last_used_at;

        // Act
        $this->middleware->handle($request, function () {
            return response()->json(['success' => true]);
        });

        // Assert
        $updatedToken = PersonalAccessToken::where('tokenable_id', $user->id)->first();
        $this->assertNotNull($updatedToken->last_used_at);

        if ($initialLastUsed) {
            $this->assertTrue($updatedToken->last_used_at->gte($initialLastUsed));
        }
    }
}
