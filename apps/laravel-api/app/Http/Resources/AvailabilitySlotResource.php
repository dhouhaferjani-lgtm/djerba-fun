<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilitySlotResource extends JsonResource
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
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time->format('H:i:s'),
            'end_time' => $this->end_time->format('H:i:s'),
            'capacity' => $this->capacity,
            'remaining_capacity' => $this->remaining_capacity,
            'base_price' => (float) $this->base_price,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_bookable' => $this->isBookable(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
