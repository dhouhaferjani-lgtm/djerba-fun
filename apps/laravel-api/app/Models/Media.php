<?php

namespace App\Models;

use App\Enums\MediaCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Media $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'url',
        'thumbnail_url',
        'alt',
        'type',
        'category',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'category' => MediaCategory::class,
        ];
    }

    /**
     * Get the parent mediable model (Listing, etc.).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if media is an image
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Check if media is a video
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Scope a query to only include hero images
     */
    public function scopeHero($query)
    {
        return $query->where('category', MediaCategory::HERO);
    }

    /**
     * Scope a query to only include gallery images
     */
    public function scopeGallery($query)
    {
        return $query->where('category', MediaCategory::GALLERY);
    }

    /**
     * Scope a query to only include featured images
     */
    public function scopeFeatured($query)
    {
        return $query->where('category', MediaCategory::FEATURED);
    }
}
