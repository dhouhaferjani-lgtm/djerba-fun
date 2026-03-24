<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = substr($request->header('Accept-Language', 'fr'), 0, 2);
        if (! in_array($locale, ['en', 'fr'])) {
            $locale = 'fr';
        }

        // Get locale-specific text with fallback to French
        $text = $this->getTranslation('text', $locale);
        if (empty($text)) {
            $text = $this->getTranslation('text', 'fr') ?? '';
        }

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'photo' => $this->getPhotoUrl(),
            'text' => $text,
            'textFr' => $this->getTranslation('text', 'fr') ?? '',
            'textEn' => $this->getTranslation('text', 'en') ?? '',
            'rating' => $this->rating,
            'location' => $this->location,
            'activity' => $this->activity,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get the full photo URL.
     */
    protected function getPhotoUrl(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        // Return as-is if already a full URL
        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }
}
