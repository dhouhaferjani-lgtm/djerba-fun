<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Agent\AgentBookingResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\AvailabilitySlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentBookingController extends Controller
{
    /**
     * Create a booking on behalf of a traveler.
     *
     * @param Request $request
     * @return AgentBookingResource
     */
    public function store(Request $request): AgentBookingResource
    {
        $agent = $request->attributes->get('agent');

        // Check permission
        if (!$agent->hasPermission('bookings:create')) {
            abort(403, 'Agent does not have permission to create bookings');
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
            'agent_reference' => 'nullable|string|max:255',
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
        $booking = DB::transaction(function () use ($listing, $slot, $validated, $user, $agent, $request) {
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
                'agent_metadata' => [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'agent_reference' => $validated['agent_reference'] ?? null,
                    'ip_address' => $request->ip(),
                ],
            ]);

            // Decrease available quantity
            $slot->decrement('available_quantity', $validated['quantity']);

            return $booking;
        });

        $booking->load(['listing', 'availabilitySlot', 'user']);

        return new AgentBookingResource($booking);
    }

    /**
     * Get booking details.
     *
     * @param Request $request
     * @param Booking $booking
     * @return AgentBookingResource
     */
    public function show(Request $request, Booking $booking): AgentBookingResource
    {
        $agent = $request->attributes->get('agent');

        // Check permission
        if (!$agent->hasPermission('bookings:read')) {
            abort(403, 'Agent does not have permission to read bookings');
        }

        // Verify this booking was created by this agent
        if (!isset($booking->agent_metadata['agent_id']) || $booking->agent_metadata['agent_id'] !== $agent->id) {
            abort(404, 'Booking not found');
        }

        $booking->load(['listing', 'availabilitySlot', 'user', 'paymentIntents']);

        return new AgentBookingResource($booking);
    }

    /**
     * Cancel a booking.
     *
     * @param Request $request
     * @param Booking $booking
     * @return AgentBookingResource
     */
    public function cancel(Request $request, Booking $booking): AgentBookingResource
    {
        $agent = $request->attributes->get('agent');

        // Check permission
        if (!$agent->hasPermission('bookings:cancel')) {
            abort(403, 'Agent does not have permission to cancel bookings');
        }

        // Verify this booking was created by this agent
        if (!isset($booking->agent_metadata['agent_id']) || $booking->agent_metadata['agent_id'] !== $agent->id) {
            abort(404, 'Booking not found');
        }

        if (!$booking->canBeCancelled()) {
            throw ValidationException::withMessages([
                'booking' => ['This booking cannot be cancelled.'],
            ]);
        }

        DB::transaction(function () use ($booking) {
            $booking->cancel();

            // Restore availability
            if ($booking->availabilitySlot) {
                $booking->availabilitySlot->increment('available_quantity', $booking->quantity);
            }
        });

        $booking->load(['listing', 'availabilitySlot', 'user', 'paymentIntents']);

        return new AgentBookingResource($booking);
    }
}
