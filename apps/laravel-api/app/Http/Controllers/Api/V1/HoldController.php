<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHoldRequest;
use App\Http\Resources\BookingHoldResource;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
    /**
     * Create a new booking hold.
     * Supports both authenticated users and guest checkout via session_id.
     *
     * @param  CreateHoldRequest  $request
     * @param  Listing  $listing
     * @return BookingHoldResource|JsonResponse
     */
    public function store(CreateHoldRequest $request, Listing $listing): BookingHoldResource|JsonResponse
    {
        $validated = $request->validated();

        // Find the slot
        $slot = AvailabilitySlot::findOrFail($validated['slot_id']);

        // Check if slot is bookable
        if (! $slot->isBookable()) {
            return response()->json([
                'message' => 'This time slot is no longer available',
                'slot_status' => $slot->status->value,
            ], 422);
        }

        // Check if quantity is available
        if ($slot->remaining_capacity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient capacity',
                'requested' => $validated['quantity'],
                'available' => $slot->remaining_capacity,
            ], 422);
        }

        // Get user (if authenticated) and session_id (for guest checkout)
        $user = $request->user();
        $sessionId = $validated['session_id'] ?? null;

        // Create the hold
        $hold = BookingHold::createForSlot($slot, $user, $validated['quantity'], $sessionId);

        if (! $hold) {
            return response()->json([
                'message' => 'Failed to create hold. Please try again.',
            ], 500);
        }

        return new BookingHoldResource($hold->load('slot'));
    }

    /**
     * Get a specific hold.
     *
     * @param  Listing  $listing
     * @param  BookingHold  $hold
     * @return BookingHoldResource|JsonResponse
     */
    public function show(Listing $listing, BookingHold $hold): BookingHoldResource|JsonResponse
    {
        // Ensure hold belongs to the listing
        if ($hold->listing_id !== $listing->id) {
            return response()->json([
                'message' => 'Hold not found for this listing',
            ], 404);
        }

        // Check if hold is expired
        if ($hold->isExpired()) {
            $hold->expire();

            return response()->json([
                'message' => 'This hold has expired',
                'hold' => new BookingHoldResource($hold),
            ], 410);
        }

        return new BookingHoldResource($hold->load('slot'));
    }

    /**
     * Cancel a hold.
     * Supports both authenticated users and guest checkout via session_id.
     *
     * @param  Listing  $listing
     * @param  BookingHold  $hold
     * @return JsonResponse
     */
    public function destroy(Listing $listing, BookingHold $hold): JsonResponse
    {
        // Ensure hold belongs to the listing
        if ($hold->listing_id !== $listing->id) {
            return response()->json([
                'message' => 'Hold not found for this listing',
            ], 404);
        }

        // Check authorization: either authenticated user owns the hold, or guest has matching session_id
        $userId = auth()->id();
        $sessionId = request()->query('session_id') ?? request()->input('session_id');

        $isOwner = ($userId && $hold->user_id === $userId) ||
                   ($sessionId && $hold->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Expire the hold to release capacity
        $hold->expire();

        return response()->json([
            'message' => 'Hold cancelled successfully',
        ]);
    }
}
