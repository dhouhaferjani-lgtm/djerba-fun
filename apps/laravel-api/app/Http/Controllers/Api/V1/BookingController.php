<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {
    }

    /**
     * List user's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->forUser($request->user()->id)
            ->with(['listing', 'availabilitySlot', 'paymentIntents'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => BookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Create a booking from a hold.
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $hold = BookingHold::findOrFail($request->input('hold_id'));

        // Ensure the hold belongs to the authenticated user
        if ($hold->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized to use this booking hold.',
            ], 403);
        }

        // Check if hold is still valid
        if ($hold->hasExpired()) {
            return response()->json([
                'message' => 'This booking hold has expired. Please create a new hold.',
            ], 422);
        }

        $booking = $this->bookingService->createFromHold(
            hold: $hold,
            travelerInfo: $request->input('traveler_info'),
            extras: $request->input('extras', [])
        );

        // Load relationships
        $booking->load(['listing', 'availabilitySlot', 'user']);

        return response()->json([
            'data' => new BookingResource($booking),
            'message' => 'Booking created successfully. Please complete the payment.',
        ], 201);
    }

    /**
     * Get booking details.
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        // Ensure the booking belongs to the authenticated user
        Gate::authorize('view', $booking);

        $booking->load(['listing', 'availabilitySlot', 'user', 'paymentIntents']);

        return response()->json([
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        // Ensure the booking belongs to the authenticated user
        Gate::authorize('cancel', $booking);

        if (! $booking->canBeCancelled()) {
            return response()->json([
                'message' => 'This booking cannot be cancelled.',
            ], 422);
        }

        $booking = $this->bookingService->cancel(
            booking: $booking,
            reason: $request->input('reason')
        );

        return response()->json([
            'data' => new BookingResource($booking),
            'message' => 'Booking cancelled successfully.',
        ]);
    }
}
