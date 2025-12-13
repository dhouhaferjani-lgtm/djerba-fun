<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Spatie\Translatable\HasTranslations;

class Location extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'coordinates',
        'address',
        'city',
        'region',
        'country',
        'timezone',
        'image_url',
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
            'coordinates' => Point::class,
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
     * Get the listings for this location.
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
}
