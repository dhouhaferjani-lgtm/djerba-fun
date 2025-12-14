<?php

declare(strict_types=1);

namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'serviceType' => $this->service_type,
            'status' => $this->status,

            // Location data
            'location' => [
                'id' => $this->location?->id,
                'name' => $this->location?->name,
                'city' => $this->location?->city,
                'region' => $this->location?->region,
                'country' => $this->location?->country,
                'latitude' => $this->location?->latitude,
                'longitude' => $this->location?->longitude,
                'address' => $this->location?->address,
            ],

            // Pricing
            'pricing' => [
                'base' => $this->pricing['base'] ?? null,
                'currency' => $this->pricing['currency'] ?? 'EUR',
                'perPerson' => $this->pricing['per_person'] ?? false,
                'minGroupSize' => $this->pricing['min_group_size'] ?? 1,
            ],

            // Capacity
            'capacity' => [
                'min' => $this->capacity['min'] ?? 1,
                'max' => $this->capacity['max'] ?? null,
            ],

            // Details
            'duration' => $this->duration,
            'difficulty' => $this->difficulty,
            'languages' => $this->languages ?? [],
            'tags' => $this->tags ?? [],
            'included' => $this->included ?? [],
            'requirements' => $this->requirements ?? [],

            // Ratings & Stats
            'rating' => $this->rating,
            'reviewsCount' => $this->reviews_count ?? 0,
            'bookingsCount' => $this->bookings_count ?? 0,

            // Vendor info (simplified for agents)
            'vendor' => [
                'id' => $this->vendor?->id,
                'name' => $this->vendor?->first_name . ' ' . $this->vendor?->last_name,
                'businessName' => $this->vendor?->vendorProfile?->business_name,
                'rating' => $this->vendor?->vendorProfile?->rating,
            ],

            // Media
            'images' => $this->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->url,
                'altText' => $media->alt_text,
                'isPrimary' => $media->pivot->is_primary ?? false,
            ])->toArray(),

            // Service-specific data
            'serviceData' => $this->service_data,

            // Timestamps
            'publishedAt' => $this->published_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),

            // Public URL
            'url' => config('app.frontend_url') . '/listings/' . $this->slug,
        ];
    }
}
