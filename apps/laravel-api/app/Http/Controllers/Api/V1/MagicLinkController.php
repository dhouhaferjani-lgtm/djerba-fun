<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Mail\MagicLinkMail;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class MagicLinkController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    /**
     * Validate a magic token and return booking data.
     * This endpoint is public - no authentication required.
     */
    public function show(string $token): JsonResponse
    {
        $booking = $this->bookingService->validateMagicToken($token);

        if (! $booking) {
            // Check if token exists but is expired
            $expiredBooking = $this->bookingService->findByMagicToken($token);

            if ($expiredBooking) {
                return response()->json([
                    'message' => 'This link has expired.',
                    'expired' => true,
                    'booking_number' => $expiredBooking->booking_number,
                ], 410); // 410 Gone
            }

            return response()->json([
                'message' => 'Invalid or unknown link.',
            ], 404);
        }

        // Load relationships for booking resource
        $booking->load([
            'listing',
            'availabilitySlot',
            'paymentIntents',
            'participants',
            'bookingExtras.extra',
        ]);

        return response()->json([
            'data' => new BookingResource($booking),
            'magic_links' => [
                'details' => $booking->getMagicLinkUrl(),
                'participants' => $booking->getMagicLinkUrl() . '/participants',
                'vouchers' => $booking->getMagicLinkUrl() . '/vouchers',
            ],
        ]);
    }

    /**
     * Request a new magic link by email and booking number.
     * Rate limited to prevent abuse.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'booking_number' => 'required|string',
        ]);

        // Rate limiting: 3 requests per hour per email
        $cacheKey = 'magic_link_resend:' . md5($validated['email']);
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $booking = $this->bookingService->findByEmailAndBookingNumber(
            $validated['email'],
            $validated['booking_number']
        );

        // Always return success to prevent email enumeration
        // But only actually send email if booking exists
        if ($booking) {
            // Regenerate the magic token
            $booking = $this->bookingService->regenerateMagicToken($booking);

            // Queue the magic link email
            Mail::to($validated['email'])->queue(new MagicLinkMail($booking));
        }

        // Increment rate limit counter
        Cache::put($cacheKey, $attempts + 1, now()->addHour());

        return response()->json([
            'message' => 'If a booking exists with this email and number, a new link has been sent.',
        ]);
    }

    /**
     * Get participants for a booking via magic token.
     */
    public function participants(string $token): JsonResponse
    {
        $booking = $this->bookingService->validateMagicToken($token);

        if (! $booking) {
            return response()->json([
                'message' => 'Invalid or expired link.',
            ], 404);
        }

        $participants = $booking->participants;

        return response()->json([
            'data' => $participants->map(fn ($p) => [
                'id' => $p->id,
                'firstName' => $p->first_name,
                'lastName' => $p->last_name,
                'email' => $p->email,
                'phone' => $p->phone,
                'personType' => $p->person_type,
                'voucherCode' => $p->voucher_code,
                'checkedIn' => $p->checked_in,
            ]),
            'meta' => [
                'bookingNumber' => $booking->booking_number,
                'requiresNames' => $booking->listing?->require_traveler_names ?? false,
                'totalParticipants' => $participants->count(),
                'completeParticipants' => $participants->filter(fn ($p) => $p->first_name && $p->last_name)->count(),
            ],
        ]);
    }

    /**
     * Update participants for a booking via magic token.
     */
    public function updateParticipants(Request $request, string $token): JsonResponse
    {
        $booking = $this->bookingService->validateMagicToken($token);

        if (! $booking) {
            return response()->json([
                'message' => 'Invalid or expired link.',
            ], 404);
        }

        $validated = $request->validate([
            'participants' => 'required|array',
            'participants.*.id' => 'required|uuid',
            'participants.*.first_name' => 'required|string|max:255',
            'participants.*.last_name' => 'required|string|max:255',
            'participants.*.email' => 'nullable|email|max:255',
            'participants.*.phone' => 'nullable|string|max:50',
        ]);

        foreach ($validated['participants'] as $participantData) {
            $participant = $booking->participants()->find($participantData['id']);

            if ($participant) {
                $participant->update([
                    'first_name' => $participantData['first_name'],
                    'last_name' => $participantData['last_name'],
                    'email' => $participantData['email'] ?? null,
                    'phone' => $participantData['phone'] ?? null,
                ]);
            }
        }

        return response()->json([
            'message' => 'Participants updated successfully.',
            'data' => $booking->fresh()->participants->map(fn ($p) => [
                'id' => $p->id,
                'firstName' => $p->first_name,
                'lastName' => $p->last_name,
                'email' => $p->email,
                'phone' => $p->phone,
                'personType' => $p->person_type,
            ]),
        ]);
    }

    /**
     * Get vouchers for a booking via magic token.
     */
    public function vouchers(string $token): JsonResponse
    {
        $booking = $this->bookingService->validateMagicToken($token);

        if (! $booking) {
            return response()->json([
                'message' => 'Invalid or expired link.',
            ], 404);
        }

        $booking->load(['listing', 'availabilitySlot', 'participants']);

        $requiresNames = $booking->listing?->require_traveler_names ?? false;
        $allNamesEntered = $booking->participants->every(fn ($p) => $p->first_name && $p->last_name);
        $canGenerate = ! $requiresNames || $allNamesEntered;

        if (! $canGenerate) {
            return response()->json([
                'canGenerate' => false,
                'message' => 'Please enter all participant names before downloading vouchers.',
            ]);
        }

        $vouchers = $booking->participants->map(fn ($participant) => [
            'voucherCode' => $participant->voucher_code,
            'qrCodeData' => $participant->voucher_code,
            'participant' => [
                'fullName' => trim("{$participant->first_name} {$participant->last_name}"),
                'personType' => $participant->person_type,
                'checkedIn' => $participant->checked_in,
            ],
            'event' => [
                'title' => $booking->listing?->title ?? 'Activity',
                'date' => $booking->availabilitySlot?->start_time?->format('l, F j, Y'),
                'time' => $booking->availabilitySlot?->start_time?->format('g:i A'),
                'location' => $booking->listing?->location?->name ?? null,
            ],
        ]);

        return response()->json([
            'canGenerate' => true,
            'data' => $vouchers,
            'booking' => [
                'bookingNumber' => $booking->booking_number,
                'listingTitle' => $booking->listing?->title,
            ],
        ]);
    }
}
