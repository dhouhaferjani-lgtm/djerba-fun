<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
                    // Set the authenticated user on the request
                    $request->setUserResolver(fn () => $accessToken->tokenable);

                    // Also update the auth guard
                    auth()->setUser($accessToken->tokenable);
                }
            } catch (\Throwable $e) {
                // If token lookup fails (DB error, etc.), continue as guest
                // This ensures checkout never breaks due to auth issues
                report($e); // Log the error for debugging
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
