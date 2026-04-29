<?php

declare(strict_types=1);

namespace Tests\Feature\Availability;

use App\Enums\AvailabilityRuleType;
use App\Enums\HoldStatus;
use App\Enums\ServiceType;
use App\Enums\SlotStatus;
use App\Mail\SlotRemovedByVendorMail;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * BDD coverage for multi-time-slot per day availability rules.
 *
 * A single AvailabilityRule should be able to declare an array of
 * {start_time, end_time, capacity} entries — one AvailabilitySlot row
 * is materialised per entry per applicable date.
 */
class MultiTimeSlotRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GIVEN: a tour listing with a weekly rule whose time_slots = [09:00, 14:00] for Mondays
     * WHEN:  CalculateAvailabilityJob runs (via observer) over a one-Monday window
     * THEN:  exactly two AvailabilitySlot rows exist for that Monday — one per entry,
     *        each carrying its own start_time / end_time / capacity.
     */
    public function test_rule_with_two_time_slots_generates_two_slots_per_applicable_day(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('start_time')
            ->get();

        $this->assertCount(2, $slots, 'Expected two slots — one per time_slots entry.');

        $this->assertEquals('09:00:00', $slots[0]->start_time->format('H:i:s'));
        $this->assertEquals('12:00:00', $slots[0]->end_time->format('H:i:s'));
        $this->assertEquals(10, $slots[0]->capacity);
        $this->assertEquals(10, $slots[0]->remaining_capacity);

        $this->assertEquals('14:00:00', $slots[1]->start_time->format('H:i:s'));
        $this->assertEquals('17:00:00', $slots[1]->end_time->format('H:i:s'));
        $this->assertEquals(5, $slots[1]->capacity);
        $this->assertEquals(5, $slots[1]->remaining_capacity);
    }

    /**
     * GIVEN: a legacy rule with start_time / end_time / capacity but no time_slots JSON
     * WHEN:  the job runs
     * THEN:  one slot per applicable day (backwards-compatible behaviour preserved).
     *
     * Production rolls forward over a backfill window — until the data migration
     * runs, every existing rule has time_slots = null. The fallback path must not
     * change observable behaviour for those rules.
     */
    public function test_legacy_rule_without_time_slots_still_produces_one_slot_per_day(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'capacity' => 8,
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->get();

        $this->assertCount(1, $slots);
        $this->assertEquals('10:00:00', $slots[0]->start_time->format('H:i:s'));
        $this->assertEquals('15:00:00', $slots[0]->end_time->format('H:i:s'));
        $this->assertEquals(8, $slots[0]->capacity);
    }

    /**
     * GIVEN: a multi-slot rule whose entries declare different capacities
     * WHEN:  one slot is fully held but the other is untouched
     * THEN:  remaining_capacity is independent — the held slot blocks bookings,
     *        the other slot retains full capacity.
     *
     * This is the load-bearing guarantee: vendors who add a 2nd time slot
     * must not see the morning slot's capacity drained when someone books the afternoon.
     */
    public function test_capacity_is_isolated_per_time_slot_on_the_same_day(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 2],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 4],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('start_time')
            ->get();

        $this->assertCount(2, $slots);

        $morningSlot = $slots[0];
        $afternoonSlot = $slots[1];

        $this->assertEquals('09:00:00', $morningSlot->start_time->format('H:i:s'));
        $this->assertEquals('14:00:00', $afternoonSlot->start_time->format('H:i:s'));

        // Saturate the morning slot via active holds (matches the
        // remaining_capacity computed accessor on AvailabilitySlot).
        BookingHold::factory()->count(2)->create([
            'slot_id' => $morningSlot->id,
            'quantity' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->assertSame(0, $morningSlot->fresh()->remaining_capacity);
        $this->assertSame(4, $afternoonSlot->fresh()->remaining_capacity);
    }

    /**
     * GIVEN: a BLOCKED_DATES rule (whole-day blocking — out of scope for multi-slot)
     * WHEN:  the job runs
     * THEN:  it produces exactly one BLOCKED slot for the date, regardless of
     *        whether time_slots is set. Whole-day-blocking semantics preserved.
     */
    public function test_blocked_dates_rule_remains_single_window_per_day(): void
    {
        $blockedDate = Carbon::today()->addDays(3);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::BLOCKED_DATES,
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'start_date' => $blockedDate->copy(),
            'end_date' => $blockedDate->copy(),
            'is_active' => true,
        ]);

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $blockedDate->toDateString())
            ->get();

        $this->assertCount(1, $slots);
        $this->assertEquals(SlotStatus::BLOCKED, $slots[0]->status);
        $this->assertSame(0, $slots[0]->capacity);
    }

    /**
     * GIVEN: time_slots containing two entries with EXACTLY the same window
     *        (same start_time AND same end_time).
     * WHEN:  the rule is saved.
     * THEN:  validation throws — the (start_time, end_time) tuple must be unique
     *        within a rule, otherwise the unique (listing_id, date, start_time,
     *        end_time) DB index would silently merge them on upsert.
     *
     * Note: two entries that share a start_time but differ in end_time are
     * legitimate (durations stacking from a shared anchor) and are covered by
     * test_same_start_time_different_durations_pass_validation below.
     */
    public function test_duplicate_slot_tuples_fail_validation(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $this->expectException(ValidationException::class);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 3],
            ],
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(7),
            'is_active' => true,
        ]);
    }

    /**
     * GIVEN: time_slots with two entries sharing a start_time but differing in end_time
     *        (e.g. 09:00–10:00 and 09:00–12:00 — a 1-hour and a 3-hour version of
     *        the same circuit).
     * WHEN:  the rule is saved.
     * THEN:  validation passes; both rows persist as distinct AvailabilitySlot rows
     *        with their own capacity and (optionally) their own price overrides.
     *
     * This is the headline behaviour-change of the per-slot-pricing feature.
     */
    public function test_same_start_time_different_durations_pass_validation(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '10:00:00', 'capacity' => 8],
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 4],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $this->assertSame(
            2,
            AvailabilitySlot::where('listing_id', $listing->id)
                ->whereDate('date', $monday->toDateString())
                ->count(),
            'Both durations must materialise at the same start_time.',
        );
    }

    /**
     * GIVEN: a multi-slot rule that has already generated slots
     * WHEN:  the rule is updated to a different time_slots array
     * THEN:  the previous slot set is wiped and the new one is fully regenerated
     *        — no stale entries from the old configuration survive.
     *
     * Guards the AvailabilityRule observer cascade for multi-slot rules.
     */
    public function test_observer_regenerates_entire_slot_set_on_rule_update(): void
    {
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $this->assertSame(2, AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())->count());

        $rule->update([
            'time_slots' => [
                ['start_time' => '11:00:00', 'end_time' => '13:00:00', 'capacity' => 8],
            ],
        ]);

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->get();

        $this->assertCount(1, $slots, 'Old morning + afternoon slots must be removed.');
        $this->assertEquals('11:00:00', $slots[0]->start_time->format('H:i:s'));
        $this->assertEquals(8, $slots[0]->capacity);
    }

    /**
     * GIVEN: a time_slots entry whose end_time is at or before its start_time
     * WHEN:  the rule is saved
     * THEN:  validation throws a translatable error keyed at time_slots.{index}.end_time.
     *
     * Without this guard, the system would persist a malformed slot with a negative
     * duration. Overnight slots are not supported in this system — `end_time > start_time`
     * is invariant.
     */
    public function test_validation_rejects_end_time_le_start_time(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $thrown = null;

        try {
            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [Carbon::MONDAY],
                'time_slots' => [
                    ['start_time' => '14:00:00', 'end_time' => '09:00:00', 'capacity' => 5],
                ],
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(7),
                'is_active' => true,
            ]);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'Expected ValidationException for inverted time window.');
        $this->assertArrayHasKey('time_slots.0.end_time', $thrown->errors());
    }

    /**
     * GIVEN: time_slots entries that share part of the same window (09:00-12:00 and 11:00-14:00)
     * WHEN:  the rule is saved
     * THEN:  validation throws — overlapping windows on the same rule would let two
     *        customers book the same physical resource for the same minute.
     *
     * Note: `start_time` is unique (caught by the existing duplicate test); this case is
     * specifically about *partial* overlap that the duplicate-start_time check misses.
     */
    public function test_validation_rejects_overlapping_time_slots_in_same_rule(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $thrown = null;

        try {
            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [Carbon::MONDAY],
                'time_slots' => [
                    ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
                    ['start_time' => '11:00:00', 'end_time' => '14:00:00', 'capacity' => 5],
                ],
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(7),
                'is_active' => true,
            ]);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'Expected ValidationException for overlapping time slots.');
        // Error is reported on the second (offending) entry.
        $this->assertArrayHasKey('time_slots.1.start_time', $thrown->errors());
    }

    /**
     * GIVEN: the application locale is set to French
     * WHEN:  any time_slots validation fails
     * THEN:  the error message comes from the fr/validation.php translation file —
     *        no English literal leaks through to a French vendor.
     *
     * Guards against hardcoded literals in the validator.
     */
    public function test_validation_messages_use_translation_keys(): void
    {
        App::setLocale('fr');

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $thrown = null;

        try {
            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [Carbon::MONDAY],
                'time_slots' => [
                    ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
                    ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 3],
                ],
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(7),
                'is_active' => true,
            ]);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $messages = $thrown->errors();
        $this->assertArrayHasKey('time_slots.1.start_time', $messages);

        $message = $messages['time_slots.1.start_time'][0];

        // The message must come from the FR translation file, not a hardcoded English literal.
        $this->assertStringNotContainsString(
            'Duplicate',
            $message,
            'Validation message leaked an English literal — must be translated.'
        );
        $this->assertNotEquals(
            'validation.availability_rule.time_slots.duplicate_slot',
            $message,
            'Translation key was not resolved — fr/validation.php is missing the entry.'
        );
    }

    /**
     * GIVEN: a tour listing with weekly rule, time_slots = [09:00, 14:00] for next Monday
     *  AND: a customer holds the 09:00 slot
     * WHEN: the vendor saves the rule with an additional 17:00 entry (now 09:00, 14:00, 17:00)
     * THEN: the customer's hold survives — same hold id, same slot id, status ACTIVE.
     *
     * Smart diff guarantee: slots whose (date, start_time) is unchanged keep their identity,
     * so the hold pointing at them is preserved.
     */
    public function test_rule_update_preserves_holds_when_slot_identity_unchanged(): void
    {
        Mail::fake();

        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $morningSlot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('start_time')
            ->firstOrFail();

        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $morningSlot->id,
            'quantity' => 2,
            'status' => HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15),
        ]);

        $rule->update([
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
                ['start_time' => '17:30:00', 'end_time' => '20:00:00', 'capacity' => 3],
            ],
        ]);

        $hold->refresh();
        $this->assertSame(HoldStatus::ACTIVE, $hold->status, 'Hold must survive an additive rule edit.');
        $this->assertSame($morningSlot->id, $hold->slot_id, 'Slot identity (id) must be preserved.');

        $this->assertTrue(
            AvailabilitySlot::where('id', $morningSlot->id)->exists(),
            'The 09:00 slot must not have been deleted and recreated.'
        );

        $this->assertSame(
            3,
            AvailabilitySlot::where('listing_id', $listing->id)
                ->whereDate('date', $monday->toDateString())
                ->count(),
            'The new 17:30 slot should be added.'
        );

        Mail::assertNothingQueued();
    }

    /**
     * GIVEN: a listing with two slots (09:00 and 14:00) for next Monday and active holds on both
     * WHEN: the vendor removes the 14:00 slot (keeps only 09:00)
     * THEN: the 14:00 hold is EXPIRED and metadata.cancellation_reason is set
     *  AND: the 09:00 hold is unchanged
     *  AND: the 09:00 slot still exists (same id)
     *  AND: the 14:00 slot is deleted
     *  AND: a SlotRemovedByVendorMail is queued for the 14:00 customer
     */
    public function test_rule_update_cancels_holds_only_for_removed_slots(): void
    {
        Mail::fake();

        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        [$morningSlot, $afternoonSlot] = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('start_time')
            ->get()
            ->all();

        $this->assertNotNull($morningSlot);
        $this->assertNotNull($afternoonSlot);

        $morningCustomer = User::factory()->create();
        $afternoonCustomer = User::factory()->create();

        $morningHold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $morningSlot->id,
            'user_id' => $morningCustomer->id,
            'quantity' => 1,
            'status' => HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15),
        ]);

        $afternoonHold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $afternoonSlot->id,
            'user_id' => $afternoonCustomer->id,
            'quantity' => 1,
            'status' => HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Capture the afternoon hold's state right after the cleanup runs,
        // BEFORE the slot row's cascade-delete drops the hold record.
        // We use a model event listener: when slot_id matches the afternoon
        // slot AND the status flips to EXPIRED, snapshot the metadata.
        $expiredAfternoonMetadata = null;
        BookingHold::updated(function (BookingHold $hold) use ($afternoonSlot, &$expiredAfternoonMetadata) {
            if ($hold->slot_id === $afternoonSlot->id && $hold->status === HoldStatus::EXPIRED) {
                $expiredAfternoonMetadata = $hold->metadata;
            }
        });

        $rule->update([
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
            ],
        ]);

        $morningHold->refresh();
        $this->assertSame(
            HoldStatus::ACTIVE,
            $morningHold->status,
            'Morning hold must remain active — its slot was untouched.'
        );
        $this->assertSame($morningSlot->id, $morningHold->slot_id);

        // The afternoon hold's row is cascade-deleted along with the slot.
        // The audit trail lives in (a) the metadata snapshot we captured above
        // via the model `updated` event, and (b) the queued cancellation email.
        $this->assertFalse(
            BookingHold::where('id', $afternoonHold->id)->exists(),
            'Afternoon hold row is removed by cascade after expiration.'
        );
        $this->assertIsArray(
            $expiredAfternoonMetadata,
            'Afternoon hold must have been transitioned to EXPIRED with metadata before cascade-delete.'
        );
        $this->assertSame(
            'vendor_removed_slot',
            $expiredAfternoonMetadata['cancellation_reason'] ?? null,
            'Cancellation reason must be recorded in hold metadata.'
        );

        $this->assertTrue(
            AvailabilitySlot::where('id', $morningSlot->id)->exists(),
            '09:00 slot must persist.'
        );
        $this->assertFalse(
            AvailabilitySlot::where('id', $afternoonSlot->id)->exists(),
            '14:00 slot must be deleted.'
        );

        Mail::assertQueued(SlotRemovedByVendorMail::class, function ($mail) use ($afternoonCustomer) {
            return $mail->hasTo($afternoonCustomer->email);
        });
        Mail::assertNotQueued(SlotRemovedByVendorMail::class, function ($mail) use ($morningCustomer) {
            return $mail->hasTo($morningCustomer->email);
        });
    }

    /**
     * GIVEN: a cart_item points at a hold pointing at the 09:00 slot
     * WHEN:  the vendor edits the rule to add a new slot (09:00 unchanged)
     * THEN:  the cart_item still exists with the same hold_id; the hold and slot are intact.
     */
    public function test_rule_update_does_not_delete_cart_items_for_unchanged_slots(): void
    {
        Mail::fake();

        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slot = AvailabilitySlot::where('listing_id', $listing->id)->firstOrFail();

        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'quantity' => 1,
            'status' => HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15),
        ]);

        $cart = Cart::factory()->create();
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'hold_id' => $hold->id,
            'listing_id' => $listing->id,
            'quantity' => 1,
            'unit_price' => 100,
            'currency' => 'EUR',
        ]);

        $rule->update([
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
        ]);

        $this->assertTrue(
            CartItem::where('id', $cartItem->id)->exists(),
            'Cart item must not be deleted on additive rule edit.'
        );
        $this->assertSame(
            $hold->id,
            $cartItem->fresh()->hold_id,
            'Cart item must still point at the same hold.'
        );
        $this->assertSame(HoldStatus::ACTIVE, $hold->fresh()->status);
    }

    /**
     * GIVEN: a listing with one rule + one slot + one active hold (and no other rules)
     * WHEN:  the rule is deleted entirely
     * THEN:  the hold is expired with cancellation_reason and an email is queued
     *  AND:  the slot is deleted (no remaining rules → no expected slots)
     */
    public function test_rule_deletion_with_active_holds_routes_through_cancellation_path(): void
    {
        Mail::fake();

        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $slot = AvailabilitySlot::where('listing_id', $listing->id)->firstOrFail();

        $customer = User::factory()->create();

        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
            'user_id' => $customer->id,
            'quantity' => 1,
            'status' => HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15),
        ]);

        $rule->delete();

        // Critical guarantee under smart-diff: the customer was notified before
        // the hold's row was cascade-deleted by the slot removal.
        Mail::assertQueued(SlotRemovedByVendorMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });

        $this->assertFalse(
            AvailabilitySlot::where('id', $slot->id)->exists(),
            'The slot must be cleaned up when the rule is deleted and no other rules cover the date.'
        );
    }

    /**
     * GIVEN: a hold owned by a logged-in user whose slot is removed
     * WHEN:  the rule is updated to drop that slot
     * THEN:  exactly one SlotRemovedByVendorMail is queued, addressed to the user.
     *
     * This focuses the email behaviour as a standalone invariant — the per-hold
     * delivery contract — separate from the slot/hold lifecycle assertions above.
     */
    public function test_customer_receives_email_when_their_slot_is_removed(): void
    {
        Mail::fake();

        $monday = Carbon::today()->next(Carbon::MONDAY);

        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
            'start_date' => $monday->copy(),
            'end_date' => $monday->copy(),
            'is_active' => true,
        ]);

        $afternoonSlot = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('start_time', 'desc') // afternoon = later start_time
            ->firstOrFail();

        $customer = User::factory()->create();

        BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $afternoonSlot->id,
            'user_id' => $customer->id,
            'quantity' => 1,
            'status' => HoldStatus::ACTIVE,
            'expires_at' => now()->addMinutes(15),
        ]);

        $rule->update([
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
            ],
        ]);

        Mail::assertQueuedCount(1);
        Mail::assertQueued(SlotRemovedByVendorMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }

    /**
     * GIVEN: a tour listing with a WEEKLY rule whose time_slots = [09:00, 14:00]
     *        and days_of_week = [Mon, Wed, Fri], no end_date set.
     * WHEN:  CalculateAvailabilityJob runs over the full 180-day window the
     *        observer triggers (matching production observer behavior).
     * THEN:  every Mon/Wed/Fri date in the window has exactly TWO AvailabilitySlot
     *        rows — one per time_slots entry. Tue/Thu/Sat/Sun dates have ZERO.
     *        No duplicate (date, start_time) tuples slip through. No silent
     *        early-exit drops slots after the first day.
     *
     * Reproduces the user-facing concern from the dev-branch ticket: "I think it
     * is done for only some days not all them." This test asserts that *every*
     * applicable day in the recurrence pattern is materialised, not just the
     * first one. Currently passing (the production code is correct) — added as
     * an exhaustive regression guard since the existing single-Monday test
     * (test_rule_with_two_time_slots_generates_two_slots_per_applicable_day)
     * could mask a future bug that drops slots beyond day 1.
     */
    public function test_weekly_rule_with_two_slots_generates_slots_on_every_applicable_weekday_in_180d_window(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        $today = Carbon::today();
        $endOfWindow = $today->copy()->addDays(180);

        // Mon=1, Wed=3, Fri=5 in Carbon's dayOfWeek convention.
        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::FRIDAY],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
                ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        // Count how many Mon/Wed/Fri dates fall in [today, today+180].
        $expectedApplicableDates = 0;
        $cursor = $today->copy();

        while ($cursor <= $endOfWindow) {
            if (in_array($cursor->dayOfWeek, [Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::FRIDAY], true)) {
                $expectedApplicableDates++;
            }
            $cursor->addDay();
        }

        $expectedSlotCount = $expectedApplicableDates * 2; // 2 time_slots entries per applicable date

        // Use whereDate (not whereBetween on the raw column) for the bounds —
        // SQLite stores the cast 'date' value as 'YYYY-MM-DD 00:00:00', and a
        // BETWEEN against bare 'YYYY-MM-DD' bounds would lexicographically
        // exclude every slot on the upper-bound date. whereDate compares by
        // date components and is timezone- and storage-format-stable.
        $totalSlots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', '>=', $today->toDateString())
            ->whereDate('date', '<=', $endOfWindow->toDateString())
            ->count();

        $this->assertSame(
            $expectedSlotCount,
            $totalSlots,
            "Expected {$expectedSlotCount} slots ({$expectedApplicableDates} Mon/Wed/Fri dates × 2 time_slots) but got {$totalSlots}. Job either silently dropped some applicable dates or some time_slots entries."
        );

        // Verify no duplicate (date, start_time) tuples — would indicate the
        // upsert lookup is wrong and the unique DB index would have tripped.
        $duplicates = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereBetween('date', [$today->toDateString(), $endOfWindow->toDateString()])
            ->selectRaw('date, start_time, COUNT(*) as cnt')
            ->groupBy('date', 'start_time')
            ->having('cnt', '>', 1)
            ->get();

        $this->assertCount(0, $duplicates, 'No (date, start_time) tuple should appear more than once.');

        // Verify NO slots exist on Tue/Thu/Sat/Sun within the window.
        $forbiddenDayCount = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereBetween('date', [$today->toDateString(), $endOfWindow->toDateString()])
            ->get()
            ->filter(function (AvailabilitySlot $slot) {
                $dayOfWeek = Carbon::parse($slot->date)->dayOfWeek;

                return ! in_array($dayOfWeek, [Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::FRIDAY], true);
            })
            ->count();

        $this->assertSame(
            0,
            $forbiddenDayCount,
            'Slots leaked onto non-applicable weekdays — isValidForDate() day-of-week check is broken.'
        );

        // Verify each applicable date has exactly 2 distinct start_times.
        $dailyCounts = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereBetween('date', [$today->toDateString(), $endOfWindow->toDateString()])
            ->selectRaw('date, COUNT(*) as cnt')
            ->groupBy('date')
            ->get();

        foreach ($dailyCounts as $row) {
            $this->assertSame(
                2,
                (int) $row->cnt,
                "Date {$row->date} has {$row->cnt} slots — expected exactly 2 (one per time_slots entry)."
            );
        }
    }
}
