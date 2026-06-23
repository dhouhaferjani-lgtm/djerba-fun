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

        // Per-slot price override + lenient merge for both currencies. The frontend
        // renders effectivePrices directly so it never has to recompute the per-key
        // fallback rule. priceOverrides exposes the raw stored override so the UI
        // can flag the slot as custom-priced.
        $listingPersonTypes = is_array($listing?->pricing['person_types'] ?? null)
            ? $listing->pricing['person_types']
            : [];

        $effectivePrices = [
            'TND' => $this->resource->getEffectivePersonTypePrices('TND', $listingPersonTypes),
            'EUR' => $this->resource->getEffectivePersonTypePrices('EUR', $listingPersonTypes),
        ];

        // Show-duration is a per-rule display preference. When true, the slot
        // picker renders the duration label on the chip. Falls back to false
        // if the rule relation isn't loaded — that means existing rules with
        // no relation eagerly loaded behave as if the toggle is off (which is
        // the correct regression-safe default).
        $rule = $this->relationLoaded('availabilityRule') ? $this->availabilityRule : null;
        $showDuration = (bool) ($rule?->show_duration ?? false);

        // durationMinutes is always exposed (independent of the toggle) — it's
        // a cheap computation and other touchpoints might want it later.
        $durationMinutes = $this->start_time && $this->end_time
            ? (int) round(abs($this->end_time->diffInMinutes($this->start_time)))
            : 0;

        return [
            'id' => $this->id,
            'listingId' => $this->listing_id,
            'date' => $this->date->toDateString(),
            'start' => $startDateTime->toIso8601String(),
            'end' => $endDateTime->toIso8601String(),
            'startTime' => $this->start_time->format('H:i:s'),
            'endTime' => $this->end_time->format('H:i:s'),
            'durationMinutes' => $durationMinutes,
            'capacity' => $this->capacity,
            'remainingCapacity' => $this->remainingCapacity, // Uses computed accessor
            'tndPrice' => $this->tieredFromPrice($listing, 'TND') ?? (float) ($listing?->pricing['tnd_price'] ?? 0),
            'eurPrice' => $this->tieredFromPrice($listing, 'EUR') ?? (float) ($listing?->pricing['eur_price'] ?? 0),
            'displayCurrency' => $currency,
            'currency' => $currency, // Legacy field for frontend compatibility
            'displayPrice' => $this->getDisplayPrice($request, $listing, $effectivePrices),
            'basePrice' => (int) $this->getDisplayPrice($request, $listing, $effectivePrices), // Legacy field
            'priceOverrides' => $this->price_overrides, // raw override JSON (or null)
            'effectivePrices' => $effectivePrices,      // merged per-currency, per-person-type
            'showDuration' => $showDuration,
            'status' => $this->status?->value,
            'statusLabel' => $this->status?->label(),
            'isBookable' => $this->status ? $this->isBookable() : null,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Headline display price for the slot in the request's detected currency.
     *
     * Preference order:
     *   1. Slot's effective price for the FIRST listed person-type (typically
     *      "adult") — this is what the UI shows next to the time chip.
     *   2. Listing's top-level dual-currency price (legacy single-tier listings).
     *
     * The slot-effective lookup means a 1-hour slot at 50 TND and a 3-hour slot
     * at 120 TND render their own headline prices side-by-side in the picker.
     */
    protected function getDisplayPrice(Request $request, $listing, array $effectivePrices): float
    {
        $currency = $request->attributes->get('user_currency', 'EUR');

        // Tiered listings have no per-person-type slot prices; the headline is
        // the single-traveller cumulative total T[1].
        $tieredFrom = $this->tieredFromPrice($listing, $currency);

        if ($tieredFrom !== null) {
            return $tieredFrom;
        }

        $effectiveForCurrency = $effectivePrices[$currency] ?? [];

        if (! empty($effectiveForCurrency)) {
            $firstKey = array_key_first($effectiveForCurrency);

            if ($firstKey !== null) {
                return (float) $effectiveForCurrency[$firstKey];
            }
        }

        if ($currency === 'TND') {
            return (float) ($listing?->pricing['tnd_price'] ?? 0);
        }

        return (float) ($listing?->pricing['eur_price'] ?? 0);
    }

    /**
     * "From" price (cumulative tier T[1]) for a tiered listing, or null when
     * the listing is not tiered. Keeps flat listings on their existing path.
     */
    private function tieredFromPrice($listing, string $currency): ?float
    {
        // Guard against MissingValue (relation not loaded) — never touch
        // ->pricing on anything but a real, loaded Listing.
        if (! $listing instanceof \App\Models\Listing) {
            return null;
        }

        $pricing = is_array($listing->pricing) ? $listing->pricing : [];
        $strategy = $pricing['pricing_strategy'] ?? $pricing['pricingStrategy'] ?? 'flat';

        if ($strategy !== 'tiered') {
            return null;
        }

        $tiers = $pricing['tiers'] ?? [];

        if (! is_array($tiers) || $tiers === []) {
            return null;
        }

        $rows = array_values(array_filter($tiers, 'is_array'));
        usort($rows, fn ($a, $b) => ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0)));
        $first = $rows[0] ?? null;

        if ($first === null) {
            return null;
        }

        return strtoupper($currency) === 'TND'
            ? (float) ($first['tnd_total'] ?? $first['tndTotal'] ?? 0)
            : (float) ($first['eur_total'] ?? $first['eurTotal'] ?? 0);
    }
}
