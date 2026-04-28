<?php

namespace App\Jobs;

use App\Enums\AvailabilityRuleType;
use App\Enums\HoldStatus;
use App\Enums\SlotStatus;
use App\Mail\SlotRemovedByVendorMail;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CalculateAvailabilityJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Composite keys ("YYYY-MM-DD|HH:MM:SS") of every slot upserted during the
     * current run. Used by reconcileStaleSlots() to figure out which slots in
     * the date range no longer match any active rule.
     *
     * @var array<string, true>
     */
    protected array $upsertedKeys = [];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Listing $listing,
        public Carbon $startDate,
        public Carbon $endDate,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->upsertedKeys = [];

        $rules = $this->listing->availabilityRules()
            ->active()
            ->get();

        // Materialise the slots the *current* rules call for.
        $period = CarbonPeriod::create($this->startDate, $this->endDate);

        foreach ($period as $date) {
            $this->generateSlotsForDate($date, $rules);
        }

        // Now reconcile: any slot in the range whose (date, start_time) key
        // was *not* upserted above is stale. Cancel its holds with a reason
        // and notification, then delete it.
        $this->reconcileStaleSlots();
    }

    /**
     * Generate availability slots for a specific date.
     */
    protected function generateSlotsForDate(Carbon $date, $rules): void
    {
        foreach ($rules as $rule) {
            // Skip if rule is not valid for this date
            if (! $rule->isValidForDate($date)) {
                continue;
            }

            // Handle blocked dates (whole-day blocking — single time window)
            if ($rule->rule_type === AvailabilityRuleType::BLOCKED_DATES) {
                $this->blockSlot($date, $rule);
                continue;
            }

            // Accommodations stay single-slot-per-day (driven by listing check-in/out times)
            if ($this->listing->isAccommodation()) {
                $this->createOrUpdateAccommodationSlot($date, $rule);
                continue;
            }

            // Tours / nautical / events: one slot per time_slots entry.
            // getEffectiveTimeSlots() falls back to legacy start_time/end_time/capacity
            // when time_slots JSON is null, so backwards compat is automatic.
            foreach ($rule->getEffectiveTimeSlots() as $entry) {
                $this->createOrUpdateSlot($date, $rule, $entry);
            }
        }
    }

    /**
     * Create or update an availability slot for one time_slots entry.
     *
     * Uses an explicit whereDate/whereTime lookup rather than Eloquent's
     * updateOrCreate. The model casts `start_time`/`date` as `datetime`/`date`
     * even though the columns are TIME/DATE, and Eloquent's `where` does NOT
     * apply those casts to bound values — so a string like '09:00:00' would
     * never match a stored value of '2026-04-27 09:00:00' written via the
     * cast on insert. updateOrCreate would then silently fall through to a
     * fresh INSERT and trip the unique (listing_id, date, start_time) index
     * the second time the job ran on the same listing+date.
     *
     * @param  array{start_time: string, end_time: string, capacity: int}  $entry
     */
    protected function createOrUpdateSlot(Carbon $date, AvailabilityRule $rule, array $entry): void
    {
        $startTime = $this->normaliseTime($entry['start_time']);
        $endTime = $this->normaliseTime($entry['end_time']);
        $capacity = (int) ($entry['capacity'] ?? $rule->capacity ?? 1);
        $basePrice = $this->getListingBasePrice();

        $this->upsertSlotByLookup($date, $startTime, [
            'availability_rule_id' => $rule->id,
            'end_time' => $endTime,
            'capacity' => $capacity,
            'remaining_capacity' => $capacity,
            'base_price' => $basePrice,
            'status' => SlotStatus::AVAILABLE,
            'currency' => 'EUR',
        ]);
    }

    /**
     * Create or update the single per-day slot for an accommodation listing.
     *
     * Accommodations don't expose multi-time-slot semantics — they use the
     * listing's check_in_time / check_out_time as the slot window.
     */
    protected function createOrUpdateAccommodationSlot(Carbon $date, AvailabilityRule $rule): void
    {
        $startTime = $rule->start_time
            ?? ($this->listing->check_in_time
                ? Carbon::parse($this->listing->check_in_time)
                : Carbon::parse('14:00:00'));
        $endTime = $rule->end_time
            ?? ($this->listing->check_out_time
                ? Carbon::parse($this->listing->check_out_time)
                : Carbon::parse('11:00:00'));

        $basePrice = (float) ($this->listing->nightly_price_eur ?? 0);

        $this->upsertSlotByLookup($date, $startTime->format('H:i:s'), [
            'availability_rule_id' => $rule->id,
            'end_time' => $endTime->format('H:i:s'),
            'capacity' => $rule->capacity,
            'remaining_capacity' => $rule->capacity,
            'base_price' => $basePrice,
            'status' => SlotStatus::AVAILABLE,
            'currency' => 'EUR',
        ]);
    }

    /**
     * Block a slot for a specific date.
     */
    protected function blockSlot(Carbon $date, AvailabilityRule $rule): void
    {
        // For accommodations, use listing's check-in/check-out times for consistency
        if ($this->listing->isAccommodation()) {
            $startTime = $rule->start_time
                ?? ($this->listing->check_in_time
                    ? Carbon::parse($this->listing->check_in_time)
                    : Carbon::parse('14:00:00'));
            $endTime = $rule->end_time
                ?? ($this->listing->check_out_time
                    ? Carbon::parse($this->listing->check_out_time)
                    : Carbon::parse('11:00:00'));
        } else {
            $startTime = $rule->start_time ?: now()->startOfDay();
            $endTime = $rule->end_time ?: now()->endOfDay();
        }

        $this->upsertSlotByLookup($date, $startTime->format('H:i:s'), [
            'availability_rule_id' => $rule->id,
            'end_time' => $endTime->format('H:i:s'),
            'capacity' => 0,
            'remaining_capacity' => 0, // Blocked slots have 0 capacity
            'base_price' => 0,
            'status' => SlotStatus::BLOCKED,
            'currency' => 'EUR', // Default currency
        ]);
    }

    /**
     * Find an existing slot for ($listing, $date, $startTime) and update it,
     * or insert a fresh row. Then record the (date, start_time) key in
     * $upsertedKeys so reconcileStaleSlots() leaves it alone.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertSlotByLookup(Carbon $date, string $startTime, array $attributes): void
    {
        $dateStr = $date->toDateString();

        $existing = AvailabilitySlot::where('listing_id', $this->listing->id)
            ->whereDate('date', $dateStr)
            ->whereTime('start_time', $startTime)
            ->first();

        if ($existing) {
            $existing->fill($attributes)->save();
        } else {
            AvailabilitySlot::create(array_merge($attributes, [
                'listing_id' => $this->listing->id,
                'date' => $dateStr,
                'start_time' => $startTime,
            ]));
        }

        $this->markUpserted($dateStr, $startTime);
    }

    /**
     * Smart-diff cleanup: find slots in [startDate, endDate] for this listing
     * whose (date, start_time) key was NOT upserted in this run, then for each
     * one expire its active holds (with cancellation_reason + customer email)
     * before deleting the slot row.
     *
     * Untouched slots — and the holds pointing at them — survive in place.
     */
    protected function reconcileStaleSlots(): void
    {
        $stale = AvailabilitySlot::where('listing_id', $this->listing->id)
            ->whereBetween('date', [$this->startDate->toDateString(), $this->endDate->toDateString()])
            ->get();

        if ($stale->isEmpty()) {
            return;
        }

        foreach ($stale as $slot) {
            $key = $this->keyFor($slot->date, $slot->start_time);

            if (isset($this->upsertedKeys[$key])) {
                continue; // slot still exists in the new schedule — leave it alone
            }

            $this->cancelHoldsAndNotify($slot);

            // FK on booking_holds.slot_id is cascade onDelete; bookings.availability_slot_id
            // is nullOnDelete. The hold cascade is now safe because we already transitioned
            // the holds to EXPIRED + recorded the reason above.
            $slot->delete();
        }
    }

    /**
     * Expire any active holds on the given slot, recording the cancellation
     * reason in metadata and queueing a notification to the customer.
     */
    protected function cancelHoldsAndNotify(AvailabilitySlot $slot): void
    {
        $holds = BookingHold::where('slot_id', $slot->id)
            ->where('status', HoldStatus::ACTIVE)
            ->with('user')
            ->get();

        if ($holds->isEmpty()) {
            return;
        }

        $listingTitle = $this->resolveListingTitle();
        $slotDate = $slot->date instanceof \DateTimeInterface
            ? $slot->date->format('Y-m-d')
            : (string) $slot->date;
        $slotStart = $slot->start_time instanceof \DateTimeInterface
            ? $slot->start_time->format('H:i')
            : substr((string) $slot->start_time, 0, 5);
        $slotEnd = $slot->end_time instanceof \DateTimeInterface
            ? $slot->end_time->format('H:i')
            : substr((string) $slot->end_time, 0, 5);

        foreach ($holds as $hold) {
            $metadata = is_array($hold->metadata) ? $hold->metadata : [];
            $metadata['cancellation_reason'] = 'vendor_removed_slot';
            $metadata['cancelled_at'] = now()->toIso8601String();
            $metadata['previous_slot'] = [
                'date' => $slotDate,
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
            ];

            $hold->update([
                'status' => HoldStatus::EXPIRED,
                'metadata' => $metadata,
            ]);

            // Email is best-effort: only authenticated users have a deliverable
            // address on the hold. Guest holds (session-only) are silently
            // expired — the customer will see "no longer available" in the
            // cart UI when they next visit.
            if ($hold->user && $hold->user->email) {
                Mail::to($hold->user->email)->queue(new SlotRemovedByVendorMail(
                    hold: $hold,
                    listingTitle: $listingTitle,
                    slotDate: $slotDate,
                    slotStartTime: $slotStart,
                    slotEndTime: $slotEnd,
                ));
            }
        }
    }

    /**
     * Resolve a human-readable listing title for the cancellation email.
     * Translatable models return an array; pick FR with EN fallback.
     */
    protected function resolveListingTitle(): string
    {
        $title = $this->listing->title ?? null;

        if (is_array($title)) {
            return (string) ($title['fr'] ?? $title['en'] ?? array_values($title)[0] ?? 'Activity');
        }

        return (string) ($title ?: 'Activity');
    }

    protected function markUpserted(string $date, string $startTime): void
    {
        $this->upsertedKeys[$this->keyFor($date, $startTime)] = true;
    }

    /**
     * Build a stable composite key for a (date, start_time) pair regardless of
     * whether the inputs come in as Carbon, string, or short ("HH:MM") forms.
     */
    protected function keyFor($date, $startTime): string
    {
        $dateStr = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
        $timeStr = $startTime instanceof \DateTimeInterface
            ? $startTime->format('H:i:s')
            : $this->normaliseTime((string) $startTime);

        return "{$dateStr}|{$timeStr}";
    }

    /**
     * Normalise a time string to "HH:MM:SS" so the upserted-keys set and the
     * existing slot rows compare equally regardless of input shape.
     */
    protected function normaliseTime(string $time): string
    {
        // Accept "HH:MM", "HH:MM:SS", or any DB-format string.
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return $time;
    }

    /**
     * Get the base price from the listing.
     * Supports both old and new pricing formats.
     */
    protected function getListingBasePrice(): float
    {
        $pricing = $this->listing->pricing;

        if (! is_array($pricing)) {
            return 0.0;
        }

        // New dual-currency format: prioritize EUR price, fallback to TND
        if (isset($pricing['eur_price'])) {
            return (float) $pricing['eur_price'];
        }

        if (isset($pricing['tnd_price'])) {
            return (float) $pricing['tnd_price'];
        }

        // Old single-currency format
        if (isset($pricing['base_price'])) {
            return (float) $pricing['base_price'];
        }

        if (isset($pricing['basePrice'])) {
            return (float) $pricing['basePrice'];
        }

        if (isset($pricing['base'])) {
            return (float) $pricing['base'];
        }

        // Legacy format with adult pricing
        if (isset($pricing['adult']['amount'])) {
            return (float) $pricing['adult']['amount'];
        }

        return 0.0;
    }
}
