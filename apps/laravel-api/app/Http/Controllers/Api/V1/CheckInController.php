<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingExtraResource;
use App\Http\Resources\BookingParticipantResource;
use App\Models\BookingParticipant;
use App\Services\ExtrasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(
        private readonly ExtrasService $extrasService
    ) {}

    /**
     * Check in a participant by voucher code.
     * This endpoint is for vendors to scan QR codes.
     */
    public function checkIn(Request $request, string $voucherCode): JsonResponse
    {
        $participant = BookingParticipant::byVoucherCode($voucherCode)
            ->with(['booking.listing.vendor', 'booking.availabilitySlot'])
            ->first();

        if (! $participant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid voucher code',
            ], 404);
        }

        $booking = $participant->booking;
        $user = $request->user();

        // Check if the authenticated user is the vendor for this listing
        if (! $user || $booking->listing?->vendor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to check in participants for this listing',
            ], 403);
        }

        // Check booking status
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is not confirmed',
                'bookingStatus' => $booking->status->value,
            ], 422);
        }

        // Check if already checked in
        if ($participant->checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'This participant has already been checked in',
                'checkedInAt' => $participant->checked_in_at?->toIso8601String(),
                'participant' => new BookingParticipantResource($participant),
            ], 409);
        }

        // Perform check-in
        $participant->checkIn();

        // Get check-in stats for this booking
        $totalParticipants = $booking->participants()->count();
        $checkedInCount = $booking->participants()->checkedIn()->count();

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful',
            'participant' => new BookingParticipantResource($participant->fresh()),
            'booking' => [
                'bookingNumber' => $booking->booking_number,
                'listingTitle' => $booking->listing?->getTranslation('title', app()->getLocale()),
                'eventDate' => $booking->availabilitySlot?->start_time?->toIso8601String(),
            ],
            'checkInStats' => [
                'checkedIn' => $checkedInCount,
                'total' => $totalParticipants,
                'allCheckedIn' => $checkedInCount === $totalParticipants,
            ],
        ]);
    }

    /**
     * Undo a check-in (useful for mistakes).
     */
    public function undoCheckIn(Request $request, string $voucherCode): JsonResponse
    {
        $participant = BookingParticipant::byVoucherCode($voucherCode)
            ->with(['booking.listing.vendor'])
            ->first();

        if (! $participant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid voucher code',
            ], 404);
        }

        $booking = $participant->booking;
        $user = $request->user();

        // Check if the authenticated user is the vendor for this listing
        if (! $user || $booking->listing?->vendor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to manage check-ins for this listing',
            ], 403);
        }

        if (! $participant->checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'This participant is not checked in',
            ], 422);
        }

        $participant->undoCheckIn();

        return response()->json([
            'success' => true,
            'message' => 'Check-in reversed',
            'participant' => new BookingParticipantResource($participant->fresh()),
        ]);
    }

    /**
     * Lookup voucher details without checking in.
     * Useful for vendors to preview before confirming check-in.
     */
    public function lookup(Request $request, string $voucherCode): JsonResponse
    {
        $participant = BookingParticipant::byVoucherCode($voucherCode)
            ->with(['booking.listing.vendor', 'booking.availabilitySlot', 'booking.user', 'booking.bookingExtras.extra'])
            ->first();

        if (! $participant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid voucher code',
            ], 404);
        }

        $booking = $participant->booking;
        $user = $request->user();
        $locale = app()->getLocale();

        // Check if the authenticated user is the vendor for this listing
        if (! $user || $booking->listing?->vendor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this voucher',
            ], 403);
        }

        // Get extras summary for check-in display
        $extrasSummary = $this->extrasService->getCheckInSummary($booking, $locale);

        return response()->json([
            'success' => true,
            'participant' => new BookingParticipantResource($participant),
            'booking' => [
                'bookingNumber' => $booking->booking_number,
                'status' => $booking->status->value,
                'statusLabel' => $booking->status->label(),
                'listingTitle' => $booking->listing?->getTranslation('title', $locale),
                'eventDate' => $booking->availabilitySlot?->date?->toDateString(),
                'eventTime' => $booking->availabilitySlot?->start_time?->format('H:i'),
                'bookedBy' => $booking->user?->display_name ?? $booking->billing_contact_name,
                'totalGuests' => $booking->quantity,
            ],
            'extras' => $extrasSummary,
            'extrasDetails' => BookingExtraResource::collection($booking->activeBookingExtras),
            'checkInStats' => [
                'checkedIn' => $booking->participants()->checkedIn()->count(),
                'total' => $booking->participants()->count(),
            ],
        ]);
    }

    /**
     * Bulk check-in multiple participants by voucher codes.
     * Useful for batch operations and offline sync.
     */
    public function bulkCheckIn(Request $request): JsonResponse
    {
        $request->validate([
            'voucher_codes' => 'required|array|min:1|max:100',
            'voucher_codes.*' => 'required|string',
        ]);

        $voucherCodes = $request->input('voucher_codes');
        $user = $request->user();

        $successResults = [];
        $failedResults = [];

        foreach ($voucherCodes as $voucherCode) {
            try {
                $participant = BookingParticipant::byVoucherCode($voucherCode)
                    ->with(['booking.listing.vendor'])
                    ->first();

                if (! $participant) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Invalid voucher code',
                    ];
                    continue;
                }

                // Verify vendor ownership
                if ($participant->booking->listing?->vendor_id !== $user->id) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Not authorized',
                    ];
                    continue;
                }

                // Check booking status
                if ($participant->booking->status !== BookingStatus::CONFIRMED) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Booking not confirmed',
                    ];
                    continue;
                }

                // Check if already checked in
                if ($participant->checked_in) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Already checked in',
                        'checkedInAt' => $participant->checked_in_at?->toIso8601String(),
                    ];
                    continue;
                }

                // Perform check-in
                $participant->checkIn();

                $successResults[] = [
                    'voucherCode' => $voucherCode,
                    'participantName' => $participant->full_name,
                    'badgeNumber' => $participant->badge_number,
                    'checkedInAt' => $participant->checked_in_at?->toIso8601String(),
                ];
            } catch (\Exception $e) {
                $failedResults[] = [
                    'voucherCode' => $voucherCode,
                    'error' => 'Check-in failed: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'successCount' => count($successResults),
            'failedCount' => count($failedResults),
            'results' => [
                'success' => $successResults,
                'failed' => $failedResults,
            ],
        ]);
    }

    /**
     * Bulk undo check-ins.
     * Useful for correcting mistakes in batch.
     */
    public function bulkUndoCheckIn(Request $request): JsonResponse
    {
        $request->validate([
            'voucher_codes' => 'required|array|min:1|max:100',
            'voucher_codes.*' => 'required|string',
        ]);

        $voucherCodes = $request->input('voucher_codes');
        $user = $request->user();

        $successResults = [];
        $failedResults = [];

        foreach ($voucherCodes as $voucherCode) {
            try {
                $participant = BookingParticipant::byVoucherCode($voucherCode)
                    ->with(['booking.listing.vendor'])
                    ->first();

                if (! $participant) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Invalid voucher code',
                    ];
                    continue;
                }

                // Verify vendor ownership
                if ($participant->booking->listing?->vendor_id !== $user->id) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Not authorized',
                    ];
                    continue;
                }

                // Check if not checked in
                if (! $participant->checked_in) {
                    $failedResults[] = [
                        'voucherCode' => $voucherCode,
                        'error' => 'Not checked in',
                    ];
                    continue;
                }

                // Undo check-in
                $participant->undoCheckIn();

                $successResults[] = [
                    'voucherCode' => $voucherCode,
                    'participantName' => $participant->full_name,
                ];
            } catch (\Exception $e) {
                $failedResults[] = [
                    'voucherCode' => $voucherCode,
                    'error' => 'Undo failed: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'successCount' => count($successResults),
            'failedCount' => count($failedResults),
            'results' => [
                'success' => $successResults,
                'failed' => $failedResults,
            ],
        ]);
    }
}
