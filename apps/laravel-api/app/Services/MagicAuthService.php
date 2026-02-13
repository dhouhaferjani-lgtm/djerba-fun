<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\MagicLoginMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MagicAuthService
{
    /**
     * Magic token expiration time in minutes.
     */
    private const TOKEN_EXPIRATION_MINUTES = 15;

    /**
     * Rate limit: maximum attempts per hour per email+IP combination.
     */
    private const MAX_ATTEMPTS_PER_HOUR = 3;

    /**
     * Send a magic link to the user's email.
     * Implements rate limiting and email enumeration protection.
     *
     * @param  string  $email  The user's email address
     * @param  string  $ip  The requester's IP address (for rate limiting)
     * @return array{success: bool, message: string, user?: User}
     *
     * @throws \Exception
     */
    public function sendMagicLink(string $email, string $ip): array
    {
        // Rate limiting: check if user has exceeded attempts
        $rateLimitKey = "magic_link_attempts:{$email}:{$ip}";
        $attempts = (int) Cache::get($rateLimitKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS_PER_HOUR) {
            // Email enumeration protection: don't reveal if user exists
            return [
                'success' => true,
                'message' => 'If an account exists with this email, a magic link has been sent.',
            ];
        }

        // Increment rate limit counter (1 hour TTL)
        Cache::put($rateLimitKey, $attempts + 1, now()->addHour());

        // Find user by email
        $user = User::where('email', $email)->first();

        // Email enumeration protection: always return same message
        if (! $user) {
            return [
                'success' => true,
                'message' => 'If an account exists with this email, a magic link has been sent.',
            ];
        }

        // Generate magic token
        $plainToken = Str::random(64);
        $hashedToken = Hash::make($plainToken);

        // Store hashed token in database
        $user->update([
            'magic_token_hash' => $hashedToken,
            'magic_token_expires_at' => now()->addMinutes(self::TOKEN_EXPIRATION_MINUTES),
            'magic_token_used_at' => null, // Reset used flag
        ]);

        // Send email with magic link
        Mail::to($user->email)->queue(new MagicLoginMail($user, $plainToken));

        return [
            'success' => true,
            'message' => 'If an account exists with this email, a magic link has been sent.',
            'user' => $user, // For internal use (not exposed to API)
        ];
    }

    /**
     * Validate a magic token and return the authenticated user.
     * Enforces single-use and expiration.
     *
     * @param  string  $plainToken  The plain-text token from the magic link
     * @return User|null The authenticated user, or null if token is invalid
     */
    public function validateToken(string $plainToken): ?User
    {
        // Find users with non-null magic token that hasn't expired
        $users = User::whereNotNull('magic_token_hash')
            ->where('magic_token_expires_at', '>', now())
            ->whereNull('magic_token_used_at') // Not yet used
            ->get();

        // Check each user's hashed token (can't use WHERE with hashed values)
        foreach ($users as $user) {
            if (Hash::check($plainToken, $user->magic_token_hash)) {
                // Token is valid! Mark as used
                $user->update([
                    'magic_token_used_at' => now(),
                    'last_magic_login_at' => now(),
                ]);

                return $user;
            }
        }

        return null;
    }

    /**
     * Create a passwordless user account.
     * User will verify email via magic link.
     */
    public function createPasswordlessUser(
        string $email,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $locale = null
    ): User {
        return User::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'preferred_locale' => $locale ?? app()->getLocale(),
            'password' => null, // Passwordless account
            'prefers_passwordless' => true,
        ]);
    }

    /**
     * Check if a user prefers passwordless authentication.
     */
    public function userPrefersPasswordless(string $email): bool
    {
        $user = User::where('email', $email)->first();

        return $user?->prefers_passwordless ?? false;
    }

    /**
     * Clear expired magic tokens (cleanup job).
     * Should be called periodically via scheduled command.
     *
     * @return int Number of tokens cleared
     */
    public function clearExpiredTokens(): int
    {
        return User::whereNotNull('magic_token_hash')
            ->where('magic_token_expires_at', '<', now())
            ->update([
                'magic_token_hash' => null,
                'magic_token_expires_at' => null,
            ]);
    }

    /**
     * Invalidate all magic tokens for a user (e.g., after password change).
     */
    public function invalidateUserTokens(User $user): void
    {
        $user->update([
            'magic_token_hash' => null,
            'magic_token_expires_at' => null,
            'magic_token_used_at' => null,
        ]);
    }

    /**
     * Get rate limit status for an email+IP combination.
     *
     * @return array{attempts: int, remaining: int, reset_at: \Carbon\Carbon|null}
     */
    public function getRateLimitStatus(string $email, string $ip): array
    {
        $rateLimitKey = "magic_link_attempts:{$email}:{$ip}";
        $attempts = (int) Cache::get($rateLimitKey, 0);
        $remaining = max(0, self::MAX_ATTEMPTS_PER_HOUR - $attempts);

        // Calculate when the rate limit resets
        $resetAt = $attempts > 0 ? now()->addHour() : null;

        return [
            'attempts' => $attempts,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
        ];
    }
}
