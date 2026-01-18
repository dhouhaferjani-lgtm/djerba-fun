<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingExtraStatus;
use App\Enums\ExtraPricingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtra extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'extra_id',
        'listing_extra_id',
        'quantity',
        'pricing_type',
        'unit_price_tnd',
        'unit_price_eur',
        'person_type_breakdown',
        'subtotal_tnd',
        'subtotal_eur',
        'extra_name',
        'extra_category',
        'inventory_reserved',
        'units_reserved',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'pricing_type' => ExtraPricingType::class,
        'unit_price_tnd' => 'decimal:2',
        'unit_price_eur' => 'decimal:2',
        'person_type_breakdown' => 'array',
        'subtotal_tnd' => 'decimal:2',
        'subtotal_eur' => 'decimal:2',
        'extra_name' => 'array',
        'inventory_reserved' => 'boolean',
        'units_reserved' => 'integer',
        'status' => BookingExtraStatus::class,
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    public function listingExtra(): BelongsTo
    {
        return $this->belongsTo(ListingExtra::class);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get display name for the extra.
     */
    public function getDisplayName(string $locale = 'en'): string
    {
        if (is_array($this->extra_name)) {
            return $this->extra_name[$locale] ?? $this->extra_name['en'] ?? 'Unknown Extra';
        }

        return (string) $this->extra_name;
    }

    /**
     * Get the subtotal in the specified currency.
     */
    public function getSubtotal(string $currency): float
    {
        return $currency === 'TND'
            ? (float) $this->subtotal_tnd
            : (float) $this->subtotal_eur;
    }

    /**
     * Get the unit price in the specified currency.
     */
    public function getUnitPrice(string $currency): float
    {
        return $currency === 'TND'
            ? (float) $this->unit_price_tnd
            : (float) $this->unit_price_eur;
    }

    /**
     * Check if this extra is active.
     */
    public function isActive(): bool
    {
        return $this->status === BookingExtraStatus::ACTIVE;
    }

    /**
     * Cancel this booking extra.
     */
    public function cancel(): void
    {
        $this->status = BookingExtraStatus::CANCELLED;
        $this->save();

        // Release inventory if it was reserved
        if ($this->inventory_reserved && $this->extra) {
            // Use units_reserved if available (for capacity-based extras), otherwise fall back to quantity
            $unitsToRelease = $this->units_reserved ?? $this->quantity;
            $this->extra->releaseInventory($unitsToRelease, $this->booking);
            $this->inventory_reserved = false;
            $this->units_reserved = null;
            $this->save();
        }
    }

    /**
     * Mark as refunded.
     */
    public function markRefunded(): void
    {
        $this->status = BookingExtraStatus::REFUNDED;
        $this->save();

        // Release inventory if it was reserved
        if ($this->inventory_reserved && $this->extra) {
            // Use units_reserved if available (for capacity-based extras), otherwise fall back to quantity
            $unitsToRelease = $this->units_reserved ?? $this->quantity;
            $this->extra->releaseInventory($unitsToRelease, $this->booking);
            $this->inventory_reserved = false;
            $this->units_reserved = null;
            $this->save();
        }
    }

    /**
     * Get a summary for display (e.g., in check-in).
     */
    public function getSummary(string $locale = 'en'): array
    {
        $summary = [
            'name' => $this->getDisplayName($locale),
            'category' => $this->extra_category,
            'quantity' => $this->quantity,
            'pricing_type' => $this->pricing_type->value,
        ];

        // Add notes based on pricing type
        if ($this->pricing_type === ExtraPricingType::PER_PERSON) {
            $summary['notes'] = "{$this->quantity} items for all guests";
        } elseif ($this->pricing_type === ExtraPricingType::PER_PERSON_TYPE && $this->person_type_breakdown) {
            $breakdown = [];

            foreach ($this->person_type_breakdown as $type => $data) {
                if (isset($data['count']) && $data['count'] > 0) {
                    $breakdown[] = "{$data['count']} {$type}";
                }
            }
            $summary['breakdown'] = $this->person_type_breakdown;
            $summary['notes'] = implode(', ', $breakdown);
        } elseif ($this->pricing_type === ExtraPricingType::PER_UNIT) {
            $summary['notes'] = "{$this->quantity} units to provide";
        }

        return $summary;
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', BookingExtraStatus::ACTIVE);
    }

    public function scopeForBooking($query, string $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('extra_category', $category);
    }
}
