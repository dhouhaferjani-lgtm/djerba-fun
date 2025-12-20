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
     * Supports both authenticated users and guest checkout via session_id.
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $hold = BookingHold::findOrFail($request->input('hold_id'));

        // Verify hold ownership: either authenticated user owns it, or guest has matching session_id
        $userId = $request->user()?->id;
        $sessionId = $request->input('session_id');

        $isOwner = ($userId && $hold->user_id === $userId) ||
                   ($sessionId && $hold->session_id === $sessionId);

        if (! $isOwner) {
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

        // Pass authenticated user's ID if available (user may have logged in during checkout)
        // Use getTravelers() which handles both legacy traveler_info and new travelers array
        $booking = $this->bookingService->createFromHold(
            hold: $hold,
            travelers: $request->getTravelers(),
            extras: $request->input('extras', []),
            authenticatedUserId: $request->user()?->id
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
     * Get booking details for guest users via session_id.
     */
    public function showGuest(Request $request, Booking $booking): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->query('session_id');

        if (!$sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        $booking->load(['listing', 'availabilitySlot', 'paymentIntents', 'participants', 'bookingExtras.extra']);

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
