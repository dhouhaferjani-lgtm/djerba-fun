<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHoldRequest;
use App\Http\Resources\BookingHoldResource;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use App\Services\AccommodationBookingService;
use App\Services\GeoPricingService;
use App\Services\PriceCalculationService;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
    public function __construct(
        private readonly GeoPricingService $geoPricingService,
        private readonly PriceCalculationService $priceCalculationService,
        private readonly AccommodationBookingService $accommodationBookingService
    ) {}
    /**
     * Create a new booking hold.
     * Supports both authenticated users and guest checkout via session_id.
     * Handles both regular (per-person) and accommodation (per-night) bookings.
     */
    public function store(CreateHoldRequest $request, Listing $listing): BookingHoldResource|JsonResponse
    {
        $validated = $request->validated();

        // Detect currency based on user context (IP geolocation or user billing country)
        $pricingContext = $this->geoPricingService->determinePricingCountry(
            billingAddress: null,
            userSelectedCountry: null,
            ipAddress: $this->geoPricingService->getRealClientIP($request)
        );
        $currency = $this->geoPricingService->getCurrencyForCountry($pricingContext['country_code']);

        // Get user (if authenticated) and session_id (for guest checkout)
        $user = $request->user();
        $sessionId = $validated['session_id'] ?? null;
        $extras = $validated['extras'] ?? null;

        // Check if this is an accommodation (date range) booking
        if ($request->isAccommodationBooking()) {
            return $this->createAccommodationHold(
                $request,
                $listing,
                $currency,
                $user,
                $sessionId,
                $pricingContext,
                $extras
            );
        }

        // Standard (per-person/per-slot) booking flow
        return $this->createStandardHold(
            $request,
            $listing,
            $validated,
            $currency,
            $user,
            $sessionId,
            $pricingContext,
            $extras
        );
    }

    /**
     * Create a standard (non-accommodation) booking hold.
     */
    private function createStandardHold(
        CreateHoldRequest $request,
        Listing $listing,
        array $validated,
        string $currency,
        $user,
        ?string $sessionId,
        array $pricingContext,
        ?array $extras
    ): BookingHoldResource|JsonResponse {
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
            pricingSource: $pricingContext['source'],
            extras: $extras
        );

        if (! $hold) {
            return response()->json([
                'message' => 'Failed to create hold. Please try again.',
            ], 500);
        }

        return new BookingHoldResource($hold->load('slot'));
    }

    /**
     * Create an accommodation (date range) booking hold.
     */
    private function createAccommodationHold(
        CreateHoldRequest $request,
        Listing $listing,
        string $currency,
        $user,
        ?string $sessionId,
        array $pricingContext,
        ?array $extras
    ): BookingHoldResource|JsonResponse {
        $checkIn = $request->getCheckInDate();
        $checkOut = $request->getCheckOutDate();
        $guests = $request->getGuestCount();

        // Validate the date range
        $validation = $this->accommodationBookingService->validateDateRange(
            $listing,
            $checkIn,
            $checkOut,
            $guests
        );

        if (! $validation['valid']) {
            return response()->json([
                'message' => $validation['message'],
                'blocked_dates' => $validation['blocked_dates'] ?? null,
            ], 422);
        }

        // Find a slot for the check-in date
        $slot = $this->accommodationBookingService->findSlotForCheckIn($listing, $checkIn);

        if (! $slot) {
            return response()->json([
                'message' => 'No availability on the check-in date',
            ], 422);
        }

        // Calculate accommodation price
        $nights = $validation['nights'];
        $priceCalculation = $this->accommodationBookingService->calculatePrice(
            $listing,
            $nights,
            $currency
        );

        // Create the hold with accommodation-specific metadata
        // Note: quantity=1 because we're booking one property/unit, not individual seats
        // The guests count is stored in metadata for display purposes
        $hold = BookingHold::createForSlot(
            slot: $slot,
            user: $user,
            quantity: 1,
            sessionId: $sessionId,
            personTypeBreakdown: ['adult' => $guests], // For compatibility
            currency: $currency,
            priceSnapshot: $priceCalculation['total'],
            pricingCountryCode: $pricingContext['country_code'],
            pricingSource: $pricingContext['source'],
            extras: $extras,
            metadata: [
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'nights' => $nights,
                'guests' => $guests,
                'nightly_rate' => $priceCalculation['nightly_rate'],
                'pricing_model' => 'per_night',
            ]
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
