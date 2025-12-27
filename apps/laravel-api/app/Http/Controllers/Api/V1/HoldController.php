<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHoldRequest;
use App\Http\Resources\BookingHoldResource;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Services\GeoPricingService;
use App\Services\PriceCalculationService;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
    public function __construct(
        private readonly GeoPricingService $geoPricingService,
        private readonly PriceCalculationService $priceCalculationService
    ) {}
    /**
     * Create a new booking hold.
     * Supports both authenticated users and guest checkout via session_id.
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

        // Get total quantity from either quantity field or person_types breakdown
        $quantity = $request->getTotalQuantity();
        $personTypeBreakdown = $request->getPersonTypeBreakdown();

        // Check if quantity is available (uses computed accessor)
        if ($slot->remainingCapacity < $quantity) {
            return response()->json([
                'message' => 'Insufficient capacity',
                'requested' => $quantity,
                'available' => $slot->remainingCapacity,
            ], 422);
        }

        // Get user (if authenticated) and session_id (for guest checkout)
        $user = $request->user();
        $sessionId = $validated['session_id'] ?? null;

        // Detect currency based on user context (IP geolocation or user billing country)
        $pricingContext = $this->geoPricingService->determinePricingCountry(
            billingAddress: null,
            userSelectedCountry: null,
            ipAddress: $request->ip()
        );

        $currency = $this->geoPricingService->getCurrencyForCountry($pricingContext['country_code']);

        // Calculate price snapshot for the hold
        $priceSnapshot = null;
        if (! empty($personTypeBreakdown)) {
            $calculation = $this->priceCalculationService->calculateTotal(
                $listing,
                $personTypeBreakdown,
                $currency
            );
            $priceSnapshot = $calculation['total'];
        } else {
            $calculation = $this->priceCalculationService->calculateSimpleTotal(
                $listing,
                $quantity,
                $currency
            );
            $priceSnapshot = $calculation['total'];
        }

        // Create the hold with pricing context
        $hold = BookingHold::createForSlot(
            slot: $slot,
            user: $user,
            quantity: $quantity,
            sessionId: $sessionId,
            personTypeBreakdown: $personTypeBreakdown,
            currency: $currency,
            priceSnapshot: $priceSnapshot,
            pricingCountryCode: $pricingContext['country_code'],
            pricingSource: $pricingContext['source']
        );

        if (! $hold) {
            return response()->json([
                'message' => 'Failed to create hold. Please try again.',
            ], 500);
        }

        return new BookingHoldResource($hold->load('slot'));
    }

    /**
     * Get a specific hold.
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
     * Get a hold by ID directly (for checkout page persistence).
     * Includes full listing and slot data.
     */
    public function showById(BookingHold $hold): BookingHoldResource|JsonResponse
    {
        // Load related data for checkout page
        $hold->load(['slot', 'listing']);

        // Check if hold is expired
        if ($hold->isExpired()) {
            $hold->expire();

            return response()->json([
                'message' => 'This hold has expired',
                'hold' => new BookingHoldResource($hold),
                'listingSlug' => $hold->listing?->slug,
            ], 410);
        }

        return new BookingHoldResource($hold);
    }

    /**
     * Cancel a hold.
     * Supports both authenticated users and guest checkout via session_id.
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
