<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
class BookingHoldResource extends BaseResource
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
            'listingId' => $this->listing_id,
            'slotId' => $this->slot_id,
            'sessionId' => $this->session_id,
            'quantity' => $this->quantity,
            'personTypeBreakdown' => is_array($this->person_type_breakdown) ? $this->toCamelCase($this->person_type_breakdown) : $this->person_type_breakdown,
            'expiresAt' => $this->expires_at->toIso8601String(),
            'expiresInSeconds' => max(0, $this->expires_at->diffInSeconds(now())),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'isActive' => $this->isActive(),
            'slot' => new AvailabilitySlotResource($this->whenLoaded('slot')),
            'listing' => new ListingResource($this->whenLoaded('listing')),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
