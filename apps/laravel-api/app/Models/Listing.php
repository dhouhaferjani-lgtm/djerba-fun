<?php

namespace App\Models;

use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Listing extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Listing $listing) {
            if (empty($listing->uuid)) {
                $listing->uuid = (string) Str::uuid();
            }
        });

        // Validate and set published_at when status changes to PUBLISHED
        static::updating(function (Listing $listing) {
            if ($listing->isDirty('status') && $listing->status === ListingStatus::PUBLISHED) {
                // CRITICAL: Validate required fields before allowing publish
                $errors = [];

                // Check title - must have at least English translation
                $title = $listing->getTranslation('title', 'en');
                if (empty($title) || (is_array($title) && empty(array_filter($title)))) {
                    $errors[] = 'English title is required';
                }

                // Check summary
                $summary = $listing->getTranslation('summary', 'en');
                if (empty($summary) || (is_array($summary) && empty(array_filter($summary)))) {
                    $errors[] = 'English summary is required';
                }

                // Check pricing - must have pricing data (new or old format)
                $pricing = $listing->pricing;
                $hasNewFormatPricing = !empty($pricing['person_types']) || !empty($pricing['personTypes']);
                $hasOldFormatPricing = !empty($pricing['base_price']) || !empty($pricing['tnd_price']) || !empty($pricing['eur_price']);
                if (!$hasNewFormatPricing && !$hasOldFormatPricing) {
                    $errors[] = 'Pricing information is required';
                }

                // Check location
                if (empty($listing->location_id)) {
                    $errors[] = 'Location is required';
                }

                if (!empty($errors)) {
                    // Try to show Filament notification if in admin context
                    try {
                        Notification::make()
                            ->title('Cannot Publish Listing')
                            ->body('Missing required fields: ' . implode(', ', $errors))
                            ->danger()
                            ->persistent()
                            ->send();
                    } catch (\Throwable $e) {
                        // Not in Filament context, that's OK
                    }

                    // Also throw exception to prevent save
                    $validator = \Illuminate\Support\Facades\Validator::make(
                        ['status' => 'published'],
                        ['status' => 'in:draft'],
                        ['status.in' => 'Cannot publish: ' . implode(', ', $errors)]
                    );
                    $validator->validate(); // This will throw ValidationException
                }

                // Set published_at if not already set
                if (empty($listing->published_at)) {
                    $listing->published_at = now();
                }
            }
        });

        // Notify admins when a new listing is created
        static::created(function (Listing $listing) {
            try {
                $admins = User::where('role', UserRole::ADMIN)->get();
                $vendorName = $listing->vendor?->display_name ?? 'Unknown Vendor';

                // Safely get title - handle potential nested arrays from form submission
                $titleValue = $listing->getTranslation('title', 'en');
                if (is_array($titleValue)) {
                    $listingTitle = $titleValue['en'] ?? reset($titleValue) ?: 'Untitled';
                } else {
                    $listingTitle = $titleValue ?: 'Untitled';
                }

                foreach ($admins as $admin) {
                    Notification::make()
                        ->title('New Listing Created')
                        ->icon('heroicon-o-document-plus')
                        ->body("Vendor \"{$vendorName}\" created a new listing: \"{$listingTitle}\"")
                        ->actions([
                            NotificationAction::make('view')
                                ->label('View Listing')
                                ->url("/admin/listings/{$listing->id}")
                                ->button(),
                        ])
                        ->sendToDatabase($admin);
                }
            } catch (\Throwable $e) {
                // Don't let notification errors break listing creation
                \Log::warning('Failed to send new listing notification', ['error' => $e->getMessage()]);
            }
        });
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'title' => '[]',
        'summary' => '[]',
        'description' => '[]',
        'highlights' => '[]',
        'included' => '[]',
        'not_included' => '[]',
        'requirements' => '[]',
        'meeting_point' => '[]',
        'pricing' => '[]',
        'cancellation_policy' => '[]',
        'min_group_size' => 1,
        'max_group_size' => 10,
        'min_advance_booking_hours' => 0,
        'bookings_count' => 0,
        'reviews_count' => 0,
        'has_elevation_profile' => false,
        'require_traveler_names' => false,
        'traveler_names_timing' => 'before_activity',
    ];

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
        'min_advance_booking_hours',
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
        // Booking settings
        'require_traveler_names',
        'traveler_names_timing',
        // Safety & Accessibility
        'safety_info',
        'accessibility_info',
        'difficulty_details',
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
            'require_traveler_names' => 'boolean',
            'traveler_names_timing' => 'string',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'published_at' => 'datetime',
            'rating' => 'float',
            'reviews_count' => 'integer',
            'bookings_count' => 'integer',
            'safety_info' => 'array',
            'accessibility_info' => 'array',
            'difficulty_details' => 'array',
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
     * Resolve the route binding, accepting both slug and ID for backward compatibility.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        // First try by slug (preferred)
        $listing = $this->where('slug', $value)->first();

        // If not found by slug, try by ID (for backward compatibility)
        if (! $listing && is_numeric($value)) {
            $listing = $this->find($value);
        }

        return $listing;
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
     * Get the extras available for this listing.
     */
    public function extras(): BelongsToMany
    {
        return $this->belongsToMany(Extra::class, 'listing_extras')
            ->using(ListingExtra::class)
            ->withPivot([
                'id',
                'override_price_tnd',
                'override_price_eur',
                'override_person_type_prices',
                'override_min_quantity',
                'override_max_quantity',
                'override_is_required',
                'available_for_slots',
                'available_for_person_types',
                'display_conditions',
                'display_order',
                'is_featured',
                'is_active',
            ])
            ->withTimestamps();
    }

    /**
     * Get the listing extras pivot records.
     */
    public function listingExtras(): HasMany
    {
        return $this->hasMany(ListingExtra::class);
    }

    /**
     * Get active extras for this listing.
     */
    public function activeExtras(): BelongsToMany
    {
        return $this->extras()
            ->wherePivot('is_active', true)
            ->whereHas('extra', fn ($q) => $q->where('is_active', true));
    }

    /**
     * Get the FAQs for the listing.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(ListingFaq::class)->ordered();
    }

    /**
     * Get active FAQs for the listing.
     */
    public function activeFaqs(): HasMany
    {
        return $this->faqs()->active();
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

    /**
     * Get the hero image for the listing.
     */
    public function heroImage(): ?Media
    {
        return $this->media()->hero()->first();
    }

    /**
     * Get gallery images for the listing.
     */
    public function galleryImages()
    {
        return $this->media()->gallery()->get();
    }

    /**
     * Get featured images for the listing.
     */
    public function featuredImages()
    {
        return $this->media()->featured()->limit(3)->get();
    }

    /**
     * Check if this listing requires traveler names.
     */
    public function requiresTravelerNames(): bool
    {
        return $this->require_traveler_names ?? false;
    }

    /**
     * Check if traveler names should be prompted immediately after payment.
     */
    public function promptForNamesImmediately(): bool
    {
        return $this->requiresTravelerNames()
            && $this->traveler_names_timing === 'immediate';
    }
}
