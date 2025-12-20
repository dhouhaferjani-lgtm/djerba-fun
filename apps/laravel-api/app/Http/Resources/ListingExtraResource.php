<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for listing extras in booking flow.
 * Returns effective values with overrides applied.
 *
 * @mixin \App\Models\ListingExtra
 */
class ListingExtraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'en');
        $currency = $request->header('X-Currency', 'EUR');
        $extra = $this->extra;

        return [
            'id' => $this->id,
            'extraId' => $extra->id,
            'name' => $extra->getTranslation('name', $locale),
            'description' => $extra->getTranslation('description', $locale),
            'shortDescription' => $extra->getTranslation('short_description', $locale),
            'imageUrl' => $extra->image_url,
            'pricingType' => $extra->pricing_type->value,
            'category' => $extra->category?->value,
            'categoryLabel' => $extra->category?->label(),

            // Effective values (with overrides applied)
            'priceTnd' => $this->getEffectivePrice('TND'),
            'priceEur' => $this->getEffectivePrice('EUR'),
            'displayPrice' => $this->getEffectivePrice($currency),
            'displayCurrency' => $currency,
            'personTypePrices' => $this->getEffectivePersonTypePrices(),
            'minQuantity' => $this->getEffectiveMinQuantity(),
            'maxQuantity' => $this->getEffectiveMaxQuantity(),
            'isRequired' => $this->getEffectiveIsRequired(),

            // Display configuration
            'isFeatured' => $this->is_featured,
            'displayOrder' => $this->display_order,
            'allowQuantityChange' => $extra->allow_quantity_change,
            'defaultQuantity' => $extra->default_quantity,
            'autoAdd' => $extra->auto_add,

            // Inventory
            'trackInventory' => $extra->track_inventory,
            'inventoryCount' => $extra->inventory_count,
            'hasAvailableInventory' => !$extra->track_inventory || ($extra->inventory_count ?? 0) > 0,
        ];
    }
}
