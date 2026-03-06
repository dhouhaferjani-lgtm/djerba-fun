<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TagResource extends BaseResource
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
            'type' => $this->type?->value,
            'name' => $this->getTranslations('name'),
            'slug' => $this->slug,
            'description' => $this->getTranslations('description'),
            'icon' => $this->icon,
            'color' => $this->color,
            'displayOrder' => $this->display_order,
            'listingsCount' => $this->listings_count,
            'applicableServiceTypes' => $this->applicable_service_types ?? [],
        ];
    }
}
