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
            'discountType' => $this->discount_type->value,
            'discountValue' => (float) $this->discount_value,
            'minimumOrder' => $this->minimum_order ? (float) $this->minimum_order : null,
            'maximumDiscount' => $this->maximum_discount ? (float) $this->maximum_discount : null,
            'validFrom' => $this->valid_from->toIso8601String(),
            'validUntil' => $this->valid_until->toIso8601String(),
            'usageLimit' => $this->usage_limit,
            'usageCount' => $this->usage_count,
            'isActive' => $this->is_active,
        ];
    }
}
