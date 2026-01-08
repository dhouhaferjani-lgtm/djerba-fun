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

        $currency = $request->attributes->get('user_currency', 'EUR');

        return [
            'id' => $this->id,
            'listingId' => $this->listing_id,
            'date' => $this->date->toDateString(),
            'start' => $startDateTime->toIso8601String(),
            'end' => $endDateTime->toIso8601String(),
            'startTime' => $this->start_time->format('H:i:s'),
            'endTime' => $this->end_time->format('H:i:s'),
            'capacity' => $this->capacity,
            'remainingCapacity' => $this->remainingCapacity, // Uses computed accessor
            'tndPrice' => (float) ($listing?->pricing['tnd_price'] ?? $this->base_price ?? 0),
            'eurPrice' => (float) ($listing?->pricing['eur_price'] ?? $this->base_price ?? 0),
            'displayCurrency' => $currency,
            'currency' => $currency, // Legacy field for frontend compatibility
            'displayPrice' => $this->getDisplayPrice($request, $listing),
            'basePrice' => (int) ($this->getDisplayPrice($request, $listing)), // Legacy field
            'status' => $this->status?->value,
            'statusLabel' => $this->status?->label(),
            'isBookable' => $this->status ? $this->isBookable() : null,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get the display price based on detected currency.
     */
    protected function getDisplayPrice(Request $request, $listing): float
    {
        $currency = $request->attributes->get('user_currency', 'EUR');

        if ($currency === 'TND') {
            return (float) ($listing?->pricing['tnd_price'] ?? $this->base_price ?? 0);
        }

        return (float) ($listing?->pricing['eur_price'] ?? $this->base_price ?? 0);
    }
}
