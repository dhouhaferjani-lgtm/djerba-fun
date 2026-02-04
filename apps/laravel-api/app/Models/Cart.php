<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Cart statuses.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CHECKING_OUT = 'checking_out';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ABANDONED = 'abandoned';

    /**
     * Default cart expiration in minutes.
     */
    public const DEFAULT_EXPIRATION_MINUTES = 15;

    /**
     * Maximum cart lifetime in minutes.
     */
    public const MAX_LIFETIME_MINUTES = 60;

    /**
     * Get the user who owns this cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all items in this cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get all holds associated with this cart.
     */
    public function holds(): HasMany
    {
        return $this->hasMany(BookingHold::class);
    }

    /**
     * Get the payment for this cart.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(CartPayment::class);
    }

    /**
     * Check if the cart has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if cart is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->hasExpired();
    }

    /**
     * Check if cart is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Get the total number of items.
     */
    public function getItemCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Get total guests across all items.
     */
    public function getTotalGuests(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Calculate the cart subtotal (includes extras).
     */
    public function getSubtotal(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->getTotal();  // Use getTotal() to include extras
        });
    }

    /**
     * Get the cart currency (from first item).
     */
    public function getCurrency(): string
    {
        return $this->items->first()?->currency ?? 'EUR';
    }

    /**
     * Extend cart expiration (when new items added).
     */
    public function extendExpiration(): void
    {
        $newExpiration = now()->addMinutes(self::DEFAULT_EXPIRATION_MINUTES);
        $maxExpiration = $this->created_at->addMinutes(self::MAX_LIFETIME_MINUTES);

        // Don't extend beyond max lifetime
        $this->expires_at = $newExpiration->min($maxExpiration);
        $this->save();
    }

    /**
     * Mark cart as checking out.
     */
    public function startCheckout(): void
    {
        $this->update(['status' => self::STATUS_CHECKING_OUT]);
    }

    /**
     * Mark cart as completed.
     */
    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark cart as abandoned.
     */
    public function abandon(): void
    {
        $this->update(['status' => self::STATUS_ABANDONED]);
    }

    /**
     * Scope to get active carts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired carts.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get cart for user or session.
     */
    public function scopeForOwner($query, ?int $userId, ?string $sessionId)
    {
        return $query->where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('user_id', $userId);
            } elseif ($sessionId) {
                $q->where('session_id', $sessionId);
            }
        });
    }
}
