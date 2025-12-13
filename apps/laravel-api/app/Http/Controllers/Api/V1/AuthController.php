<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\TravelerProfile;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => UserStatus::ACTIVE,
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

            // Create API token
            $token = $user->createToken(
                $request->device_name ?? 'api-token',
                ['*'],
                now()->addDays(30)
            )->plainTextToken;

            DB::commit();

            return response()->json([
                'user' => new UserResource($user->load(['travelerProfile', 'vendorProfile'])),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'message' => 'Registration failed. Please try again.',
                ],
            ], 500);
        }
    }

    /**
     * Login user and create token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->canAccess()) {
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
}
