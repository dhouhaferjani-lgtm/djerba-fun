<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'booking_number' => $this->booking_number,
            'user_id' => $this->user_id,
            'listing_id' => $this->listing_id,
            'availability_slot_id' => $this->availability_slot_id,
            'quantity' => $this->quantity,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'traveler_info' => $this->traveler_info,
            'extras' => $this->extras,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'listing' => new ListingResource($this->whenLoaded('listing')),
            'availability_slot' => new AvailabilitySlotResource($this->whenLoaded('availabilitySlot')),
            'payment_intents' => PaymentIntentResource::collection($this->whenLoaded('paymentIntents')),
            'latest_payment_intent' => new PaymentIntentResource($this->whenLoaded('latestPaymentIntent')),

            // Computed properties
            'can_be_cancelled' => $this->canBeCancelled(),
            'is_confirmed' => $this->isConfirmed(),
            'is_cancelled' => $this->isCancelled(),
        ];
    }
}
