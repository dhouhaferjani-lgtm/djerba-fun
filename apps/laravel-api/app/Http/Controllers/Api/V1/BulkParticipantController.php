<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingParticipantResource;
use App\Models\Booking;
use App\Models\BookingParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Bulk Participant Controller
 *
 * Handles bulk participant name updates for multiple bookings,
 * used when users want to apply the same names across multiple tours.
 */
class BulkParticipantController extends Controller
{
    /**
     * Apply participant names from template to multiple bookings.
     *
     * POST /api/v1/bookings/participants/bulk-apply
     * (Authenticated users)
     */
    public function bulkApply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_ids' => 'required|array|min:1|max:20',
            'booking_ids.*' => 'required|uuid',
            'participants' => 'required|array|min:1|max:50',
            'participants.*.first_name' => 'required|string|max:100',
            'participants.*.last_name' => 'required|string|max:100',
            'participants.*.email' => 'nullable|email|max:255',
            'participants.*.phone' => 'nullable|string|max:50',
        ]);

        $results = ['success' => [], 'failed' => []];

        DB::transaction(function () use ($validated, &$results) {
            foreach ($validated['booking_ids'] as $bookingId) {
                try {
                    $booking = Booking::findOrFail($bookingId);

                    // Verify user can update this booking
                    Gate::authorize('update', $booking);

                    // Verify booking is confirmed (participants can only be updated on confirmed bookings)
                    if (! $booking->isConfirmed()) {
                        throw new \Exception('Participant details can only be updated for confirmed bookings.');
                    }

                    // Update participants matching by position
                    $this->applyParticipantsToBooking($booking, $validated['participants']);

                    $results['success'][] = [
                        'booking_id' => $bookingId,
                        'booking_number' => $booking->booking_number,
                        'participants_updated' => $booking->participants()->count(),
                    ];
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    $results['failed'][] = [
                        'booking_id' => $bookingId,
                        'error' => 'Unauthorized to update this booking',
                    ];
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    $results['failed'][] = [
                        'booking_id' => $bookingId,
                        'error' => 'Booking not found',
                    ];
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'booking_id' => $bookingId,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        Log::info('BulkParticipantController: Bulk apply completed', [
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'results' => $results,
            'message' => count($results['success']) > 0
                ? 'Participants updated successfully'
                : 'No participants were updated',
        ]);
    }

    /**
     * Apply participant names from template to multiple bookings (guest access via session_id).
     *
     * POST /api/v1/bookings/participants/bulk-apply/guest
     * (Guest users with valid session_id)
     */
    public function bulkApplyGuest(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'message' => 'Session ID is required for guest access.',
            ], 403);
        }

        $validated = $request->validate([
            'booking_ids' => 'required|array|min:1|max:20',
            'booking_ids.*' => 'required|uuid',
            'participants' => 'required|array|min:1|max:50',
            'participants.*.first_name' => 'required|string|max:100',
            'participants.*.last_name' => 'required|string|max:100',
            'participants.*.email' => 'nullable|email|max:255',
            'participants.*.phone' => 'nullable|string|max:50',
        ]);

        $results = ['success' => [], 'failed' => []];

        DB::transaction(function () use ($validated, $sessionId, &$results) {
            foreach ($validated['booking_ids'] as $bookingId) {
                try {
                    $booking = Booking::findOrFail($bookingId);

                    // Verify session_id matches the booking
                    if ($booking->session_id !== $sessionId) {
                        throw new \Exception('Invalid session for this booking');
                    }

                    // Verify booking is confirmed
                    if (! $booking->isConfirmed()) {
                        throw new \Exception('Participant details can only be updated for confirmed bookings.');
                    }

                    // Update participants matching by position
                    $this->applyParticipantsToBooking($booking, $validated['participants']);

                    $results['success'][] = [
                        'booking_id' => $bookingId,
                        'booking_number' => $booking->booking_number,
                        'participants_updated' => $booking->participants()->count(),
                    ];
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    $results['failed'][] = [
                        'booking_id' => $bookingId,
                        'error' => 'Booking not found',
                    ];
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'booking_id' => $bookingId,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        Log::info('BulkParticipantController: Guest bulk apply completed', [
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'session_id' => substr($sessionId, 0, 8) . '...',
        ]);

        return response()->json([
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'results' => $results,
            'message' => count($results['success']) > 0
                ? 'Participants updated successfully'
                : 'No participants were updated',
        ]);
    }

    /**
     * Apply template participant names to a booking's participants.
     *
     * Matches participants by their position (order) in the booking.
     */
    private function applyParticipantsToBooking(Booking $booking, array $templateParticipants): void
    {
        $bookingParticipants = $booking->participants()->orderBy('id')->get();

        foreach ($bookingParticipants as $index => $participant) {
            if (isset($templateParticipants[$index])) {
                $template = $templateParticipants[$index];
                $participant->update([
                    'first_name' => $template['first_name'],
                    'last_name' => $template['last_name'],
                    'email' => $template['email'] ?? null,
                    'phone' => $template['phone'] ?? null,
                ]);
            }
        }

        // Update booking's traveler_details_status if all participants now have names
        $this->updateTravelerDetailsStatus($booking);
    }

    /**
     * Update the booking's traveler_details_status based on participant completion.
     */
    private function updateTravelerDetailsStatus(Booking $booking): void
    {
        $booking->refresh();

        $totalParticipants = $booking->participants()->count();
        $completeParticipants = $booking->participants()
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->count();

        if ($totalParticipants > 0 && $completeParticipants === $totalParticipants) {
            $booking->update([
                'traveler_details_status' => 'complete',
                'traveler_details_completed_at' => now(),
            ]);
        } elseif ($completeParticipants > 0) {
            $booking->update([
                'traveler_details_status' => 'partial',
            ]);
        }
    }
}
