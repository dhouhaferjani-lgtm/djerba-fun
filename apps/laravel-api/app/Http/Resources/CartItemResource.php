<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartItemResource extends BaseResource
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
            'cartId' => $this->cart_id,
            'holdId' => $this->hold_id,
            'listingId' => $this->listing_id,
            'listingTitle' => $this->listing_title,
            'slotStart' => $this->slot_start?->toIso8601String(),
            'slotEnd' => $this->slot_end?->toIso8601String(),
            'quantity' => $this->quantity,
            'personTypeBreakdown' => $this->person_type_breakdown,
            'unitPrice' => (float) $this->unit_price,
            'currency' => $this->currency,
            'primaryContact' => $this->primary_contact,
            'guestNames' => $this->guest_names,
            'extras' => $this->extras,
            'subtotal' => $this->getSubtotal(),
            'extrasTotal' => $this->getExtrasTotal(),
            'total' => $this->getTotal(),
            'holdValid' => $this->isHoldValid(),
            'requiresTravelerNames' => $this->requiresTravelerNames(),
            'hold' => new BookingHoldResource($this->whenLoaded('hold')),
            'listing' => new ListingResource($this->whenLoaded('listing')),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
