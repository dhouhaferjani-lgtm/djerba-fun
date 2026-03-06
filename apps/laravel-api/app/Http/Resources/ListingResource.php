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
        $locale = app()->getLocale();

        return [
            'id' => $this->uuid,
            'vendorId' => $this->vendor?->uuid,
            'serviceType' => $this->service_type?->value,
            'activityType' => $this->when($this->isTourLike() && $this->activityType, [
                'id' => $this->activityType?->uuid,
                'name' => $this->getTranslationWithFallback($this->activityType?->getTranslations('name') ?? [], $locale),
                'slug' => $this->activityType?->slug,
                'icon' => $this->activityType?->icon,
                'color' => $this->activityType?->color,
            ]),
            'activityTypeId' => $this->when($this->isTourLike(), $this->activityType?->uuid),
            'status' => $this->status?->value,
            'title' => $this->getTranslationWithFallback($this->getTranslations('title'), $locale),
            'slug' => $this->slug,
            'summary' => $this->getTranslationWithFallback($this->getTranslations('summary'), $locale),
            'description' => $this->getTranslationWithFallback($this->getTranslations('description'), $locale),
            'highlights' => $this->getArrayWithFallback($this->highlights, $locale),
            'included' => $this->getArrayWithFallback($this->included, $locale),
            'notIncluded' => $this->getArrayWithFallback($this->not_included, $locale),
            'requirements' => $this->getArrayWithFallback($this->requirements, $locale),
            'locationId' => $this->location?->uuid,
            'location' => $this->location ? [
                'id' => $this->location->uuid,
                'name' => $this->getTranslationWithFallback($this->location->getTranslations('name'), $locale),
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
            'extras' => ListingExtraResource::collection($this->whenLoaded('listingExtras')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'safetyInfo' => $this->when($this->safety_info, is_array($this->safety_info) ? $this->toCamelCase($this->safety_info) : $this->safety_info),
            'accessibilityInfo' => $this->when($this->accessibility_info, is_array($this->accessibility_info) ? $this->toCamelCase($this->accessibility_info) : $this->accessibility_info),
            'difficultyDetails' => $this->when($this->isTourLike() && $this->difficulty_details, is_array($this->difficulty_details) ? $this->toCamelCase($this->difficulty_details) : $this->difficulty_details),
            'minGroupSize' => $this->min_group_size,
            'maxGroupSize' => $this->max_group_size,
            'minAdvanceBookingHours' => $this->min_advance_booking_hours,
            'rating' => $this->rating,
            'reviewsCount' => $this->reviews_count,
            'bookingsCount' => $this->bookings_count,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'publishedAt' => $this->published_at?->toIso8601String(),

            // Tour & Sejour shared fields
            'duration' => $this->when($this->isTourLike(), $this->duration),
            'difficulty' => $this->when($this->isTourLike() && $this->difficulty, $this->difficulty?->value),
            'distance' => $this->when($this->isTourLike(), $this->distance),
            'itinerary' => $this->when($this->isTourLike(), is_array($this->itinerary) ? $this->toCamelCase($this->itinerary) : $this->itinerary),
            'hasElevationProfile' => $this->when($this->isTourLike(), $this->has_elevation_profile),
            'elevationProfile' => $this->when(
                $this->isTourLike() && $this->has_elevation_profile && $this->elevation_profile,
                fn () => is_array($this->elevation_profile) ? $this->toCamelCase($this->elevation_profile) : $this->elevation_profile
            ),

            // Sejour-specific fields
            'numberOfDays' => $this->when($this->isSejour(), $this->number_of_days),
            'accommodationType' => $this->when($this->isSejour(), $this->accommodation_type),
            'mealsIncluded' => $this->when($this->isSejour(), $this->meals_included),

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

        // Use custom rules if provided, otherwise generate defaults based on type
        $rules = $policy['rules'] ?? $this->getDefaultCancellationRules($type);

        return $this->toCamelCase([
            'type' => $type,
            'rules' => $rules,
            'description' => $policy['description'] ?? null,
        ]);
    }

    /**
     * Get default cancellation rules based on policy type.
     * These match the descriptions shown in the vendor form.
     * Returns camelCase keys to match frontend expectations.
     */
    protected function getDefaultCancellationRules(string $type): array
    {
        return match ($type) {
            'flexible' => [
                // Full refund up to 24 hours before
                ['hoursBeforeStart' => 24, 'refundPercent' => 100],
                ['hoursBeforeStart' => 0, 'refundPercent' => 0],
            ],
            'moderate' => [
                // Full refund up to 5 days (120 hours) before
                ['hoursBeforeStart' => 120, 'refundPercent' => 100],
                ['hoursBeforeStart' => 24, 'refundPercent' => 50],
                ['hoursBeforeStart' => 0, 'refundPercent' => 0],
            ],
            'strict' => [
                // 50% refund up to 1 week (168 hours) before
                ['hoursBeforeStart' => 168, 'refundPercent' => 50],
                ['hoursBeforeStart' => 0, 'refundPercent' => 0],
            ],
            'non_refundable' => [
                // No refund at any time
                ['hoursBeforeStart' => 0, 'refundPercent' => 0],
            ],
            default => [],
        };
    }

    /**
     * Get translation with fallback to alternate language.
     * If the requested locale is empty, falls back to the other locale.
     *
     * @param  array<string, string>  $translations  Translation array {en: "...", fr: "..."}
     * @param  string  $locale  Requested locale (en or fr)
     * @return string|null The translated string with fallback
     */
    protected function getTranslationWithFallback(array $translations, string $locale): ?string
    {
        $alternateLocale = $locale === 'en' ? 'fr' : 'en';

        // Try requested locale first
        $value = $translations[$locale] ?? null;

        // Handle malformed nested arrays
        if (is_array($value)) {
            $value = $value[$locale] ?? reset($value) ?: null;
        }

        // If empty, try alternate locale
        if (empty($value)) {
            $value = $translations[$alternateLocale] ?? null;

            if (is_array($value)) {
                $value = $value[$alternateLocale] ?? reset($value) ?: null;
            }
        }

        return $value ?: null;
    }

    /**
     * Get array field (highlights, included, etc.) with fallback logic.
     * Each item has {en: "...", fr: "..."} structure.
     *
     * @param  array|null  $items  Array of items with en/fr keys
     * @param  string  $locale  Requested locale
     * @return array Processed array with fallback applied
     */
    protected function getArrayWithFallback(?array $items, string $locale): array
    {
        if (! is_array($items) || empty($items)) {
            return [];
        }

        $alternateLocale = $locale === 'en' ? 'fr' : 'en';

        return array_values(array_filter(array_map(function ($item) use ($locale, $alternateLocale) {
            if (! is_array($item)) {
                return $item;
            }

            // Get value for requested locale, fallback to alternate
            $value = $item[$locale] ?? null;

            if (empty($value)) {
                $value = $item[$alternateLocale] ?? null;
            }

            return $value ?: null;
        }, $items)));
    }
}
