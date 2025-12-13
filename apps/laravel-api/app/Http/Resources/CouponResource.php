<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type->value,
            'discount_value' => (float) $this->discount_value,
            'minimum_order' => $this->minimum_order ? (float) $this->minimum_order : null,
            'maximum_discount' => $this->maximum_discount ? (float) $this->maximum_discount : null,
            'valid_from' => $this->valid_from->toIso8601String(),
            'valid_until' => $this->valid_until->toIso8601String(),
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'is_active' => $this->is_active,
        ];
    }
}
