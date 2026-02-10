<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPasswordlessRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendMagicLinkRequest;
use App\Http\Requests\VerifyMagicLinkRequest;
use App\Http\Resources\UserResource;
use App\Models\TravelerProfile;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\EmailVerificationService;
use App\Services\MagicAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly MagicAuthService $magicAuthService,
        private readonly EmailVerificationService $verificationService
    ) {}

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create user with pending verification status
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => UserStatus::PENDING_VERIFICATION,
                'display_name' => $request->display_name,
            ]);

            // Create role-specific profile
            if ($user->role === UserRole::TRAVELER) {
                TravelerProfile::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'preferred_locale' => $request->preferred_locale ?? 'en',
                ]);
            } elseif ($user->role === UserRole::VENDOR) {
                VendorProfile::create([
                    'user_id' => $user->id,
                    'company_name' => $request->company_name,
                    'company_type' => $request->company_type,
                    'tax_id' => $request->tax_id,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'message' => 'Registration failed. Please try again.',
                ],
            ], 500);
        }

        // Send verification email (after commit so user exists)
        // Separate try-catch: if email fails, user still created successfully
        try {
            $this->verificationService->sendVerificationEmail($user);
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'message' => 'verification_email_sent',
            'email' => $user->email,
        ], 201);
    }

    /**
     * Login user and create token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // OAuth-only accounts have no password — direct them to social login
        if ($user->password === null) {
            $provider = $user->oauth_provider ?? 'social login';

            return response()->json([
                'error' => [
                    'code' => 'OAUTH_ACCOUNT',
                    'message' => "This account uses {$provider}. Please log in with {$provider} or use a magic link.",
                ],
            ], 400);
        }

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Block unverified users and auto-resend verification email
        if ($user->status === UserStatus::PENDING_VERIFICATION) {
            $this->verificationService->sendVerificationEmail($user);

            return response()->json([
                'error' => [
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'message' => 'Please verify your email. A new verification link has been sent.',
                    'email' => $user->email,
                ],
            ], 403);
        }

        if (! $user->canAccess()) {
            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_SUSPENDED',
                    'message' => 'Your account is not active. Please contact support.',
                ],
            ], 403);
        }

        // Revoke existing tokens (optional - single device login)
        // $user->tokens()->delete();

        // Create new token
        $token = $user->createToken(
            $request->device_name ?? 'api-token',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load(['travelerProfile', 'vendorProfile'])),
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load(['travelerProfile', 'vendorProfile'])
        );
    }

    /**
     * Send a magic link to the user's email.
     * Implements email enumeration protection.
     */
    public function sendMagicLink(SendMagicLinkRequest $request): JsonResponse
    {
        $result = $this->magicAuthService->sendMagicLink(
            email: $request->email,
            ip: $request->ip()
        );

        return response()->json([
            'message' => $result['message'],
        ]);
    }

    /**
     * Verify a magic link token and return authentication token.
     */
    public function verifyMagicLink(VerifyMagicLinkRequest $request): JsonResponse
    {
        $user = $this->magicAuthService->validateToken($request->token);

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Invalid or expired magic link. Please request a new one.',
                ],
            ], 401);
        }

        // If user proved email ownership via magic link, auto-verify them
        if ($user->status === UserStatus::PENDING_VERIFICATION) {
            $user->update([
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'verification_token_hash' => null,
                'verification_token_expires_at' => null,
            ]);
        }

        if (! $user->canAccess()) {
            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_SUSPENDED',
                    'message' => 'Your account is not active. Please contact support.',
                ],
            ], 403);
        }

        // Create API token
        $token = $user->createToken(
            $request->device_name ?? 'magic-link-login',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load(['travelerProfile', 'vendorProfile'])),
            'token' => $token,
        ]);
    }

    /**
     * Verify email address using token from verification email.
     * Auto-logs in the user and returns API token.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = $this->verificationService->verifyToken($request->token);

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_VERIFICATION_TOKEN',
                    'message' => 'Invalid or expired verification link. Please request a new one.',
                ],
            ], 401);
        }

        // Create API token (auto-login after verification)
        $token = $user->createToken(
            'email-verification',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load(['travelerProfile', 'vendorProfile'])),
            'token' => $token,
        ]);
    }

    /**
     * Resend verification email.
     * Email enumeration protection: always returns success message.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Email enumeration protection — always return same message
        if ($user && $user->status === UserStatus::PENDING_VERIFICATION) {
            try {
                $this->verificationService->resendVerification($user);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'If this email exists and is not yet verified, a verification link has been sent.',
        ]);
    }

    /**
     * Register a new passwordless user account.
     * User will receive verification email.
     */
    public function registerPasswordless(RegisterPasswordlessRequest $request): JsonResponse
    {
        try {
            $user = $this->magicAuthService->createPasswordlessUser(
                email: $request->email,
                firstName: $request->first_name,
                lastName: $request->last_name,
                phone: $request->phone,
                locale: $request->preferred_locale ?? 'en'
            );

            // Set user as traveler and active by default
            $user->update([
                'role' => UserRole::TRAVELER,
                'status' => UserStatus::ACTIVE,
                'display_name' => trim("{$request->first_name} {$request->last_name}"),
            ]);

            // Send magic link for verification/first login
            $this->magicAuthService->sendMagicLink(
                email: $user->email,
                ip: $request->ip()
            );

            return response()->json([
                'message' => 'Account created successfully! Check your email for a magic link to log in.',
                'user' => [
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'message' => 'Registration failed. Please try again.',
                ],
            ], 500);
        }
    }
}
