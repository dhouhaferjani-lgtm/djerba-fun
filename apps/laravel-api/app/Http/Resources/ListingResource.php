<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ListingResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'vendorId' => $this->vendor?->uuid,
            'serviceType' => $this->service_type?->value,
            'activityType' => $this->when($this->isTour() && $this->activityType, [
                'id' => $this->activityType?->uuid,
                'name' => $this->activityType?->getTranslations('name'),
                'slug' => $this->activityType?->slug,
                'icon' => $this->activityType?->icon,
                'color' => $this->activityType?->color,
            ]),
            'activityTypeId' => $this->when($this->isTour(), $this->activityType?->uuid),
            'status' => $this->status?->value,
            'title' => $this->getTranslations('title'),
            'slug' => $this->slug,
            'summary' => $this->getTranslations('summary'),
            'description' => $this->getTranslations('description'),
            'highlights' => is_array($this->highlights) ? $this->toCamelCase($this->highlights) : $this->highlights,
            'included' => is_array($this->included) ? $this->toCamelCase($this->included) : $this->included,
            'notIncluded' => is_array($this->not_included) ? $this->toCamelCase($this->not_included) : $this->not_included,
            'requirements' => is_array($this->requirements) ? $this->toCamelCase($this->requirements) : $this->requirements,
            'locationId' => $this->location?->uuid,
            'location' => $this->location ? [
                'id' => $this->location->uuid,
                'name' => $this->location->name,
                'latitude' => $this->location->latitude,
                'longitude' => $this->location->longitude,
            ] : null,
            'meetingPoint' => is_array($this->meeting_point) ? $this->toCamelCase($this->meeting_point) : $this->meeting_point,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'galleryImages' => collect($this->gallery_images ?? [])
                ->filter(fn ($img) => is_string($img) && ! empty($img))
                ->values()
                ->all(),
            'galleryLayout' => $this->gallery_layout ?? 'bento-1-4',
            'pricing' => $this->formatPricing($request),
            'cancellationPolicy' => $this->formatCancellationPolicy(),
            'faqs' => ListingFaqResource::collection($this->whenLoaded('faqs')),
            'safetyInfo' => $this->when($this->safety_info, is_array($this->safety_info) ? $this->toCamelCase($this->safety_info) : $this->safety_info),
            'accessibilityInfo' => $this->when($this->accessibility_info, is_array($this->accessibility_info) ? $this->toCamelCase($this->accessibility_info) : $this->accessibility_info),
            'difficultyDetails' => $this->when($this->isTour() && $this->difficulty_details, is_array($this->difficulty_details) ? $this->toCamelCase($this->difficulty_details) : $this->difficulty_details),
            'minGroupSize' => $this->min_group_size,
            'maxGroupSize' => $this->max_group_size,
            'minAdvanceBookingHours' => $this->min_advance_booking_hours,
            'rating' => $this->rating,
            'reviewsCount' => $this->reviews_count,
            'bookingsCount' => $this->bookings_count,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'publishedAt' => $this->published_at?->toIso8601String(),

            // Tour-specific fields
            'duration' => $this->when($this->isTour(), $this->duration),
            'difficulty' => $this->when($this->isTour() && $this->difficulty, $this->difficulty?->value),
            'distance' => $this->when($this->isTour(), $this->distance),
            'itinerary' => $this->when($this->isTour(), is_array($this->itinerary) ? $this->toCamelCase($this->itinerary) : $this->itinerary),
            'hasElevationProfile' => $this->when($this->isTour(), $this->has_elevation_profile),
            'elevationProfile' => $this->when(
                $this->isTour() && $this->has_elevation_profile && $this->elevation_profile,
                fn () => is_array($this->elevation_profile) ? $this->toCamelCase($this->elevation_profile) : $this->elevation_profile
            ),

            // Event-specific fields
            'eventType' => $this->when($this->isEvent(), $this->event_type),
            'startDate' => $this->when($this->isEvent(), $this->start_date?->toIso8601String()),
            'endDate' => $this->when($this->isEvent(), $this->end_date?->toIso8601String()),
            'venue' => $this->when($this->isEvent(), is_array($this->venue) ? $this->toCamelCase($this->venue) : $this->venue),
            'agenda' => $this->when($this->isEvent(), is_array($this->agenda) ? $this->toCamelCase($this->agenda) : $this->agenda),
        ];
    }

    /**
     * Format pricing with dual currency support and user detection.
     */
    protected function formatPricing(Request $request): array
    {
        $pricing = is_array($this->pricing) ? $this->pricing : [];

        // Get detected currency from middleware (or default to EUR)
        $detectedCurrency = $request->attributes->get('user_currency', 'EUR');

        // Format person types with dual pricing (do this first so we can extract prices)
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];

        // Get prices for both currencies
        $tndPrice = $pricing['tnd_price'] ?? $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? null;
        $eurPrice = $pricing['eur_price'] ?? $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? null;

        // Extract from first person type (usually "adult") if no top-level prices exist
        if ($tndPrice === null && ! empty($personTypes)) {
            $firstType = reset($personTypes);
            $tndPrice = $firstType['tnd_price'] ?? $firstType['tndPrice'] ?? $firstType['price'] ?? null;
            $eurPrice = $firstType['eur_price'] ?? $firstType['eurPrice'] ?? $firstType['price'] ?? null;
        }

        // Determine display price based on detected currency
        $displayPrice = $detectedCurrency === 'TND' ? $tndPrice : $eurPrice;

        if (! empty($personTypes)) {
            $personTypes = array_map(function ($type) use ($detectedCurrency) {
                $tndTypePrice = $type['tnd_price'] ?? $type['price'] ?? 0;
                $eurTypePrice = $type['eur_price'] ?? $type['price'] ?? 0;

                return $this->toCamelCase([
                    'key' => $type['key'],
                    'label' => $type['label'],
                    'tnd_price' => $tndTypePrice,
                    'eur_price' => $eurTypePrice,
                    'display_price' => $detectedCurrency === 'TND' ? $tndTypePrice : $eurTypePrice,
                    'min_age' => $type['minAge'] ?? $type['min_age'] ?? null,
                    'max_age' => $type['maxAge'] ?? $type['max_age'] ?? null,
                    'min_quantity' => $type['minQuantity'] ?? $type['min_quantity'] ?? 0,
                    'max_quantity' => $type['maxQuantity'] ?? $type['max_quantity'] ?? null,
                ]);
            }, $personTypes);
        }

        // Format group discount
        $groupDiscount = $pricing['groupDiscount'] ?? $pricing['group_discount'] ?? null;

        if ($groupDiscount) {
            $groupDiscount = $this->toCamelCase($groupDiscount);
        }

        return $this->toCamelCase([
            'tnd_price' => $tndPrice,
            'eur_price' => $eurPrice,
            'display_currency' => $detectedCurrency,
            'display_price' => $displayPrice,
            'person_types' => $personTypes,
            'group_discount' => $groupDiscount,
        ]);
    }

    /**
     * Format cancellation policy with sensible defaults.
     */
    protected function formatCancellationPolicy(): ?array
    {
        $policy = is_array($this->cancellation_policy) ? $this->cancellation_policy : [];

        // If policy is empty or doesn't have a valid type, return null
        // This allows frontend to gracefully handle missing policy
        if (empty($policy)) {
            return null;
        }

        // Get type with validation
        $type = $policy['type'] ?? null;
        $validTypes = ['flexible', 'moderate', 'strict', 'non_refundable'];

        // If type is not valid, don't return a policy
        // This prevents "type.null" translation errors on frontend
        if (! $type || ! in_array($type, $validTypes)) {
            return null;
        }

        return $this->toCamelCase([
            'type' => $type,
            'rules' => $policy['rules'] ?? [],
            'description' => $policy['description'] ?? null,
        ]);
    }
}
