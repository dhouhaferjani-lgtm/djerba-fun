<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional Authentication Middleware
 *
 * Attempts to authenticate the user if a Bearer token is present,
 * but does NOT block the request if no token is provided.
 * This allows routes to work for both authenticated users and guests.
 *
 * Use case: Guest checkout where authenticated users should have their
 * user_id linked to bookings, but guests can still complete checkout.
 */
class OptionalAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to authenticate if Bearer token is present
        $token = $request->bearerToken();

        if ($token) {
            try {
                $accessToken = PersonalAccessToken::findToken($token);

                if ($accessToken && ! $this->isTokenExpired($accessToken)) {
                    // Get the user (handles lazy loading)
                    $user = $accessToken->tokenable;

                    // Only set if user exists (could be deleted)
                    if ($user) {
                        // CRITICAL: Set user on the auth guard FIRST
                        // This makes auth()->user() and $request->user() work
                        Auth::guard('sanctum')->setUser($user);

                        // Also set the user resolver on the request for compatibility
                        $request->setUserResolver(fn () => $user);

                        Log::debug('OptionalAuth: User authenticated', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                        ]);

                        // Update last_used_at SEPARATELY (non-critical operation)
                        // If this fails, user is still authenticated
                        try {
                            $accessToken->forceFill(['last_used_at' => now()])->save();
                        } catch (\Throwable $e) {
                            Log::warning('OptionalAuth: Failed to update token last_used_at', [
                                'token_id' => $accessToken->id,
                                'error' => $e->getMessage(),
                            ]);
                            // Don't fail - user is still authenticated
                        }
                    }
                }
            } catch (\Throwable $e) {
                // If token lookup fails (DB error, etc.), continue as guest
                // This ensures checkout never breaks due to auth issues
                Log::error('OptionalAuth: Token validation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                report($e);
            }
        }

        return $next($request);
    }

    /**
     * Check if the token is expired.
     */
    private function isTokenExpired(PersonalAccessToken $token): bool
    {
        $expiresAt = $token->expires_at;

        if (! $expiresAt) {
            // No expiration set, check last_used_at with Sanctum's default expiration
            $expiration = config('sanctum.expiration');

            if (! $expiration) {
                return false; // No expiration configured
            }

            return $token->created_at->addMinutes($expiration)->isPast();
        }

        return $expiresAt->isPast();
    }
}
