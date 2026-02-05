<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailabilitySlot extends Model
{
    use HasFactory;

    /**
     * The attributes that should be appended to model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['remainingCapacity'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'listing_id',
        'availability_rule_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'remaining_capacity',
        'base_price',
        'status',
        'currency',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => SlotStatus::class,
            'base_price' => 'decimal:2',
        ];
    }

    /**
     * Get the listing that owns the slot.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the availability rule that generated this slot.
     */
    public function availabilityRule(): BelongsTo
    {
        return $this->belongsTo(AvailabilityRule::class);
    }

    /**
     * Get the booking holds for this slot.
     */
    public function holds(): HasMany
    {
        return $this->hasMany(BookingHold::class, 'slot_id');
    }

    /**
     * Get the confirmed bookings for this slot.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'availability_slot_id');
    }

    /**
     * Scope for available slots.
     */
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', [SlotStatus::AVAILABLE, SlotStatus::LIMITED]);
    }

    /**
     * Scope for slots on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope for slots between dates.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for future slots.
     */
    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    /**
     * Scope to select only columns needed for API responses.
     * Prevents column mismatch issues by centralizing column selection.
     */
    public function scopeSelectApi($query)
    {
        return $query->select([
            'id', 'listing_id', 'availability_rule_id', 'date', 'start_time', 'end_time',
            'capacity', 'remaining_capacity', 'base_price', 'status', 'currency',
            'created_at', 'updated_at',
        ]);
    }

    /**
     * Update the slot status based on remaining capacity.
     * Uses the computed remainingCapacity accessor.
     */
    public function updateStatus(): void
    {
        $remaining = $this->remainingCapacity; // Use computed accessor

        $this->status = match (true) {
            $remaining === 0 => SlotStatus::SOLD_OUT,
            $remaining <= $this->capacity * 0.3 => SlotStatus::LIMITED,
            default => SlotStatus::AVAILABLE,
        };

        $this->save();
    }

    /**
     * Check if slot is bookable.
     */
    public function isBookable(): bool
    {
        return $this->status->isBookable() && $this->remainingCapacity > 0;
    }

    /**
     * Computed accessor for remaining capacity.
     * Dynamically calculates based on confirmed bookings and active holds.
     */
    public function getRemainingCapacityAttribute(): int
    {
        // Count spots taken by confirmed/completed bookings
        $bookedQuantity = $this->bookings()
            ->whereIn('status', [
                BookingStatus::CONFIRMED,
                BookingStatus::COMPLETED,
            ])
            ->sum('quantity');

        // Count spots held by active holds (temporary reservations)
        $heldQuantity = $this->holds()
            ->where('status', HoldStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->sum('quantity');

        // Calculate remaining: capacity - (confirmed bookings + active holds)
        return max(0, $this->capacity - $bookedQuantity - $heldQuantity);
    }
}
