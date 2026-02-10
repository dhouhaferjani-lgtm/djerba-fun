<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\TravelerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Supported OAuth providers.
     */
    private const SUPPORTED_PROVIDERS = ['google', 'facebook'];

    /**
     * Redirect to OAuth provider's authorization page.
     * Returns the redirect URL for the frontend to open in a popup.
     */
    public function redirect(string $provider): JsonResponse
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS)) {
            return response()->json([
                'error' => [
                    'code' => 'UNSUPPORTED_PROVIDER',
                    'message' => "OAuth provider '{$provider}' is not supported.",
                ],
            ], 400);
        }

        $url = Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * Handle OAuth provider callback.
     * Creates or links user, then redirects to frontend with token.
     */
    public function callback(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS)) {
            return redirect($this->getFrontendUrl('/auth/login?error=unsupported_provider'));
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return redirect($this->getFrontendUrl('/auth/login?error=oauth_failed'));
        }

        if (! $socialUser->getEmail()) {
            return redirect($this->getFrontendUrl('/auth/login?error=no_email'));
        }

        // 1. Find existing OAuth user
        $user = User::where('oauth_provider', $provider)
            ->where('oauth_provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            // 2. Find by email — link OAuth to existing account
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $socialUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'status' => UserStatus::ACTIVE,
                ]);
            } else {
                // 3. Create new user (auto-verified via OAuth)
                $nameParts = $this->splitName($socialUser->getName() ?? '');

                $user = User::create([
                    'email' => $socialUser->getEmail(),
                    'password' => null,
                    'role' => UserRole::TRAVELER,
                    'status' => UserStatus::ACTIVE,
                    'display_name' => $socialUser->getName() ?? $socialUser->getEmail(),
                    'first_name' => $nameParts['first'],
                    'last_name' => $nameParts['last'],
                    'email_verified_at' => now(),
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $socialUser->getId(),
                    'avatar_url' => $socialUser->getAvatar(),
                ]);

                // Create traveler profile
                TravelerProfile::create([
                    'user_id' => $user->id,
                    'first_name' => $nameParts['first'],
                    'last_name' => $nameParts['last'],
                    'preferred_locale' => 'en',
                ]);
            }
        }

        // Create API token
        $token = $user->createToken(
            "oauth-{$provider}",
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        // Redirect to frontend callback page with token
        $callbackUrl = $this->getFrontendUrl("/auth/oauth/callback?token={$token}");

        return redirect($callbackUrl);
    }

    /**
     * Get the frontend URL with path.
     */
    private function getFrontendUrl(string $path): string
    {
        $baseUrl = config('app.frontend_url', 'https://dev.go-adventure.net');

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Split a full name into first and last name.
     */
    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'first' => $parts[0] ?? '',
            'last' => $parts[1] ?? '',
        ];
    }
}
