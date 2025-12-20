<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Extra
 */
class ExtraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'en');

        return [
            'id' => $this->id,
            'vendorId' => $this->vendor_id,
            'name' => $this->name,
            'displayName' => $this->getTranslation('name', $locale),
            'description' => $this->description,
            'displayDescription' => $this->getTranslation('description', $locale),
            'shortDescription' => $this->short_description,
            'displayShortDescription' => $this->getTranslation('short_description', $locale),
            'imageUrl' => $this->image_url,
            'thumbnailUrl' => $this->thumbnail_url,
            'pricingType' => $this->pricing_type->value,
            'pricingTypeLabel' => $this->pricing_type->label(),
            'basePriceTnd' => (float) $this->base_price_tnd,
            'basePriceEur' => (float) $this->base_price_eur,
            'personTypePrices' => $this->person_type_prices,
            'minQuantity' => $this->min_quantity,
            'maxQuantity' => $this->max_quantity,
            'defaultQuantity' => $this->default_quantity,
            'trackInventory' => $this->track_inventory,
            'inventoryCount' => $this->inventory_count,
            'isRequired' => $this->is_required,
            'autoAdd' => $this->auto_add,
            'allowQuantityChange' => $this->allow_quantity_change,
            'displayOrder' => $this->display_order,
            'category' => $this->category?->value,
            'categoryLabel' => $this->category?->label(),
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
