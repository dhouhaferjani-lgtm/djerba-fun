<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingHoldResource extends JsonResource
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
            'listing_id' => $this->listing_id,
            'slot_id' => $this->slot_id,
            'quantity' => $this->quantity,
            'expires_at' => $this->expires_at->toIso8601String(),
            'expires_in_seconds' => max(0, $this->expires_at->diffInSeconds(now())),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->isActive(),
            'slot' => new AvailabilitySlotResource($this->whenLoaded('slot')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
