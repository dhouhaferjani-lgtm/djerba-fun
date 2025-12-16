<?php

namespace App\Models;

use App\Enums\HoldStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Redis;

class BookingHold extends Model
{
    use HasFactory, HasUuids;

    /**
     * The number of minutes a hold is valid for.
     */
    public const HOLD_DURATION_MINUTES = 15;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'listing_id',
        'slot_id',
        'user_id',
        'session_id',
        'quantity',
        'expires_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'status' => HoldStatus::class,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::created(function (BookingHold $hold) {
            $hold->cacheInRedis();
        });

        static::updated(function (BookingHold $hold) {
            if ($hold->isDirty('status')) {
                $hold->removeFromRedis();
            } else {
                $hold->cacheInRedis();
            }
        });

        static::deleted(function (BookingHold $hold) {
            $hold->removeFromRedis();
        });
    }

    /**
     * Get the listing that owns the hold.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the availability slot for this hold.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySlot::class, 'slot_id');
    }

    /**
     * Get the user that owns the hold.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active holds.
     */
    public function scopeActive($query)
    {
        return $query->where('status', HoldStatus::ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired holds.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', HoldStatus::ACTIVE)
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if the hold is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === HoldStatus::ACTIVE && $this->expires_at->isPast();
    }

    /**
     * Alias for isExpired() for backward compatibility.
     */
    public function hasExpired(): bool
    {
        return $this->isExpired();
    }

    /**
     * Check if the hold is active.
     */
    public function isActive(): bool
    {
        return $this->status === HoldStatus::ACTIVE && $this->expires_at->isFuture();
    }

    /**
     * Convert the hold to a booking.
     */
    public function convert(): void
    {
        $this->update(['status' => HoldStatus::CONVERTED]);
    }

    /**
     * Expire the hold and release capacity.
     */
    public function expire(): void
    {
        if ($this->status !== HoldStatus::ACTIVE) {
            return;
        }

        $this->update(['status' => HoldStatus::EXPIRED]);
        $this->slot->releaseCapacity($this->quantity);
    }

    /**
     * Cache the hold in Redis with TTL.
     * Fails gracefully if Redis is unavailable.
     */
    protected function cacheInRedis(): void
    {
        if ($this->status !== HoldStatus::ACTIVE) {
            return;
        }

        try {
            $key = $this->getRedisKey();
            $ttl = max(1, $this->expires_at->diffInSeconds(now()));

            Redis::setex($key, $ttl, json_encode([
                'id' => $this->id,
                'listing_id' => $this->listing_id,
                'slot_id' => $this->slot_id,
                'user_id' => $this->user_id,
                'session_id' => $this->session_id,
                'quantity' => $this->quantity,
                'expires_at' => $this->expires_at->toIso8601String(),
            ]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to cache booking hold in Redis', [
                'hold_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the hold from Redis.
     * Fails gracefully if Redis is unavailable.
     */
    protected function removeFromRedis(): void
    {
        try {
            Redis::del($this->getRedisKey());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to remove booking hold from Redis', [
                'hold_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the Redis key for this hold.
     */
    protected function getRedisKey(): string
    {
        return "booking_hold:{$this->id}";
    }

    /**
     * Get a hold from Redis by ID.
     */
    public static function getFromRedis(string $holdId): ?array
    {
        $key = "booking_hold:{$holdId}";
        $data = Redis::get($key);

        return $data ? json_decode($data, true) : null;
    }

    /**
     * Create a new hold for a slot.
     * Supports both authenticated users and guest checkout via session_id.
     */
    public static function createForSlot(AvailabilitySlot $slot, ?User $user, int $quantity, ?string $sessionId = null): ?self
    {
        // Check if slot has enough capacity
        if (! $slot->reserveCapacity($quantity)) {
            return null;
        }

        // Create the hold
        $hold = static::create([
            'listing_id' => $slot->listing_id,
            'slot_id' => $slot->id,
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'quantity' => $quantity,
            'expires_at' => now()->addMinutes(self::HOLD_DURATION_MINUTES),
            'status' => HoldStatus::ACTIVE,
        ]);

        return $hold;
    }
}
