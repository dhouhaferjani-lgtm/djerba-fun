<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Partner\PartnerBookingResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\AvailabilitySlot;
use App\Models\User;
use App\Services\PartnerFinancialService;
use App\Services\PartnerWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerBookingController extends Controller
{
    public function __construct(
        protected PartnerFinancialService $financialService,
        protected PartnerWebhookService $webhookService
    ) {
    }
    /**
     * Create a booking on behalf of a traveler.
     *
     * @param Request $request
     * @return PartnerBookingResource
     */
    public function store(Request $request): PartnerBookingResource
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('bookings:create')) {
            abort(403, 'Partner does not have permission to create bookings');
        }

        $validated = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'slot_id' => 'required|exists:availability_slots,id',
            'quantity' => 'required|integer|min:1',
            'traveler_info' => 'required|array',
            'traveler_info.first_name' => 'required|string|max:255',
            'traveler_info.last_name' => 'required|string|max:255',
            'traveler_info.email' => 'required|email',
            'traveler_info.phone' => 'required|string|max:50',
            'partner_reference' => 'nullable|string|max:255',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $listing = Listing::findOrFail($validated['listing_id']);
        $slot = AvailabilitySlot::findOrFail($validated['slot_id']);

        // Verify slot belongs to listing
        if ($slot->listing_id !== $listing->id) {
            throw ValidationException::withMessages([
                'slot_id' => ['The selected slot does not belong to this listing.'],
            ]);
        }

        // Find or create user
        $user = User::where('email', $validated['traveler_info']['email'])->first();

        if (!$user) {
            // Create a traveler account
            $user = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'first_name' => $validated['traveler_info']['first_name'],
                    'last_name' => $validated['traveler_info']['last_name'],
                    'email' => $validated['traveler_info']['email'],
                    'phone' => $validated['traveler_info']['phone'],
                    'role' => 'traveler',
                    'status' => 'active',
                    'password' => bcrypt(bin2hex(random_bytes(16))), // Random password
                ]);

                // Create traveler profile
                $user->travelerProfile()->create([
                    'preferences' => [],
                ]);

                return $user;
            });
        }

        // Create booking
        $booking = DB::transaction(function () use ($listing, $slot, $validated, $user, $partner, $request) {
            // Check availability
            if ($slot->available_quantity < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => ['Not enough availability for the requested quantity.'],
                ]);
            }

            // Calculate pricing
            $unitPrice = $slot->price ?? $listing->pricing['base'];
            $subtotal = $unitPrice * $validated['quantity'];
            $tax = $subtotal * 0.1; // 10% tax
            $total = $subtotal + $tax;

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
                'availability_slot_id' => $slot->id,
                'partner_id' => $partner->id,
                'quantity' => $validated['quantity'],
                'status' => 'pending',
                'traveler_info' => $validated['traveler_info'],
                'special_requests' => $validated['special_requests'] ?? null,
                'pricing_snapshot' => [
                    'unit_price' => $unitPrice,
                    'quantity' => $validated['quantity'],
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'currency' => $listing->pricing['currency'] ?? 'EUR',
                ],
                'partner_metadata' => [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'partner_reference' => $validated['partner_reference'] ?? null,
                    'ip_address' => $request->ip(),
                ],
            ]);

            // Decrease available quantity
            $slot->decrement('available_quantity', $validated['quantity']);

            // Create financial transaction (charge partner for this booking)
            $this->financialService->createChargeForBooking($booking, $partner);

            return $booking;
        });

        $booking->load(['listing', 'availabilitySlot', 'user']);

        // Dispatch webhook asynchronously
        try {
            $this->webhookService->sendBookingCreated($booking, $partner);
        } catch (\Exception $e) {
            // Log but don't fail the request
            \Log::error('Failed to send booking created webhook', [
                'booking_id' => $booking->id,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
        }

        return new PartnerBookingResource($booking);
    }

    /**
     * List all bookings created by this partner.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('bookings:read')) {
            abort(403, 'Partner does not have permission to read bookings');
        }

        $query = Booking::where('partner_id', $partner->id)
            ->with(['listing', 'availabilitySlot', 'user'])
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereHas('availabilitySlot', function ($q) use ($request) {
                $q->where('start_time', '>=', $request->input('from_date'));
            });
        }

        if ($request->has('to_date')) {
            $query->whereHas('availabilitySlot', function ($q) use ($request) {
                $q->where('start_time', '<=', $request->input('to_date'));
            });
        }

        $bookings = $query->paginate($request->input('per_page', 15));

        return PartnerBookingResource::collection($bookings);
    }

    /**
     * Get booking details.
     *
     * @param Request $request
     * @param Booking $booking
     * @return PartnerBookingResource
     */
    public function show(Request $request, Booking $booking): PartnerBookingResource
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('bookings:read')) {
            abort(403, 'Partner does not have permission to read bookings');
        }

        // Verify this booking was created by this partner
        if ($booking->partner_id !== $partner->id) {
            abort(404, 'Booking not found');
        }

        $booking->load(['listing', 'availabilitySlot', 'user', 'paymentIntents']);

        return new PartnerBookingResource($booking);
    }

    /**
     * Confirm a booking as paid (partner collected payment offline).
     *
     * @param Request $request
     * @param Booking $booking
     * @return PartnerBookingResource
     */
    public function confirm(Request $request, Booking $booking): PartnerBookingResource
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('bookings:confirm')) {
            abort(403, 'Partner does not have permission to confirm bookings');
        }

        // Verify this booking was created by this partner
        if ($booking->partner_id !== $partner->id) {
            abort(404, 'Booking not found');
        }

        if ($booking->status !== 'pending') {
            throw ValidationException::withMessages([
                'booking' => ['Only pending bookings can be confirmed.'],
            ]);
        }

        $validated = $request->validate([
            'payment_reference' => 'required|string|max:255',
            'payment_method' => 'required|string|in:bank_transfer,cash,credit_card,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($booking, $validated, $partner) {
            // Update booking status
            $booking->update([
                'status' => 'confirmed',
                'partner_metadata' => array_merge($booking->partner_metadata ?? [], [
                    'payment_confirmed_at' => now()->toISOString(),
                    'payment_reference' => $validated['payment_reference'],
                    'payment_method' => $validated['payment_method'],
                    'confirmation_notes' => $validated['notes'] ?? null,
                ]),
            ]);
        });

        $booking->load(['listing', 'availabilitySlot', 'user', 'paymentIntents']);

        // Dispatch webhook asynchronously
        try {
            $this->webhookService->sendBookingConfirmed($booking, $partner);
        } catch (\Exception $e) {
            \Log::error('Failed to send booking confirmed webhook', [
                'booking_id' => $booking->id,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
        }

        return new PartnerBookingResource($booking);
    }

    /**
     * Cancel a booking.
     *
     * @param Request $request
     * @param Booking $booking
     * @return PartnerBookingResource
     */
    public function cancel(Request $request, Booking $booking): PartnerBookingResource
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('bookings:cancel')) {
            abort(403, 'Partner does not have permission to cancel bookings');
        }

        // Verify this booking was created by this partner
        if ($booking->partner_id !== $partner->id) {
            abort(404, 'Booking not found');
        }

        if (!$booking->canBeCancelled()) {
            throw ValidationException::withMessages([
                'booking' => ['This booking cannot be cancelled.'],
            ]);
        }

        DB::transaction(function () use ($booking, $partner) {
            $booking->cancel();

            // Restore availability
            if ($booking->availabilitySlot) {
                $booking->availabilitySlot->increment('available_quantity', $booking->quantity);
            }

            // Create refund transaction (reduces partner's balance owed)
            $this->financialService->createRefundForBooking($booking, $partner);
        });

        $booking->load(['listing', 'availabilitySlot', 'user', 'paymentIntents']);

        // Dispatch webhook asynchronously
        try {
            $this->webhookService->sendBookingCancelled($booking, $partner);
        } catch (\Exception $e) {
            \Log::error('Failed to send booking cancelled webhook', [
                'booking_id' => $booking->id,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
        }

        return new PartnerBookingResource($booking);
    }
}
