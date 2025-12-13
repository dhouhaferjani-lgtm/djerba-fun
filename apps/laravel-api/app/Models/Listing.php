<?php

namespace App\Models;

use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Listing extends Model
{
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'vendor_id',
        'location_id',
        'service_type',
        'status',
        'title',
        'slug',
        'summary',
        'description',
        'highlights',
        'included',
        'not_included',
        'requirements',
        'meeting_point',
        'pricing',
        'cancellation_policy',
        'min_group_size',
        'max_group_size',
        // Tour fields
        'duration',
        'difficulty',
        'distance',
        'itinerary',
        'has_elevation_profile',
        // Event fields
        'event_type',
        'start_date',
        'end_date',
        'venue',
        'agenda',
        // Stats
        'rating',
        'reviews_count',
        'bookings_count',
        'published_at',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    public array $translatable = [
        'title',
        'summary',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'status' => ListingStatus::class,
            'difficulty' => DifficultyLevel::class,
            'highlights' => 'array',
            'included' => 'array',
            'not_included' => 'array',
            'requirements' => 'array',
            'meeting_point' => 'array',
            'pricing' => 'array',
            'cancellation_policy' => 'array',
            'duration' => 'array',
            'distance' => 'array',
            'itinerary' => 'array',
            'venue' => 'array',
            'agenda' => 'array',
            'has_elevation_profile' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'published_at' => 'datetime',
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
     * Get the vendor that owns the listing.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the location for the listing.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the media for the listing.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('order');
    }

    /**
     * Get the availability rules for the listing.
     */
    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    /**
     * Get the availability slots for the listing.
     */
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    /**
     * Get the booking holds for the listing.
     */
    public function bookingHolds(): HasMany
    {
        return $this->hasMany(BookingHold::class);
    }

    /**
     * Get the reviews for the listing.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the average rating for the listing.
     */
    public function averageRating(): ?float
    {
        return $this->rating;
    }

    /**
     * Check if listing is a tour
     */
    public function isTour(): bool
    {
        return $this->service_type === ServiceType::TOUR;
    }

    /**
     * Check if listing is an event
     */
    public function isEvent(): bool
    {
        return $this->service_type === ServiceType::EVENT;
    }

    /**
     * Check if listing is published
     */
    public function isPublished(): bool
    {
        return $this->status->isPublic();
    }

    /**
     * Publish the listing
     */
    public function publish(): void
    {
        $this->update([
            'status' => ListingStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Archive the listing
     */
    public function archive(): void
    {
        $this->update([
            'status' => ListingStatus::ARCHIVED,
        ]);
    }

    /**
     * Scope for published listings
     */
    public function scopePublished($query)
    {
        return $query->where('status', ListingStatus::PUBLISHED)
            ->whereNotNull('published_at');
    }

    /**
     * Scope for tours
     */
    public function scopeTours($query)
    {
        return $query->where('service_type', ServiceType::TOUR);
    }

    /**
     * Scope for events
     */
    public function scopeEvents($query)
    {
        return $query->where('service_type', ServiceType::EVENT);
    }

    /**
     * Scope for vendor's listings
     */
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
