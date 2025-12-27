<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendVoucherEmailJob;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Services\VoucherPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class VoucherController extends Controller
{
    /**
     * List all vouchers for a booking.
     */
    public function index(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('view', $booking);

        if (! $booking->canGenerateVouchers()) {
            $reason = ! $booking->isConfirmed()
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

        if (! $sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        if (! $booking->canGenerateVouchers()) {
            $reason = ! $booking->isConfirmed()
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

        if (! $participant) {
            return response()->json([
                'message' => 'Voucher not found',
            ], 404);
        }

        if (! $booking->canGenerateVouchers()) {
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
     * Download single voucher as PDF.
     */
    public function downloadSingle(
        Request $request,
        Booking $booking,
        string $voucherCode,
        VoucherPdfService $pdfService
    ): Response {
        Gate::authorize('view', $booking);

        if (! $booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet',
            ], 422);
        }

        $participant = $booking->participants()
            ->byVoucherCode($voucherCode)
            ->firstOrFail();

        $pdf = $pdfService->generateSingleVoucher($participant);
        $filename = $pdfService->getFilename($booking, $voucherCode);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download all vouchers for a booking as PDF.
     */
    public function downloadAll(
        Request $request,
        Booking $booking,
        VoucherPdfService $pdfService
    ): Response {
        Gate::authorize('view', $booking);

        if (! $booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet',
            ], 422);
        }

        $pdf = $pdfService->generateAllVouchers($booking);
        $filename = $pdfService->getFilename($booking);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Email single voucher.
     */
    public function emailSingle(
        Request $request,
        Booking $booking,
        string $voucherCode
    ): JsonResponse {
        Gate::authorize('view', $booking);

        if (! $booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet',
            ], 422);
        }

        $participant = $booking->participants()
            ->byVoucherCode($voucherCode)
            ->firstOrFail();

        $email = $request->input('email') ?? $participant->email ?? $booking->getPrimaryEmail();

        if (! $email) {
            return response()->json([
                'message' => 'No email address available',
            ], 422);
        }

        // Queue email job
        SendVoucherEmailJob::dispatch($booking, $voucherCode, $email);

        return response()->json([
            'message' => 'Voucher will be sent to ' . $email,
            'email' => $email,
        ]);
    }

    /**
     * Email all vouchers.
     */
    public function emailAll(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('view', $booking);

        if (! $booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet',
            ], 422);
        }

        $email = $request->input('email') ?? $booking->getPrimaryEmail();

        if (! $email) {
            return response()->json([
                'message' => 'No email address available',
            ], 422);
        }

        // Queue email job
        SendVoucherEmailJob::dispatch($booking, null, $email);

        return response()->json([
            'message' => 'Vouchers will be sent to ' . $email,
            'email' => $email,
            'count' => $booking->participants()->count(),
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
            'badgeNumber' => $participant->badge_number,
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
