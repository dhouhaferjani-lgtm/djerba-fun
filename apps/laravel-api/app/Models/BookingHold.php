<?php

namespace App\Models;

use App\Enums\HoldStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BookingHold extends Model
{
    use HasFactory;
    use HasUuids;

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
        'cart_id',
        'quantity',
        'person_type_breakdown',
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
            'person_type_breakdown' => 'array',
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
     * Expire the hold.
     * Capacity is automatically recalculated via the slot's computed accessor.
     */
    public function expire(): void
    {
        if ($this->status !== HoldStatus::ACTIVE) {
            return;
        }

        $this->update(['status' => HoldStatus::EXPIRED]);
        // No need to release capacity - it's computed automatically
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
            $ttl = max(1, $this->expires_at->getTimestamp() - now()->getTimestamp());

            Redis::setex($key, $ttl, json_encode([
                'id' => $this->id,
                'listing_id' => $this->listing_id,
                'slot_id' => $this->slot_id,
                'user_id' => $this->user_id,
                'session_id' => $this->session_id,
                'quantity' => $this->quantity,
                'person_type_breakdown' => $this->person_type_breakdown,
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
     *
     * @param  AvailabilitySlot  $slot  The availability slot
     * @param  User|null  $user  The authenticated user, if any
     * @param  int  $quantity  The total number of guests
     * @param  string|null  $sessionId  The guest session ID
     * @param  array|null  $personTypeBreakdown  Optional breakdown by person type: ["adult" => 2, "child" => 1]
     */
    public static function createForSlot(
        AvailabilitySlot $slot,
        ?User $user,
        int $quantity,
        ?string $sessionId = null,
        ?array $personTypeBreakdown = null
    ): ?self {
        // Wrap in transaction with row-level locking to prevent race conditions
        return DB::transaction(function () use ($slot, $user, $quantity, $sessionId, $personTypeBreakdown) {
            // Lock the slot row for this transaction to prevent concurrent bookings
            $lockedSlot = AvailabilitySlot::lockForUpdate()->find($slot->id);

            if (! $lockedSlot) {
                return null;
            }

            // Check if slot has enough capacity (uses computed remainingCapacity accessor)
            if ($lockedSlot->remainingCapacity < $quantity) {
                return null;
            }

            // Create the hold (capacity is now tracked automatically via accessor)
            $hold = static::create([
                'listing_id' => $lockedSlot->listing_id,
                'slot_id' => $lockedSlot->id,
                'user_id' => $user?->id,
                'session_id' => $sessionId,
                'quantity' => $quantity,
                'person_type_breakdown' => $personTypeBreakdown,
                'expires_at' => now()->addMinutes(self::HOLD_DURATION_MINUTES),
                'status' => HoldStatus::ACTIVE,
            ]);

            return $hold;
        });
    }
}
