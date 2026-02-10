<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Mail\AccountVerificationMail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    /**
     * Verification token expiration time in hours.
     */
    private const TOKEN_EXPIRATION_HOURS = 24;

    /**
     * Rate limit: maximum verification emails per hour per email.
     */
    private const MAX_RESENDS_PER_HOUR = 3;

    /**
     * Send a verification email to the user.
     */
    public function sendVerificationEmail(User $user): void
    {
        // Generate token
        $plainToken = Str::random(64);
        $hashedToken = Hash::make($plainToken);

        // Store in dedicated verification columns (separate from magic_token_*)
        $user->update([
            'verification_token_hash' => $hashedToken,
            'verification_token_expires_at' => now()->addHours(self::TOKEN_EXPIRATION_HOURS),
        ]);

        // Count claimable bookings for this email (shown in email template)
        $claimableCount = Booking::where('guest_email', $user->email)
            ->whereNull('user_id')
            ->count();

        // Send verification email (queued)
        Mail::to($user->email)->queue(
            new AccountVerificationMail($user, $plainToken, $claimableCount)
        );
    }

    /**
     * Verify a token and activate the user.
     *
     * @return User|null The verified user, or null if token is invalid/expired
     */
    public function verifyToken(string $plainToken): ?User
    {
        // Find users with a non-expired verification token
        $users = User::whereNotNull('verification_token_hash')
            ->where('verification_token_expires_at', '>', now())
            ->get();

        foreach ($users as $user) {
            if (Hash::check($plainToken, $user->verification_token_hash)) {
                // Token is valid — activate the user
                $user->update([
                    'email_verified_at' => now(),
                    'status' => UserStatus::ACTIVE,
                    'verification_token_hash' => null,
                    'verification_token_expires_at' => null,
                ]);

                return $user;
            }
        }

        return null;
    }

    /**
     * Resend verification email with rate limiting.
     *
     * @return bool True if email was sent, false if rate limited
     */
    public function resendVerification(User $user): bool
    {
        // Rate limiting
        $rateLimitKey = "verification_resend:{$user->email}";
        $attempts = (int) Cache::get($rateLimitKey, 0);

        if ($attempts >= self::MAX_RESENDS_PER_HOUR) {
            return false;
        }

        Cache::put($rateLimitKey, $attempts + 1, now()->addHour());

        $this->sendVerificationEmail($user);

        return true;
    }
}
