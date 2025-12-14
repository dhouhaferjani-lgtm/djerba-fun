<?php

declare(strict_types=1);

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
        $startDateTime = $this->date->copy()->setTimeFrom($this->start_time);
        $endDateTime = $this->date->copy()->setTimeFrom($this->end_time);
        $listing = $this->whenLoaded('listing', fn () => $this->listing);

        return [
            'id' => $this->id,
            'listingId' => $this->listing_id,
            'date' => $this->date->toDateString(),
            'start' => $startDateTime->toIso8601String(),
            'end' => $endDateTime->toIso8601String(),
            'startTime' => $this->start_time->format('H:i:s'),
            'endTime' => $this->end_time->format('H:i:s'),
            'capacity' => $this->capacity,
            'remainingCapacity' => $this->remaining_capacity,
            'price' => (float) $this->base_price,
            'basePrice' => (float) $this->base_price,
            'currency' => $listing?->pricing['currency'] ?? 'EUR',
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'isBookable' => $this->isBookable(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
