<?php

namespace App\Models;

use App\Enums\AvailabilityRuleType;
use App\Jobs\CalculateAvailabilityJob;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\CartItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailabilityRule extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Generate availability slots when a rule is created or updated
        static::saved(function (AvailabilityRule $rule) {
            if ($rule->listing) {
                // Get slot IDs that will be deleted
                $slotIds = AvailabilitySlot::where('listing_id', $rule->listing_id)->pluck('id');

                // Get hold IDs that reference these slots
                $holdIds = BookingHold::whereIn('slot_id', $slotIds)->pluck('id');

                // Delete cart_items referencing these holds FIRST
                // (cart_items.hold_id FK has no cascade delete)
                if ($holdIds->isNotEmpty()) {
                    CartItem::whereIn('hold_id', $holdIds)->delete();
                }

                // NOW we can safely delete slots (cascades to holds)
                AvailabilitySlot::where('listing_id', $rule->listing_id)->delete();

                // Only regenerate slots if there are still active rules
                if ($rule->listing->availabilityRules()->active()->exists()) {
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->addDays(90);

                    // Run the job synchronously so slots are available immediately
                    CalculateAvailabilityJob::dispatchSync($rule->listing, $startDate, $endDate);
                }
            }
        });

        // Clean up slots when a rule is deleted
        static::deleted(function (AvailabilityRule $rule) {
            if ($rule->listing) {
                // Get slot IDs that will be deleted
                $slotIds = AvailabilitySlot::where('listing_id', $rule->listing_id)->pluck('id');

                // Get hold IDs that reference these slots
                $holdIds = BookingHold::whereIn('slot_id', $slotIds)->pluck('id');

                // Delete cart_items referencing these holds FIRST
                // (cart_items.hold_id FK has no cascade delete)
                if ($holdIds->isNotEmpty()) {
                    CartItem::whereIn('hold_id', $holdIds)->delete();
                }

                // NOW we can safely delete slots (cascades to holds)
                AvailabilitySlot::where('listing_id', $rule->listing_id)->delete();

                // Regenerate if there are remaining active rules
                if ($rule->listing->availabilityRules()->active()->exists()) {
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->addDays(90);
                    CalculateAvailabilityJob::dispatchSync($rule->listing, $startDate, $endDate);
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'listing_id',
        'rule_type',
        'days_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'specific_dates',
        'capacity',
        'price_override',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule_type' => AvailabilityRuleType::class,
            'days_of_week' => 'array',
            'specific_dates' => 'array',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'price_override' => 'decimal:2',
        ];
    }

    /**
     * Get the listing that owns the rule.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the availability slots for this rule.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    /**
     * Scope for active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules of a specific type.
     */
    public function scopeOfType($query, AvailabilityRuleType $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope for rules valid on a specific date.
     */
    public function scopeValidOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $date);
        });
    }

    /**
     * Check if rule is valid for a specific date.
     */
    public function isValidForDate($date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // For SPECIFIC_DATES with explicit dates array, check against the array
        if ($this->rule_type === AvailabilityRuleType::SPECIFIC_DATES) {
            $specificDates = $this->specific_dates ?? [];
            if (! empty($specificDates)) {
                return in_array($date->toDateString(), $specificDates);
            }
            // Fallback: if no specific_dates set (old data), use start_date/end_date range
        }

        // Range checks (for WEEKLY/DAILY bounds, BLOCKED_DATES, and SPECIFIC_DATES fallback)
        if ($this->start_date && $date < $this->start_date) {
            return false;
        }

        if ($this->end_date && $date > $this->end_date) {
            return false;
        }

        // For weekly and daily rules, check day of week
        if ($this->rule_type === AvailabilityRuleType::WEEKLY ||
            $this->rule_type === AvailabilityRuleType::DAILY) {
            $daysOfWeek = $this->days_of_week ?? [];

            // If days_of_week is set and not empty, validate against it
            if (! empty($daysOfWeek)) {
                $dayOfWeek = $date->dayOfWeek;

                // Use strict comparison to handle both int and string values
                return in_array($dayOfWeek, array_map('intval', $daysOfWeek), true);
            }

            // Both WEEKLY and DAILY rules require days_of_week to be set
            // If no days selected, no slots should be created
            return false;
        }

        return true;
    }
}
