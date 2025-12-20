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
            'vendorId' => $this->vendor->uuid,
            'serviceType' => $this->service_type->value,
            'status' => $this->status->value,
            'title' => $this->getTranslations('title'),
            'slug' => $this->slug,
            'summary' => $this->getTranslations('summary'),
            'description' => $this->getTranslations('description'),
            'highlights' => is_array($this->highlights) ? $this->toCamelCase($this->highlights) : $this->highlights,
            'included' => is_array($this->included) ? $this->toCamelCase($this->included) : $this->included,
            'notIncluded' => is_array($this->not_included) ? $this->toCamelCase($this->not_included) : $this->not_included,
            'requirements' => is_array($this->requirements) ? $this->toCamelCase($this->requirements) : $this->requirements,
            'locationId' => $this->location->uuid,
            'meetingPoint' => is_array($this->meeting_point) ? $this->toCamelCase($this->meeting_point) : $this->meeting_point,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'pricing' => $this->formatPricing($request),
            'cancellationPolicy' => is_array($this->cancellation_policy) ? $this->toCamelCase($this->cancellation_policy) : $this->cancellation_policy,
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
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
            'publishedAt' => $this->published_at?->toIso8601String(),

            // Tour-specific fields
            'duration' => $this->when($this->isTour(), $this->duration),
            'difficulty' => $this->when($this->isTour() && $this->difficulty, $this->difficulty?->value),
            'distance' => $this->when($this->isTour(), $this->distance),
            'itinerary' => $this->when($this->isTour(), is_array($this->itinerary) ? $this->toCamelCase($this->itinerary) : $this->itinerary),
            'hasElevationProfile' => $this->when($this->isTour(), $this->has_elevation_profile),

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

        // Get prices for both currencies
        $tndPrice = $pricing['tnd_price'] ?? $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? null;
        $eurPrice = $pricing['eur_price'] ?? $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? null;

        // Determine display price based on detected currency
        $displayPrice = $detectedCurrency === 'TND' ? $tndPrice : $eurPrice;

        // Format person types with dual pricing
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];
        if (!empty($personTypes)) {
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
}
