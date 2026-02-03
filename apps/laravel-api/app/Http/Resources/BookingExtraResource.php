<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for booking extras (selected extras with price snapshot).
 *
 * @mixin \App\Models\BookingExtra
 */
class BookingExtraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'en');
        // Use booking's stored currency first, then middleware-detected currency
        $currency = $this->booking?->currency ?? $request->attributes->get('user_currency', 'EUR');

        return [
            'id' => $this->id,
            'bookingId' => $this->booking_id,
            'extraId' => $this->extra_id,
            'listingExtraId' => $this->listing_extra_id,
            'quantity' => $this->quantity,
            'pricingType' => $this->pricing_type->value,

            // Prices in both currencies
            'unitPriceTnd' => (float) $this->unit_price_tnd,
            'unitPriceEur' => (float) $this->unit_price_eur,
            'subtotalTnd' => (float) $this->subtotal_tnd,
            'subtotalEur' => (float) $this->subtotal_eur,

            // Display values in requested currency
            'unitPrice' => $this->getUnitPrice($currency),
            'subtotal' => $this->getSubtotal($currency),
            'displayCurrency' => $currency,

            // Per-person-type breakdown
            'personTypeBreakdown' => $this->person_type_breakdown,

            // Snapshot data
            'extraName' => $this->extra_name,
            'name' => $this->getDisplayName($locale),
            'extraCategory' => $this->extra_category,

            // Status
            'status' => $this->status->value,
            'isActive' => $this->isActive(),
            'inventoryReserved' => $this->inventory_reserved,

            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
