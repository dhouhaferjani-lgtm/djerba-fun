<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceType;
use App\Enums\TagType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The table associated with the model.
     * Named 'listing_filter_tags' to avoid conflict with Filament's content block 'tags' table.
     */
    protected $table = 'listing_filter_tags';

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->uuid)) {
                $tag->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'type',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'display_order',
        'is_active',
        'listings_count',
        'applicable_service_types',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TagType::class,
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'listings_count' => 'integer',
            'applicable_service_types' => 'array',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ==================== Relationships ====================

    /**
     * Get the listings that have this tag.
     */
    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_tag')
            ->withTimestamps();
    }

    /**
     * Get published listings that have this tag.
     */
    public function publishedListings(): BelongsToMany
    {
        return $this->listings()->published();
    }

    // ==================== Scopes ====================

    /**
     * Scope to get only active tags.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by tag type.
     */
    public function scopeOfType($query, TagType|string $type)
    {
        $typeValue = $type instanceof TagType ? $type->value : $type;

        return $query->where('type', $typeValue);
    }

    /**
     * Scope to filter tags applicable to a given service type.
     * Checks if the tag's applicable_service_types contains the service type,
     * or if applicable_service_types is null (applies to all).
     */
    public function scopeForServiceType($query, ServiceType|string $serviceType)
    {
        $serviceTypeValue = $serviceType instanceof ServiceType ? $serviceType->value : $serviceType;

        return $query->where(function ($q) use ($serviceTypeValue) {
            $q->whereNull('applicable_service_types')
                ->orWhereJsonContains('applicable_service_types', $serviceTypeValue);
        });
    }

    /**
     * Scope to order by display order and slug.
     * Note: Cannot order by 'name' directly as it's a JSON column in PostgreSQL.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('slug');
    }

    /**
     * Scope to get tags that have at least one listing.
     */
    public function scopeHasListings($query)
    {
        return $query->where('listings_count', '>', 0);
    }

    /**
     * Scope to select only columns needed for API responses.
     */
    public function scopeSelectApi($query)
    {
        return $query->select([
            'id',
            'uuid',
            'type',
            'name',
            'slug',
            'description',
            'icon',
            'color',
            'display_order',
            'is_active',
            'listings_count',
            'applicable_service_types',
            'created_at',
            'updated_at',
        ]);
    }

    // ==================== Helper Methods ====================

    /**
     * Increment the listings count.
     */
    public function incrementListingsCount(): void
    {
        $this->increment('listings_count');
    }

    /**
     * Decrement the listings count.
     */
    public function decrementListingsCount(): void
    {
        $this->decrement('listings_count');
    }

    /**
     * Recalculate the listings count from the database.
     */
    public function recalculateListingsCount(): void
    {
        $this->update([
            'listings_count' => $this->publishedListings()->count(),
        ]);
    }

    /**
     * Check if this tag applies to a given service type.
     */
    public function appliesToServiceType(ServiceType $serviceType): bool
    {
        // If no restrictions, applies to all
        if (empty($this->applicable_service_types)) {
            return true;
        }

        return in_array($serviceType->value, $this->applicable_service_types, true);
    }

    /**
     * Get the type label (handles null gracefully).
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? 'Unknown';
    }
}
