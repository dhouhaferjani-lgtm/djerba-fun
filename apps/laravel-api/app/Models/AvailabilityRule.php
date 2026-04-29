<?php

namespace App\Models;

use App\Enums\AvailabilityRuleType;
use App\Jobs\CalculateAvailabilityJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class AvailabilityRule extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Validate the time_slots JSON shape *before* the row hits the database,
        // so the unique (listing_id, date, start_time) constraint can never be
        // tripped silently by an updateOrCreate clobber inside CalculateAvailabilityJob.
        static::saving(function (AvailabilityRule $rule) {
            $rule->validateTimeSlotsShape();
        });

        // Recalculate slots when a rule is created or updated.
        //
        // We deliberately do NOT pre-wipe the listing's slot set here. Doing so
        // would cascade-delete every active BookingHold (cart_items.hold_id has
        // no cascade), silently destroying customer reservations whenever a
        // vendor edits *any* rule on the listing — even one that does not
        // actually invalidate the held slot.
        //
        // Instead, the job runs a smart diff: it upserts the slots the current
        // rules call for, then deletes only the leftover (date, start_time)
        // pairs while routing their active holds through a proper cancellation
        // path (status → EXPIRED, metadata.cancellation_reason recorded,
        // customer notified by email). Holds whose slot identity is unchanged
        // survive untouched.
        static::saved(function (AvailabilityRule $rule) {
            if ($rule->listing) {
                CalculateAvailabilityJob::dispatchSync(
                    $rule->listing,
                    Carbon::today(),
                    Carbon::today()->addDays(180),
                );
            }
        });

        // Same logic on rule deletion. The job inspects the listing's currently
        // active rules — if none remain, the expected slot set is empty and the
        // diff cleans up every slot through the cancellation path.
        static::deleted(function (AvailabilityRule $rule) {
            if ($rule->listing) {
                CalculateAvailabilityJob::dispatchSync(
                    $rule->listing,
                    Carbon::today(),
                    Carbon::today()->addDays(180),
                );
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
        'time_slots',
        'start_date',
        'end_date',
        'specific_dates',
        'capacity',
        'price_override',
        'is_active',
        'show_duration',
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
            'time_slots' => 'array',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'price_override' => 'decimal:2',
            'show_duration' => 'boolean',
        ];
    }

    /**
     * Validate the time_slots JSON column before persisting.
     *
     * Hard rules (always enforced when time_slots is present):
     *  - At least one entry.
     *  - Each entry has start_time, end_time, capacity >= 1.
     *  - end_time strictly after start_time (no zero-duration or inverted windows;
     *    overnight slots are not modelled here).
     *  - The (start_time, end_time) TUPLE is unique within the rule. Two slots
     *    sharing the exact same window would still be silently merged by the
     *    unique (listing_id, date, start_time, end_time) DB constraint, but
     *    the vendor-intent is ambiguous so we reject up front.
     *  - Two slots with the SAME start_time but different end_time ARE allowed —
     *    they represent alternative durations from a shared anchor (e.g. a
     *    1-hour and 3-hour version of the same circuit), each with its own
     *    independent inventory and (optionally) its own price override.
     *  - Slot windows that overlap with DIFFERENT start_times are still rejected:
     *    that's an overbooking risk on a single physical resource, not the
     *    duration-stacking pattern the previous rule captures.
     *
     * Optional, only when an entry carries a `price_overrides` key:
     *  - Must be an object with `person_types[]`.
     *  - Each entry must have `key`, `tnd_price`, `eur_price` (lenient: not every
     *    listing person-type needs to be listed, but the rows that ARE present
     *    must be complete in both currencies).
     *  - `key` must reference a person-type defined on the parent listing's
     *    `pricing.person_types[]` (no orphans). Skipped when the listing has
     *    no person-types defined yet.
     *  - No duplicate `key` values within a single slot's overrides.
     *
     * All messages are routed through validation.availability_rule.time_slots.* so
     * French vendors do not see English literals.
     */
    public function validateTimeSlotsShape(): void
    {
        $slots = $this->time_slots;

        if (! is_array($slots) || count($slots) === 0) {
            return; // legacy single-time path or unset — nothing to validate here
        }

        $errors = [];
        $tuples = [];     // (start_time, end_time) seen so far — new dedup key
        $intervals = [];  // confirmed-shape entries used for overlap detection
        $listingKeys = $this->resolveListingPersonTypeKeys();

        foreach ($slots as $index => $entry) {
            if (! is_array($entry)) {
                $errors["time_slots.{$index}"] = [
                    trans('validation.availability_rule.time_slots.entry_invalid'),
                ];
                continue;
            }

            if (! isset($entry['start_time']) || ! isset($entry['end_time'])) {
                $errors["time_slots.{$index}"] = [
                    trans('validation.availability_rule.time_slots.times_required'),
                ];
                continue;
            }

            if (! isset($entry['capacity']) || (int) $entry['capacity'] < 1) {
                $errors["time_slots.{$index}.capacity"] = [
                    trans('validation.availability_rule.time_slots.capacity_required'),
                ];
            }

            $startTime = (string) $entry['start_time'];
            $endTime = (string) $entry['end_time'];
            $tupleKey = "{$startTime}|{$endTime}";

            if (in_array($tupleKey, $tuples, true)) {
                $errors["time_slots.{$index}.start_time"] = [
                    trans('validation.availability_rule.time_slots.duplicate_slot', [
                        'start' => $startTime,
                        'end' => $endTime,
                    ]),
                ];
            }
            $tuples[] = $tupleKey;

            if ($endTime <= $startTime) {
                $errors["time_slots.{$index}.end_time"] = [
                    trans('validation.availability_rule.time_slots.end_before_start'),
                ];
                // Don't include malformed intervals in the overlap check —
                // they'd produce a false positive against any neighbour.
                continue;
            }

            foreach ($intervals as $existing) {
                // Same anchor → durations stacking: the vendor offers e.g. a 1h
                // and a 3h version of the same circuit at 09:00. Independent
                // inventory; allowed.
                if ($startTime === $existing['start']) {
                    continue;
                }
                // Half-open intervals [start, end). Different start_times that
                // intersect would let two customers book the same physical
                // resource for the same minute → reject.
                if ($startTime < $existing['end'] && $endTime > $existing['start']) {
                    $errors["time_slots.{$index}.start_time"] = [
                        trans('validation.availability_rule.time_slots.overlapping', [
                            'first' => "{$existing['start']}–{$existing['end']}",
                            'second' => "{$startTime}–{$endTime}",
                        ]),
                    ];
                    break;
                }
            }

            $intervals[] = ['start' => $startTime, 'end' => $endTime];

            if (array_key_exists('price_overrides', $entry) && $entry['price_overrides'] !== null) {
                $this->validatePriceOverrides($entry['price_overrides'], $index, $listingKeys, $errors);
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate the optional price_overrides object inside one time_slots entry.
     *
     * Lenient: any subset of the listing's person-type keys may be overridden.
     * Strict where it matters: every row that IS present must reference a
     * legitimate listing key, must have both TND and EUR prices, and there
     * are no duplicate keys.
     *
     * @param  array<string>  $listingKeys  All defined keys on the listing's pricing.person_types[]
     * @param  array<string, array<int, string>>  $errors  Mutated by reference (Laravel idiom)
     */
    protected function validatePriceOverrides(mixed $overrides, int $index, array $listingKeys, array &$errors): void
    {
        if (! is_array($overrides)) {
            $errors["time_slots.{$index}.price_overrides"] = [
                trans('validation.availability_rule.time_slots.price_overrides.invalid_shape'),
            ];

            return;
        }

        $personTypes = $overrides['person_types'] ?? null;

        if ($personTypes === null) {
            return; // entire override block omitted — same as no override
        }

        if (! is_array($personTypes)) {
            $errors["time_slots.{$index}.price_overrides.person_types"] = [
                trans('validation.availability_rule.time_slots.price_overrides.invalid_shape'),
            ];

            return;
        }

        if (count($personTypes) === 0) {
            return; // empty array — caller normalises to null on save
        }

        $seenKeys = [];

        foreach ($personTypes as $pIndex => $pt) {
            if (! is_array($pt) || ! isset($pt['key']) || $pt['key'] === '') {
                $errors["time_slots.{$index}.price_overrides.person_types.{$pIndex}"] = [
                    trans('validation.availability_rule.time_slots.price_overrides.entry_invalid'),
                ];
                continue;
            }

            $key = (string) $pt['key'];

            if (! empty($listingKeys) && ! in_array($key, $listingKeys, true)) {
                $errors["time_slots.{$index}.price_overrides.person_types.{$pIndex}.key"] = [
                    trans('validation.availability_rule.time_slots.price_overrides.orphan_key', [
                        'key' => $key,
                    ]),
                ];
            }

            if (in_array($key, $seenKeys, true)) {
                $errors["time_slots.{$index}.price_overrides.person_types.{$pIndex}.key"] = [
                    trans('validation.availability_rule.time_slots.price_overrides.duplicate_key', [
                        'key' => $key,
                    ]),
                ];
            }
            $seenKeys[] = $key;

            $tnd = $pt['tnd_price'] ?? null;
            if ($tnd === null || $tnd === '' || (float) $tnd < 0) {
                $errors["time_slots.{$index}.price_overrides.person_types.{$pIndex}.tnd_price"] = [
                    trans('validation.availability_rule.time_slots.price_overrides.tnd_required'),
                ];
            }

            $eur = $pt['eur_price'] ?? null;
            if ($eur === null || $eur === '' || (float) $eur < 0) {
                $errors["time_slots.{$index}.price_overrides.person_types.{$pIndex}.eur_price"] = [
                    trans('validation.availability_rule.time_slots.price_overrides.eur_required'),
                ];
            }
        }
    }

    /**
     * Read the listing's defined person-type keys (used for the orphan check
     * inside override validation).
     *
     * @return array<int, string>
     */
    protected function resolveListingPersonTypeKeys(): array
    {
        $listing = $this->listing;
        if (! $listing) {
            return [];
        }

        $pricing = $listing->pricing;
        if (! is_array($pricing) || ! isset($pricing['person_types']) || ! is_array($pricing['person_types'])) {
            return [];
        }

        $keys = [];
        foreach ($pricing['person_types'] as $pt) {
            if (is_array($pt) && isset($pt['key']) && $pt['key'] !== '') {
                $keys[] = (string) $pt['key'];
            }
        }

        return $keys;
    }

    /**
     * Effective time slots for this rule.
     *
     * Returns the new `time_slots` JSON when present, else synthesises a single
     * entry from the legacy `start_time` / `end_time` / `capacity` columns so
     * any caller can iterate uniformly during the migration window.
     *
     * @return array<int, array{start_time: string, end_time: string, capacity: int}>
     */
    public function getEffectiveTimeSlots(): array
    {
        $slots = $this->time_slots;

        if (is_array($slots) && count($slots) > 0) {
            return $slots;
        }

        if ($this->start_time && $this->end_time) {
            $start = $this->start_time instanceof \DateTimeInterface
                ? $this->start_time->format('H:i:s')
                : (string) $this->start_time;

            $end = $this->end_time instanceof \DateTimeInterface
                ? $this->end_time->format('H:i:s')
                : (string) $this->end_time;

            return [[
                'start_time' => $start,
                'end_time' => $end,
                'capacity' => (int) ($this->capacity ?? 1),
            ]];
        }

        return [];
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
