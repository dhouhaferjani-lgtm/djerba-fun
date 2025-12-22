<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'imageUrl' => $this->image_url,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'listingsCount' => $this->listings_count ?? $this->listings()->count(),
        ];
    }
}
