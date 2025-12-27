<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'listing_id',
        'user_id',
        'rating',
        'title',
        'content',
        'pros',
        'cons',
        'photos',
        'is_verified_booking',
        'is_published',
        'published_at',
        'helpful_count',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'pros' => 'array',
            'cons' => 'array',
            'photos' => 'array',
            'is_verified_booking' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'helpful_count' => 'integer',
        ];
    }

    /**
     * Get the booking for this review.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the listing being reviewed.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the user who wrote the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vendor's reply to this review.
     */
    public function reply(): HasOne
    {
        return $this->hasOne(ReviewReply::class);
    }

    /**
     * Publish the review.
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the review.
     */
    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Increment the helpful count.
     */
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Scope to filter published reviews.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to filter by listing.
     */
    public function scopeForListing($query, string $listingId)
    {
        return $query->where('listing_id', $listingId);
    }

    /**
     * Scope to filter by rating.
     */
    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to order by most helpful.
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }
}
