<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->getPreferredLanguage(['en', 'fr']) ?? 'fr';

        return [
            'id' => $this->id,
            'listing_id' => $this->listing?->uuid ?? $this->listing_id,
            'added_at' => $this->created_at?->toISOString(),
            'listing' => $this->when($this->relationLoaded('listing') && $this->listing, function () use ($locale, $request) {
                $listing = $this->listing;

                // Calculate display price from pricing JSON
                $pricing = is_array($listing->pricing) ? $listing->pricing : [];
                $detectedCurrency = $request->attributes->get('user_currency', 'EUR');
                $tndPrice = $pricing['tnd_price'] ?? $pricing['basePrice'] ?? $pricing['base_price'] ?? null;
                $eurPrice = $pricing['eur_price'] ?? $pricing['basePrice'] ?? $pricing['base_price'] ?? null;

                // Extract from first person type if no top-level prices
                if ($tndPrice === null) {
                    $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];
                    if (! empty($personTypes)) {
                        $firstType = reset($personTypes);
                        $tndPrice = $firstType['tnd_price'] ?? $firstType['tndPrice'] ?? $firstType['price'] ?? 0;
                        $eurPrice = $firstType['eur_price'] ?? $firstType['eurPrice'] ?? $firstType['price'] ?? 0;
                    }
                }

                $displayPrice = $detectedCurrency === 'TND' ? ($tndPrice ?? 0) : ($eurPrice ?? 0);

                return [
                    'id' => $listing->uuid,
                    'slug' => $listing->slug,
                    'title' => $listing->getTranslation('title', $locale),
                    'summary' => $listing->getTranslation('summary', $locale),
                    'service_type' => $listing->service_type?->value,
                    'hero_image' => $listing->hero_image,
                    'location' => $listing->relationLoaded('location') && $listing->location ? [
                        'id' => $listing->location->id,
                        'name' => $listing->location->getTranslation('name', $locale),
                        'slug' => $listing->location->slug,
                    ] : null,
                    'activity_type' => $listing->relationLoaded('activityType') && $listing->activityType ? [
                        'id' => $listing->activityType->id,
                        'name' => $listing->activityType->getTranslation('name', $locale),
                        'slug' => $listing->activityType->slug,
                    ] : null,
                    'pricing' => [
                        'display_price' => $displayPrice,
                        'display_currency' => $detectedCurrency,
                    ],
                    'duration' => $listing->duration,
                    'rating_average' => $listing->rating_average,
                    'review_count' => $listing->review_count,
                ];
            }),
        ];
    }
}
