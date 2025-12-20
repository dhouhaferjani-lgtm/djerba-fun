<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingParticipantResource;
use App\Models\Booking;
use App\Models\BookingParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VoucherController extends Controller
{
    /**
     * List all vouchers for a booking.
     */
    public function index(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('view', $booking);

        if (!$booking->canGenerateVouchers()) {
            $reason = !$booking->isConfirmed()
                ? 'Booking is not confirmed'
                : 'Participant names are required but not complete';

            return response()->json([
                'message' => 'Vouchers are not available yet',
                'reason' => $reason,
                'canGenerate' => false,
            ], 422);
        }

        $participants = $booking->participants()
            ->with(['booking.listing', 'booking.availabilitySlot'])
            ->orderBy('created_at')
            ->get();

        $vouchers = $participants->map(fn ($p) => $this->formatVoucher($p, $booking));

        return response()->json([
            'data' => $vouchers,
            'canGenerate' => true,
            'booking' => [
                'bookingNumber' => $booking->booking_number,
                'listingTitle' => $booking->listing?->getTranslation('title', app()->getLocale()),
                'eventDate' => $booking->availabilitySlot?->start_time?->toIso8601String(),
            ],
        ]);
    }

    /**
     * List all vouchers for a booking (guest access via session_id).
     */
    public function indexGuest(Request $request, Booking $booking): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->query('session_id');

        if (!$sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        if (!$booking->canGenerateVouchers()) {
            $reason = !$booking->isConfirmed()
                ? 'Booking is not confirmed'
                : 'Participant names are required but not complete';

            return response()->json([
                'message' => 'Vouchers are not available yet',
                'reason' => $reason,
                'canGenerate' => false,
            ], 422);
        }

        $participants = $booking->participants()
            ->with(['booking.listing', 'booking.availabilitySlot'])
            ->orderBy('created_at')
            ->get();

        $vouchers = $participants->map(fn ($p) => $this->formatVoucher($p, $booking));

        return response()->json([
            'data' => $vouchers,
            'canGenerate' => true,
            'booking' => [
                'bookingNumber' => $booking->booking_number,
                'listingTitle' => $booking->listing?->getTranslation('title', app()->getLocale()),
                'eventDate' => $booking->availabilitySlot?->start_time?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get a single voucher by voucher code.
     */
    public function show(Request $request, Booking $booking, string $voucherCode): JsonResponse
    {
        Gate::authorize('view', $booking);

        $participant = $booking->participants()
            ->byVoucherCode($voucherCode)
            ->first();

        if (!$participant) {
            return response()->json([
                'message' => 'Voucher not found',
            ], 404);
        }

        if (!$booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet',
                'canGenerate' => false,
            ], 422);
        }

        return response()->json([
            'data' => $this->formatVoucher($participant, $booking),
        ]);
    }

    /**
     * Format voucher data for display/printing.
     */
    private function formatVoucher(BookingParticipant $participant, Booking $booking): array
    {
        $listing = $booking->listing;
        $slot = $booking->availabilitySlot;

        return [
            'voucherCode' => $participant->voucher_code,
            'qrCodeData' => $participant->getQrCodeData(),
            'participant' => [
                'id' => $participant->id,
                'firstName' => $participant->first_name,
                'lastName' => $participant->last_name,
                'fullName' => $participant->full_name,
                'personType' => $participant->person_type,
                'checkedIn' => $participant->checked_in,
            ],
            'booking' => [
                'bookingNumber' => $booking->booking_number,
            ],
            'event' => [
                'title' => $listing?->getTranslation('title', app()->getLocale()),
                'date' => $slot?->date?->toDateString(),
                'time' => $slot?->start_time?->format('H:i'),
                'location' => $listing?->location?->getTranslation('name', app()->getLocale()),
            ],
            'vendor' => [
                'name' => $listing?->vendor?->display_name ?? $listing?->vendor?->name,
            ],
        ];
    }
}
