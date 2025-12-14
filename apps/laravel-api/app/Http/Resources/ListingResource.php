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
            'highlights' => $this->highlights,
            'included' => $this->included,
            'notIncluded' => $this->not_included,
            'requirements' => $this->requirements,
            'locationId' => $this->location->uuid,
            'meetingPoint' => is_array($this->meeting_point) ? $this->toCamelCase($this->meeting_point) : $this->meeting_point,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'pricing' => is_array($this->pricing) ? $this->toCamelCase($this->pricing) : $this->pricing,
            'cancellationPolicy' => is_array($this->cancellation_policy) ? $this->toCamelCase($this->cancellation_policy) : $this->cancellation_policy,
            'minGroupSize' => $this->min_group_size,
            'maxGroupSize' => $this->max_group_size,
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
}
