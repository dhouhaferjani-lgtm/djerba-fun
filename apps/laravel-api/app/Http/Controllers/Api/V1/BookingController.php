<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingAlreadyLinkedException;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\EmailMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Services\BookingLinkingService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingLinkingService $bookingLinkingService
    ) {}

    /**
     * List user's bookings.
     *
     * Includes both:
     * - Bookings directly owned by user (user_id matches)
     * - Guest bookings where billing email matches user's email
     *
     * Performance optimizations:
     * - Eager loading relationships with specific column selection
     * - Select only required columns from bookings table
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userEmail = strtolower(trim($user->email));

        $bookings = Booking::query()
            ->where(function ($query) use ($user, $userEmail) {
                // Direct ownership (user_id matches)
                $query->where('user_id', $user->id)
                    // OR email-based match for guest bookings (with robust matching)
                    ->orWhere(function ($q) use ($userEmail) {
                        $q->whereNull('user_id')
                            ->where(function ($emailQuery) use ($userEmail) {
                                // Try multiple email extraction methods for robustness
                                $emailQuery->whereRaw("LOWER(TRIM(billing_contact->>'email')) = ?", [$userEmail])
                                    ->orWhereRaw("LOWER(TRIM(traveler_info->>'email')) = ?", [$userEmail]);
                            });
                    });
            })
            ->selectApi() // Use model scope to prevent column mismatch issues
            // Performance: Eager load relationships with column selection to prevent N+1 queries
            ->with([
                'listing:id,uuid,vendor_id,location_id,title,slug,service_type,status,pricing,duration,require_traveler_names',
                'listing.location:id,uuid,name,slug,city',
                'listing.vendor:id,uuid',
                'availabilitySlot:id,listing_id,date,start_time,end_time,remaining_capacity',
                'paymentIntents:id,booking_id,amount,currency,status,payment_method,created_at',
            ])
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
     *
     * Performance: Eager load with specific columns
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        // Performance: Eager load with specific columns
        $hold = BookingHold::with([
            'slot:id,listing_id,date,start_time,end_time,capacity,remaining_capacity',
            'listing:id,uuid,vendor_id,location_id,title,slug,pricing,service_type,status,require_traveler_names',
        ])->findOrFail($request->input('hold_id'));

        // Verify hold ownership: either authenticated user owns it, or guest has matching session_id
        $userId = $request->user()?->id;
        $sessionId = $request->input('session_id');

        // Diagnostic logging to track auth state during booking creation
        Log::info('BookingController::store - Creating booking', [
            'has_user' => $request->user() !== null,
            'user_id' => $userId,
            'user_email' => $request->user()?->email,
            'hold_id' => $request->input('hold_id'),
            'hold_user_id' => $hold->user_id,
            'session_id' => $sessionId,
        ]);

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

        // Performance: Load relationships with specific columns
        $booking->load([
            'listing:id,uuid,vendor_id,location_id,title,slug,service_type,status,pricing,duration',
            'listing.location:id,uuid,name,slug,city',
            'listing.vendor:id,uuid',
            'availabilitySlot:id,listing_id,date,start_time,end_time,remaining_capacity',
            'user:id,uuid,first_name,last_name,email',
        ]);

        return response()->json([
            'data' => new BookingResource($booking),
            'message' => 'Booking created successfully. Please complete the payment.',
        ], 201);
    }

    /**
     * Get booking details.
     *
     * Performance: Eager load relationships with specific columns
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        // Ensure the booking belongs to the authenticated user
        Gate::authorize('view', $booking);

        // Performance: Load relationships with specific columns
        $booking->load([
            'listing:id,uuid,vendor_id,location_id,title,slug,service_type,status,pricing,duration,require_traveler_names',
            'listing.location:id,uuid,name,slug,city',
            'listing.vendor:id,uuid',
            'availabilitySlot:id,listing_id,date,start_time,end_time,remaining_capacity',
            'user:id,uuid,first_name,last_name,email',
            'paymentIntents:id,booking_id,amount,currency,status,payment_method,created_at',
            'participants:id,booking_id,first_name,last_name,email,phone,person_type',
        ]);

        return response()->json([
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Get booking details for guest users via session_id.
     *
     * Performance: Eager load relationships with specific columns
     */
    public function showGuest(Request $request, Booking $booking): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->query('session_id');

        if (! $sessionId || $booking->session_id !== $sessionId) {
            return response()->json([
                'message' => 'Unauthorized. Invalid session.',
            ], 403);
        }

        // Performance: Load relationships with specific columns
        $booking->load([
            'listing:id,uuid,vendor_id,location_id,title,slug,service_type,status,pricing,duration,require_traveler_names',
            'listing.location:id,uuid,name,slug,city',
            'availabilitySlot:id,listing_id,date,start_time,end_time,remaining_capacity',
            'paymentIntents:id,booking_id,amount,currency,status,payment_method,created_at',
            'participants:id,booking_id,first_name,last_name,email,phone,age,badge_number',
            'bookingExtras.extra:id,listing_id,name,description,price,currency',
        ]);

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

    /**
     * Get all claimable bookings for the authenticated user.
     * Returns guest bookings that match the user's email.
     */
    public function claimable(Request $request): JsonResponse
    {
        $user = $request->user();
        $claimableBookings = $this->bookingLinkingService->findClaimableBookings($user);

        return response()->json([
            'data' => BookingResource::collection($claimableBookings),
            'meta' => [
                'total' => $claimableBookings->count(),
            ],
        ]);
    }

    /**
     * Link selected bookings to the authenticated user's account.
     */
    public function link(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['required', 'uuid', 'exists:bookings,id'],
        ]);

        $user = $request->user();

        // CRITICAL SECURITY: Verify all bookings are actually claimable by this user
        $claimableIds = $this->bookingLinkingService
            ->findClaimableBookings($user)
            ->pluck('id')
            ->toArray();

        $invalidIds = array_diff($validated['booking_ids'], $claimableIds);

        if (! empty($invalidIds)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_BOOKING_IDS',
                    'message' => 'One or more booking IDs are not claimable by your account.',
                    'invalid_ids' => array_values($invalidIds),
                ],
            ], 403);
        }

        $result = $this->bookingLinkingService->linkBookingsByEmail(
            user: $user,
            bookingIds: $validated['booking_ids']
        );

        return response()->json([
            'data' => BookingResource::collection($result['bookings']),
            'meta' => [
                'linked' => $result['linked'],
            ],
            'message' => "Successfully linked {$result['linked']} booking(s) to your account.",
        ]);
    }

    /**
     * Claim a booking by booking number.
     * Verifies email match before linking.
     */
    public function claim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_number' => ['required', 'string', 'regex:/^GA-\d{6}-[A-Z0-9]{5}$/'],
        ]);

        $user = $request->user();

        try {
            $booking = $this->bookingLinkingService->linkBookingByNumber(
                user: $user,
                bookingNumber: $validated['booking_number']
            );

            return response()->json([
                'data' => new BookingResource($booking),
                'message' => 'Booking successfully claimed and linked to your account.',
            ]);
        } catch (BookingNotFoundException $e) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => $e->getMessage(),
                ],
            ], 404);
        } catch (BookingAlreadyLinkedException $e) {
            return response()->json([
                'error' => [
                    'code' => 'ALREADY_LINKED',
                    'message' => $e->getMessage(),
                ],
            ], 409);
        } catch (EmailMismatchException $e) {
            return response()->json([
                'error' => [
                    'code' => 'EMAIL_MISMATCH',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        } catch (\Exception $e) {
            Log::error('Unexpected error claiming booking', [
                'user_id' => $user->id,
                'booking_number' => $validated['booking_number'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => [
                    'code' => 'CLAIM_FAILED',
                    'message' => 'An unexpected error occurred. Please try again.',
                ],
            ], 500);
        }
    }
}
