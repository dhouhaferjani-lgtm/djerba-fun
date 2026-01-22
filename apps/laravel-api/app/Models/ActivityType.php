<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ActivityType extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function booted(): void
    {
        static::creating(function (ActivityType $activityType) {
            if (empty($activityType->uuid)) {
                $activityType->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'display_order',
        'is_active',
        'listings_count',
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
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'listings_count' => 'integer',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the listings for this activity type.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

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
     * Scope to select only columns needed for API responses.
     */
    public function scopeSelectApi($query)
    {
        return $query->select([
            'id',
            'uuid',
            'name',
            'slug',
            'description',
            'icon',
            'color',
            'display_order',
            'is_active',
            'listings_count',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * Scope to get only active activity types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
