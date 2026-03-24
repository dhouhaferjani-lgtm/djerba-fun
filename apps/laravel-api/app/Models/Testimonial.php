<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The attributes that are translatable.
     */
    public array $translatable = [
        'text',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'photo',
        'text',
        'rating',
        'location',
        'activity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Testimonial $testimonial) {
            if (empty($testimonial->uuid)) {
                $testimonial->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Scope active testimonials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered testimonials.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the full URL for the photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        // Return as-is if already a full URL
        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }

    /**
     * Get localized text for a specific locale.
     */
    public function getLocalizedText(string $locale): string
    {
        return $this->getTranslation('text', $locale) ?? $this->getTranslation('text', 'fr') ?? '';
    }
}
