<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailabilitySlot extends Model
{
    use HasFactory;

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
     * Update the slot status based on remaining capacity.
     */
    public function updateStatus(): void
    {
        $this->status = match (true) {
            $this->remaining_capacity === 0 => SlotStatus::SOLD_OUT,
            $this->remaining_capacity <= $this->capacity * 0.3 => SlotStatus::LIMITED,
            default => SlotStatus::AVAILABLE,
        };

        $this->save();
    }

    /**
     * Reserve capacity for a hold.
     */
    public function reserveCapacity(int $quantity): bool
    {
        if ($this->remaining_capacity < $quantity) {
            return false;
        }

        $this->remaining_capacity -= $quantity;
        $this->updateStatus();

        return true;
    }

    /**
     * Release capacity from a hold.
     */
    public function releaseCapacity(int $quantity): void
    {
        $this->remaining_capacity = min($this->remaining_capacity + $quantity, $this->capacity);
        $this->updateStatus();
    }

    /**
     * Check if slot is bookable.
     */
    public function isBookable(): bool
    {
        return $this->status->isBookable() && $this->remaining_capacity > 0;
    }
}
