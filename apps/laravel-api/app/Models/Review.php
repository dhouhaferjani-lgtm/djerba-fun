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
        'rejected_at',
        'rejection_reason',
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
            'rejected_at' => 'datetime',
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
     * Publish the review (approve).
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
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
     * Reject the review with a reason.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get the moderation status of the review.
     */
    public function getModerationStatusAttribute(): string
    {
        if ($this->is_published) {
            return 'published';
        }

        if ($this->rejected_at !== null) {
            return 'rejected';
        }

        return 'pending';
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
     * Scope to filter pending reviews (not published, not rejected).
     */
    public function scopePending($query)
    {
        return $query->where('is_published', false)->whereNull('rejected_at');
    }

    /**
     * Scope to filter rejected reviews.
     */
    public function scopeRejected($query)
    {
        return $query->where('is_published', false)->whereNotNull('rejected_at');
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

    /**
     * Scope to select only columns needed for API responses.
     * Prevents column mismatch issues by centralizing column selection.
     */
    public function scopeSelectApi($query)
    {
        return $query->select([
            'id', 'booking_id', 'listing_id', 'user_id', 'rating',
            'title', 'content', 'pros', 'cons', 'photos',
            'is_verified_booking', 'is_published', 'helpful_count',
            'created_at', 'updated_at'
        ]);
    }

    /**
     * Recalculate listing rating based on published reviews.
     * Called after review approval/rejection to update listing stats.
     */
    public static function recalculateListingRating(?Listing $listing): void
    {
        if (! $listing) {
            return;
        }

        $stats = static::query()
            ->forListing($listing->id)
            ->published()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        $listing->update([
            'rating' => $stats?->avg_rating ? round((float) $stats->avg_rating, 2) : null,
            'reviews_count' => $stats?->total_reviews ?? 0,
        ]);

        // Clear review caches so frontend gets fresh data immediately
        cache()->forget("reviews:summary:{$listing->id}");
        $sorts = ['latest', 'helpful'];
        $ratings = ['all', '1', '2', '3', '4', '5'];
        foreach ($sorts as $sort) {
            foreach ($ratings as $rating) {
                for ($page = 1; $page <= 5; $page++) {
                    cache()->forget("reviews:listing:{$listing->id}:rating:{$rating}:sort:{$sort}:page:{$page}");
                }
            }
        }
    }
}
