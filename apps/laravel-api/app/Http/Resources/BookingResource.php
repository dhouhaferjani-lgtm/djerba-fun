<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
class BookingResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bookingNumber' => $this->booking_number,
            'userId' => $this->user_id,
            'listingId' => $this->listing_id,
            'availabilitySlotId' => $this->availability_slot_id,
            'quantity' => $this->quantity,
            'totalAmount' => (float) $this->total_amount,
            'discountAmount' => (float) $this->discount_amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'travelerInfo' => is_array($this->traveler_info) ? $this->toCamelCase($this->traveler_info) : $this->traveler_info,
            'extras' => is_array($this->extras) ? $this->toCamelCase($this->extras) : $this->extras,
            'confirmedAt' => $this->confirmed_at?->toIso8601String(),
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'cancellationReason' => $this->cancellation_reason,
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'listing' => new ListingResource($this->whenLoaded('listing')),
            'availabilitySlot' => new AvailabilitySlotResource($this->whenLoaded('availabilitySlot')),
            'paymentIntents' => PaymentIntentResource::collection($this->whenLoaded('paymentIntents')),
            'latestPaymentIntent' => new PaymentIntentResource($this->whenLoaded('latestPaymentIntent')),

            // Computed properties
            'canBeCancelled' => $this->canBeCancelled(),
            'isConfirmed' => $this->isConfirmed(),
            'isCancelled' => $this->isCancelled(),

            // Convenience aliases for frontend compatibility
            'code' => $this->booking_number,
            'guests' => $this->quantity,
            'startsAt' => $this->whenLoaded('availabilitySlot', fn () => $this->availabilitySlot?->date?->copy()->setTimeFrom($this->availabilitySlot->start_time)->toIso8601String()),
        ];
    }
}
