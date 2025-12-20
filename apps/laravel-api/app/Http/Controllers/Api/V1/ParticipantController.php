<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingParticipantResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
    public function update(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        $validated = $request->validate([
            'participants' => 'required|array',
            'participants.*.id' => 'required|uuid',
            'participants.*.first_name' => 'nullable|string|max:255',
            'participants.*.last_name' => 'nullable|string|max:255',
            'participants.*.email' => 'nullable|email|max:255',
            'participants.*.phone' => 'nullable|string|max:50',
            'participants.*.person_type' => 'nullable|string|max:50',
            'participants.*.special_requests' => 'nullable|string|max:1000',
        ]);

        $updated = 0;
        $errors = [];

        foreach ($validated['participants'] as $index => $participantData) {
            $participant = $booking->participants()
                ->where('id', $participantData['id'])
                ->first();

            if (!$participant) {
                $errors[] = [
                    'index' => $index,
                    'id' => $participantData['id'],
                    'message' => 'Participant not found',
                ];
                continue;
            }

            $participant->update([
                'first_name' => $participantData['first_name'] ?? $participant->first_name,
                'last_name' => $participantData['last_name'] ?? $participant->last_name,
                'email' => $participantData['email'] ?? $participant->email,
                'phone' => $participantData['phone'] ?? $participant->phone,
                'person_type' => $participantData['person_type'] ?? $participant->person_type,
                'special_requests' => $participantData['special_requests'] ?? $participant->special_requests,
            ]);

            $updated++;
        }

        // Reload the booking with participants
        $booking->load('participants');

        $response = [
            'data' => new BookingResource($booking),
            'message' => $updated > 0 ? 'Participants updated successfully' : 'No participants were updated',
            'updated' => $updated,
        ];

        if (!empty($errors)) {
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

        if (!$sessionId || $booking->session_id !== $sessionId) {
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
    public function updateGuest(Request $request, Booking $booking): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->query('session_id');

        if (!$sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        $validated = $request->validate([
            'participants' => 'required|array',
            'participants.*.id' => 'required|uuid',
            'participants.*.first_name' => 'nullable|string|max:255',
            'participants.*.last_name' => 'nullable|string|max:255',
            'participants.*.email' => 'nullable|email|max:255',
            'participants.*.phone' => 'nullable|string|max:50',
            'participants.*.person_type' => 'nullable|string|max:50',
            'participants.*.special_requests' => 'nullable|string|max:1000',
        ]);

        $updated = 0;
        $errors = [];

        foreach ($validated['participants'] as $index => $participantData) {
            $participant = $booking->participants()
                ->where('id', $participantData['id'])
                ->first();

            if (!$participant) {
                $errors[] = [
                    'index' => $index,
                    'id' => $participantData['id'],
                    'message' => 'Participant not found',
                ];
                continue;
            }

            $participant->update([
                'first_name' => $participantData['first_name'] ?? $participant->first_name,
                'last_name' => $participantData['last_name'] ?? $participant->last_name,
                'email' => $participantData['email'] ?? $participant->email,
                'phone' => $participantData['phone'] ?? $participant->phone,
                'person_type' => $participantData['person_type'] ?? $participant->person_type,
                'special_requests' => $participantData['special_requests'] ?? $participant->special_requests,
            ]);

            $updated++;
        }

        // Reload the booking with participants
        $booking->load('participants');

        $response = [
            'data' => new BookingResource($booking),
            'message' => $updated > 0 ? 'Participants updated successfully' : 'No participants were updated',
            'updated' => $updated,
        ];

        if (!empty($errors)) {
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

        if (!$participant) {
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
