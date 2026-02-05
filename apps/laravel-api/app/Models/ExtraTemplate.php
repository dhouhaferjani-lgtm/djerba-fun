<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraCategory;
use App\Enums\ExtraPricingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Global extra templates that vendors can clone to their own extras.
 *
 * These are pre-defined common extras (breakfast, transport, equipment, etc.)
 * that save vendors time by providing suggested prices and configurations.
 */
class ExtraTemplate extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'short_description',
        'icon',
        'pricing_type',
        'suggested_price_tnd',
        'suggested_price_eur',
        'person_type_prices',
        'category',
        'min_quantity',
        'max_quantity',
        'capacity_per_unit',
        'track_inventory',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'person_type_prices' => 'array',
        'suggested_price_tnd' => 'decimal:2',
        'suggested_price_eur' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'capacity_per_unit' => 'integer',
        'track_inventory' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'pricing_type' => ExtraPricingType::class,
        'category' => ExtraCategory::class,
    ];

    public array $translatable = ['name', 'description', 'short_description'];

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, ExtraCategory $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderByRaw("COALESCE(name->>'en', name->>'fr') asc");
    }

    // =========================================================================
    // Clone Methods
    // =========================================================================

    /**
     * Clone this template to create a vendor's own Extra.
     */
    public function cloneForVendor(string $vendorId): Extra
    {
        return Extra::create([
            'vendor_id' => $vendorId,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'short_description' => $this->getTranslations('short_description'),
            'image_url' => null,
            'thumbnail_url' => null,
            'pricing_type' => $this->pricing_type,
            'base_price_tnd' => $this->suggested_price_tnd,
            'base_price_eur' => $this->suggested_price_eur,
            'person_type_prices' => $this->person_type_prices,
            'min_quantity' => $this->min_quantity ?? 0,
            'max_quantity' => $this->max_quantity,
            'default_quantity' => 1,
            'track_inventory' => $this->track_inventory ?? false,
            'inventory_count' => null,
            'capacity_per_unit' => $this->capacity_per_unit,
            'is_required' => false,
            'auto_add' => false,
            'allow_quantity_change' => true,
            'display_order' => 0,
            'category' => $this->category,
            'is_active' => false, // Start inactive so vendor can customize
        ]);
    }
}
