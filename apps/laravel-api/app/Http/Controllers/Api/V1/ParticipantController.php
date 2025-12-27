<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBookingParticipantsRequest;
use App\Http\Resources\BookingParticipantResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ParticipantController extends Controller
{
    /**
     * List all participants for a booking.
     */
    public function index(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('view', $booking);

        $participants = $booking->participants()->orderBy('created_at')->get();

        return response()->json([
            'data' => BookingParticipantResource::collection($participants),
            'meta' => [
                'total' => $participants->count(),
                'complete' => $participants->filter(fn ($p) => $p->isComplete())->count(),
                'requiresNames' => $booking->listing?->require_traveler_names ?? false,
            ],
        ]);
    }

    /**
     * Update all participants for a booking.
     * Expects an array of participant updates matching the quantity.
     */
    public function update(UpdateBookingParticipantsRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        // Only allow updates on CONFIRMED bookings
        if (! $booking->isConfirmed()) {
            return response()->json([
                'message' => 'Participant details can only be updated for confirmed bookings.',
            ], 422);
        }

        $validated = $request->validated();
        $participants = $validated['participants'];

        // Load all participants at once to avoid N+1 queries
        $participantIds = collect($participants)->pluck('id');
        $bookingParticipants = $booking->participants()
            ->whereIn('id', $participantIds)
            ->get()
            ->keyBy('id');

        $updated = 0;
        $errors = [];

        foreach ($participants as $index => $participantData) {
            $participant = $bookingParticipants->get($participantData['id']);

            if (! $participant) {
                $errors[] = [
                    'index' => $index,
                    'id' => $participantData['id'],
                    'message' => 'Participant not found for this booking',
                ];
                continue;
            }

            $participant->update([
                'first_name' => $participantData['first_name'],
                'last_name' => $participantData['last_name'],
                'email' => $participantData['email'] ?? null,
                'phone' => $participantData['phone'] ?? null,
            ]);

            $updated++;
        }

        // Reload the booking to get updated traveler_details_status
        // (The observer on BookingParticipant will have updated the status)
        $booking->refresh();

        $response = [
            'data' => BookingParticipantResource::collection($bookingParticipants->values()),
            'meta' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'traveler_details_status' => $booking->traveler_details_status,
                'traveler_details_completed_at' => $booking->traveler_details_completed_at?->toIso8601String(),
                'updated' => $updated,
            ],
            'message' => $updated > 0 ? 'Participants updated successfully' : 'No participants were updated',
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response);
    }

    /**
     * List all participants for a booking (guest access via session_id).
     */
    public function indexGuest(Request $request, Booking $booking): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->query('session_id');

        if (! $sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        $participants = $booking->participants()->orderBy('created_at')->get();

        return response()->json([
            'data' => BookingParticipantResource::collection($participants),
            'meta' => [
                'total' => $participants->count(),
                'complete' => $participants->filter(fn ($p) => $p->isComplete())->count(),
                'requiresNames' => $booking->listing?->require_traveler_names ?? false,
            ],
        ]);
    }

    /**
     * Update all participants for a booking (guest access via session_id).
     */
    public function updateGuest(UpdateBookingParticipantsRequest $request, Booking $booking): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');

        if (! $sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        // Only allow updates on CONFIRMED bookings
        if (! $booking->isConfirmed()) {
            return response()->json([
                'message' => 'Participant details can only be updated for confirmed bookings.',
            ], 422);
        }

        $validated = $request->validated();
        $participants = $validated['participants'];

        // Load all participants at once to avoid N+1 queries
        $participantIds = collect($participants)->pluck('id');
        $bookingParticipants = $booking->participants()
            ->whereIn('id', $participantIds)
            ->get()
            ->keyBy('id');

        $updated = 0;
        $errors = [];

        foreach ($participants as $index => $participantData) {
            $participant = $bookingParticipants->get($participantData['id']);

            if (! $participant) {
                $errors[] = [
                    'index' => $index,
                    'id' => $participantData['id'],
                    'message' => 'Participant not found for this booking',
                ];
                continue;
            }

            $participant->update([
                'first_name' => $participantData['first_name'],
                'last_name' => $participantData['last_name'],
                'email' => $participantData['email'] ?? null,
                'phone' => $participantData['phone'] ?? null,
            ]);

            $updated++;
        }

        // Reload the booking to get updated traveler_details_status
        // (The observer on BookingParticipant will have updated the status)
        $booking->refresh();

        $response = [
            'data' => BookingParticipantResource::collection($bookingParticipants->values()),
            'meta' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'traveler_details_status' => $booking->traveler_details_status,
                'traveler_details_completed_at' => $booking->traveler_details_completed_at?->toIso8601String(),
                'updated' => $updated,
            ],
            'message' => $updated > 0 ? 'Participants updated successfully' : 'No participants were updated',
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response);
    }

    /**
     * Get a single participant by voucher code.
     */
    public function showByVoucherCode(Request $request, string $voucherCode): JsonResponse
    {
        $participant = BookingParticipant::byVoucherCode($voucherCode)
            ->with(['booking.listing', 'booking.availabilitySlot'])
            ->first();

        if (! $participant) {
            return response()->json([
                'message' => 'Voucher not found',
            ], 404);
        }

        // Check if user has access to this booking
        Gate::authorize('view', $participant->booking);

        return response()->json([
            'data' => new BookingParticipantResource($participant),
            'booking' => new BookingResource($participant->booking),
        ]);
    }
}
