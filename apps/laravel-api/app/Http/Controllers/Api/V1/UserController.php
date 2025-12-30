<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get current user profile
     */
    public function show(Request $request): UserResource
    {
        $user = $request->user();
        $user->load(['travelerProfile', 'vendorProfile']);

        return new UserResource($user);
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();

        // Update user fields
        if (isset($data['first_name'])) {
            $user->first_name = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $user->last_name = $data['last_name'];
        }
        if (isset($data['display_name'])) {
            $user->display_name = $data['display_name'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }
        if (isset($data['preferred_locale'])) {
            $user->preferred_locale = $data['preferred_locale'];
        }

        $user->save();

        // Update or create traveler profile if user is a traveler
        if ($user->isTraveler()) {
            $profileData = [];

            if (isset($data['first_name'])) {
                $profileData['first_name'] = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $profileData['last_name'] = $data['last_name'];
            }
            if (isset($data['phone'])) {
                $profileData['phone'] = $data['phone'];
            }
            if (isset($data['preferred_locale'])) {
                $profileData['preferred_locale'] = $data['preferred_locale'];
            }

            if (!empty($profileData)) {
                $user->travelerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
                );
            }
        }

        $user->load(['travelerProfile', 'vendorProfile']);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['The current password is incorrect'],
                ],
            ], 422);
        }

        // Update password
        $user->password = Hash::make($data['new_password']);
        $user->save();

        // Revoke all other tokens except current
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'message' => 'Password updated successfully. All other sessions have been logged out.',
        ]);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar_url) {
            $oldPath = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_url = Storage::disk('public')->url($path);
        $user->save();

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => $user->avatar_url,
        ]);
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_url) {
            $oldPath = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $user->avatar_url = null;
        $user->save();

        return response()->json([
            'message' => 'Avatar deleted successfully',
        ]);
    }

    /**
     * Get user preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $preferences = [
            'email_notifications' => true,
            'marketing_emails' => false,
            'booking_reminders' => true,
            'review_reminders' => true,
        ];

        // Get from traveler profile if exists
        if ($user->travelerProfile && $user->travelerProfile->preferences) {
            $preferences = array_merge($preferences, $user->travelerProfile->preferences);
        }

        return response()->json([
            'data' => [
                'locale' => $user->preferred_locale ?? 'en',
                'currency' => $user->travelerProfile?->default_currency ?? 'TND',
                'notifications' => $preferences,
            ],
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Update locale if provided
        if (isset($data['locale'])) {
            $user->preferred_locale = $data['locale'];
            $user->save();
        }

        // Update currency and notifications in traveler profile
        if ($user->isTraveler()) {
            $profileData = [];

            if (isset($data['currency'])) {
                $profileData['default_currency'] = $data['currency'];
            }

            if (isset($data['notifications'])) {
                $profileData['preferences'] = array_merge(
                    $user->travelerProfile?->preferences ?? [],
                    $data['notifications']
                );
            }

            if (!empty($profileData)) {
                $user->travelerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
                );
            }
        }

        return response()->json([
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Delete user account (GDPR compliant)
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // For GDPR compliance, we should:
        // 1. Export user data (optional - could be done before this call)
        // 2. Anonymize bookings (keep booking data but remove personal info)
        // 3. Delete or anonymize reviews
        // 4. Soft delete the user

        // Anonymize bookings - keep booking records but remove personal data
        $user->bookings()->update([
            'customer_email' => 'deleted-user-' . $user->id . '@deleted.local',
            'customer_first_name' => 'Deleted',
            'customer_last_name' => 'User',
            'customer_phone' => null,
        ]);

        // Anonymize reviews - keep review content but mark as anonymous
        $user->reviews()->update([
            'user_id' => null,
        ]);

        // Delete tokens
        $user->tokens()->delete();

        // Delete avatar
        if ($user->avatar_url) {
            $oldPath = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Delete profiles
        $user->travelerProfile()->delete();
        $user->vendorProfile()->delete();

        // Soft delete user (if using SoftDeletes) or hard delete
        // For now we'll anonymize the user data instead of deleting
        $user->email = 'deleted-user-' . $user->id . '@deleted.local';
        $user->first_name = 'Deleted';
        $user->last_name = 'User';
        $user->display_name = 'Deleted User';
        $user->phone = null;
        $user->avatar_url = null;
        $user->status = \App\Enums\UserStatus::INACTIVE;
        $user->save();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Export user data (GDPR compliant data export)
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['travelerProfile', 'vendorProfile', 'bookings', 'reviews']);

        $data = [
            'user' => [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'traveler_profile' => $user->travelerProfile ? [
                'first_name' => $user->travelerProfile->first_name,
                'last_name' => $user->travelerProfile->last_name,
                'phone' => $user->travelerProfile->phone,
                'default_currency' => $user->travelerProfile->default_currency,
                'preferred_locale' => $user->travelerProfile->preferred_locale,
                'preferences' => $user->travelerProfile->preferences,
            ] : null,
            'bookings' => $user->bookings->map(function ($booking) {
                return [
                    'code' => $booking->code,
                    'status' => $booking->status->value,
                    'total_price' => $booking->total_price,
                    'currency' => $booking->currency,
                    'created_at' => $booking->created_at->toIso8601String(),
                ];
            }),
            'reviews' => $user->reviews->map(function ($review) {
                return [
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'content' => $review->content,
                    'created_at' => $review->created_at->toIso8601String(),
                ];
            }),
        ];

        return response()->json([
            'data' => $data,
        ]);
    }
}
