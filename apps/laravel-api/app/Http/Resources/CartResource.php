<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\PriceCalculationService;
use Illuminate\Http\Request;

class CartResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = $this->getCurrency();
        $subtotal = $this->getSubtotal();

        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'sessionId' => $this->session_id,
            'status' => $this->status,
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'expiresInSeconds' => $this->expires_at
                ? max(0, $this->expires_at->getTimestamp() - now()->getTimestamp())
                : 0,
            'isExpired' => $this->hasExpired(),
            'isActive' => $this->isActive(),
            'isEmpty' => $this->isEmpty(),
            'itemCount' => $this->getItemCount(),
            'totalGuests' => $this->getTotalGuests(),
            'subtotal' => $subtotal,
            'currency' => $currency,
            'tndSubtotal' => $currency === 'TND' ? $subtotal : $this->getTndEquivalent(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Calculate TND equivalent total for EUR carts (for ClikToPay currency notice).
     */
    private function getTndEquivalent(): float
    {
        $tndTotal = 0;
        $priceService = app(PriceCalculationService::class);

        foreach ($this->items as $item) {
            $listing = $item->listing;
            if (! $listing) {
                continue;
            }

            if (! empty($item->person_type_breakdown)) {
                $result = $priceService->calculateTotal($listing, $item->person_type_breakdown, 'TND');
                $tndTotal += $result['total'];
            } else {
                $result = $priceService->calculateTotal($listing, ['adult' => $item->quantity], 'TND');
                $tndTotal += $result['total'];
            }
        }

        return round($tndTotal, 2);
    }
}
