<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryChangeType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraInventoryLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'extra_id',
        'booking_id',
        'change_type',
        'quantity_change',
        'previous_count',
        'new_count',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'change_type' => InventoryChangeType::class,
        'quantity_change' => 'integer',
        'previous_count' => 'integer',
        'new_count' => 'integer',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get a human-readable description of the change.
     */
    public function getDescription(): string
    {
        $absChange = abs($this->quantity_change);

        return match ($this->change_type) {
            InventoryChangeType::RESERVED => "Reserved {$absChange} unit(s) for booking",
            InventoryChangeType::RELEASED => "Released {$absChange} unit(s) from booking",
            InventoryChangeType::ADJUSTMENT => $this->quantity_change >= 0
                ? "Increased inventory by {$absChange}"
                : "Decreased inventory by {$absChange}",
            InventoryChangeType::RESTOCK => "Restocked {$absChange} unit(s)",
        };
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeForExtra($query, string $extraId)
    {
        return $query->where('extra_id', $extraId);
    }

    public function scopeForBooking($query, string $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeByType($query, InventoryChangeType $type)
    {
        return $query->where('change_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
