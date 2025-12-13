<?php

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
            'service_type' => $this->service_type,
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
                'per_person' => $this->pricing['per_person'] ?? false,
                'min_group_size' => $this->pricing['min_group_size'] ?? 1,
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
            'reviews_count' => $this->reviews_count ?? 0,
            'bookings_count' => $this->bookings_count ?? 0,

            // Vendor info (simplified for agents)
            'vendor' => [
                'id' => $this->vendor?->id,
                'name' => $this->vendor?->first_name . ' ' . $this->vendor?->last_name,
                'business_name' => $this->vendor?->vendorProfile?->business_name,
                'rating' => $this->vendor?->vendorProfile?->rating,
            ],

            // Media
            'images' => $this->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->url,
                'alt_text' => $media->alt_text,
                'is_primary' => $media->pivot->is_primary ?? false,
            ])->toArray(),

            // Service-specific data
            'service_data' => $this->service_data,

            // Timestamps
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Public URL
            'url' => config('app.frontend_url') . '/listings/' . $this->slug,
        ];
    }
}
