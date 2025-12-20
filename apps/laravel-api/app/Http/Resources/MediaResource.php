<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'url' => $this->url,
            'thumbnailUrl' => $this->thumbnail_url,
            'alt' => $this->alt,
            'type' => $this->type,
            'category' => $this->category?->value ?? 'gallery',
            'order' => $this->order,
        ];
    }
}
