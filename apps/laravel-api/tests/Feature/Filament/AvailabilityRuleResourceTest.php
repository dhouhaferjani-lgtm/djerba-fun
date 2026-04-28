<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\AvailabilityRuleType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Vendor\Resources\AvailabilityRuleResource;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * BDD coverage for the vendor AvailabilityRuleResource Filament form.
 *
 * The form roundtrip is the load-bearing UX surface for the multi-time-slot
 * feature — the Repeater must hydrate from both the new time_slots JSON and
 * legacy single-time rules, and dehydrate cleanly on save.
 */
class AvailabilityRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $vendor;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create([
            'role' => UserRole::VENDOR->value,
        ]);

        $this->listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::TOUR,
        ]);

        $this->actingAs($this->vendor);
    }

    /**
     * GIVEN: a legacy rule whose time_slots JSON is null but legacy
     *        start_time/end_time/capacity columns are populated
     * WHEN:  the edit form opens
     * THEN:  the Repeater hydrates with one entry derived from the legacy fields,
     *        so the vendor sees a sensible representation rather than an empty Repeater.
     *
     * Guards against existing-rule editing breaking after the multi-slot rollout.
     */
    public function test_edit_form_hydrates_legacy_rule_into_repeater(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'capacity' => 8,
            'is_active' => true,
        ]);
        // Ensure time_slots is null so the legacy hydration path is exercised
        // (the saving event leaves time_slots alone for legacy-shaped creates).
        $rule->forceFill(['time_slots' => null])->save();

        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertFormSet(function (array $state) {
                $this->assertNotEmpty($state['time_slots'] ?? null);

                $entries = array_values($state['time_slots']);
                $this->assertCount(1, $entries);
                // Lock the H:i format. Hydrating with H:i:s ('10:00:00') causes
                // Flatpickr (TimePicker w/ ->seconds(false)) to report "Invalid
                // value" the moment the edit form renders for a legacy rule.
                $this->assertSame('10:00', (string) $entries[0]['start_time']);
                $this->assertSame('15:00', (string) $entries[0]['end_time']);
                $this->assertSame(8, (int) $entries[0]['capacity']);
            });
    }

    /**
     * GIVEN: the Filament edit form for an AvailabilityRule.
     * WHEN:  the time fields inside the time_slots Repeater are inspected.
     * THEN:  they are TextInputs (not TimePickers).
     *
     * Track 12 decision: after three rounds of tuning Filament TimePicker
     * variants, Safari's native <input type="time"> kept producing
     * "Invalid value" tooltips (most recently on freshly-added Repeater
     * rows where the user typed a perfectly valid time). Switching to
     * TextInput renders <input type="text"> which has none of the
     * native time-input quirks. Server-side regex enforces HH:MM.
     */
    public function test_time_fields_are_textinputs_not_timepickers(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertSuccessful();

        $repeaterField = $component->instance()->getForm('form')->getFlatFields(withHidden: true)['time_slots'] ?? null;
        $childForm = $repeaterField->getChildComponentContainers()[0] ?? null;
        $this->assertNotNull($childForm);

        $startTime = $childForm->getFlatFields(withHidden: true)['start_time'] ?? null;
        $endTime = $childForm->getFlatFields(withHidden: true)['end_time'] ?? null;

        $this->assertInstanceOf(\Filament\Forms\Components\TextInput::class, $startTime);
        $this->assertInstanceOf(\Filament\Forms\Components\TextInput::class, $endTime);
        $this->assertNotInstanceOf(\Filament\Forms\Components\TimePicker::class, $startTime);
        $this->assertNotInstanceOf(\Filament\Forms\Components\TimePicker::class, $endTime);
    }

    /**
     * GIVEN: the time fields are TextInputs.
     * WHEN:  validation rules are inspected.
     * THEN:  each carries a regex rule enforcing HH:MM (24h, 0-23 / 0-59).
     */
    public function test_time_fields_carry_hhmm_regex_rule(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertSuccessful();

        $repeaterField = $component->instance()->getForm('form')->getFlatFields(withHidden: true)['time_slots'] ?? null;
        $childForm = $repeaterField->getChildComponentContainers()[0] ?? null;

        foreach (['start_time', 'end_time'] as $name) {
            $field = $childForm->getFlatFields(withHidden: true)[$name];
            $rules = $field->getValidationRules();
            $regexRule = collect($rules)->first(
                fn ($r) => is_string($r) && str_starts_with($r, 'regex:')
            );
            $this->assertNotNull($regexRule, "{$name} must carry a regex validation rule.");
            $pattern = (string) preg_replace('/^regex:/', '', $regexRule);
            // Canonical HH:MM and forgiving H:MM both accepted (single-digit
            // hours are padded later by dehydrateStateUsing).
            $this->assertSame(1, preg_match($pattern, '09:30'), "{$name} regex should accept '09:30'.");
            $this->assertSame(1, preg_match($pattern, '9:30'), "{$name} regex should accept '9:30'.");
            $this->assertSame(0, preg_match($pattern, 'abc'), "{$name} regex should reject 'abc'.");
            $this->assertSame(0, preg_match($pattern, '25:00'), "{$name} regex should reject '25:00' (hour > 23).");
            $this->assertSame(0, preg_match($pattern, '12:99'), "{$name} regex should reject '12:99' (minute > 59).");
        }
    }

    /**
     * GIVEN: a rule whose time fields receive a malformed string.
     * WHEN:  the form is saved.
     * THEN:  the server-side regex rule fails the field — Filament's inline
     *        red error shown, no "Invalid value" client tooltip needed.
     */
    /**
     * GIVEN: the time fields are TextInputs.
     * WHEN:  the field configuration is inspected.
     * THEN:  each is configured as `->live(onBlur: true)` so that wire:model
     *        flushes typed values to Livewire state on blur — without this,
     *        the deferred default doesn't sync newly-typed Repeater rows
     *        before the Save action fires, and the new slot silently fails
     *        to persist.
     *
     * Track 13 regression guard.
     */
    public function test_time_fields_use_live_on_blur_for_repeater_safe_wire_model_sync(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertSuccessful();

        $repeaterField = $component->instance()->getForm('form')->getFlatFields(withHidden: true)['time_slots'] ?? null;
        $childForm = $repeaterField->getChildComponentContainers()[0] ?? null;

        foreach (['start_time', 'end_time'] as $name) {
            $field = $childForm->getFlatFields(withHidden: true)[$name];
            $this->assertTrue(
                $field->isLive() || $field->isLiveOnBlur(),
                "{$name} must be ->live() or ->live(onBlur: true) so Livewire wire:model flushes typed values; current default is deferred and Repeater rows don't sync on Save."
            );
        }
    }

    /**
     * GIVEN: the time fields are TextInputs.
     * WHEN:  the field configuration is inspected.
     * THEN:  no `mask` directive is configured. Filament's `->mask('99:99')`
     *        delegates to Alpine's `x-mask`, which intercepts input/change
     *        events and (in dynamically-inserted Repeater rows) prevents
     *        Livewire's wire:model from receiving the typed value.
     *
     * Track 13 regression guard. The placeholder + server-side regex are
     * sufficient to communicate and enforce the HH:MM shape.
     */
    public function test_time_fields_have_no_input_mask_directive(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertSuccessful();

        $repeaterField = $component->instance()->getForm('form')->getFlatFields(withHidden: true)['time_slots'] ?? null;
        $childForm = $repeaterField->getChildComponentContainers()[0] ?? null;

        foreach (['start_time', 'end_time'] as $name) {
            $field = $childForm->getFlatFields(withHidden: true)[$name];
            // Filament's TextInput::getMask() returns null when no mask is set.
            $this->assertNull(
                $field->getMask(),
                "{$name} must not have a mask directive — Alpine's x-mask interferes with wire:model in dynamically-inserted Repeater rows."
            );
        }
    }

    /**
     * GIVEN: a vendor types a single-digit-hour value (e.g. "9:30") into a
     *        time field — a common typo / shortcut.
     * WHEN:  the form is saved.
     * THEN:  the dehydrate step pads the hour to two digits before persistence,
     *        so the DB row stores "09:30" (the canonical H:i shape every other
     *        layer expects).
     */
    public function test_save_pads_single_digit_hour_to_two_digits(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'time_slots' => [
                    ['start_time' => '9:30', 'end_time' => '11:00', 'capacity' => 5],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $rule->refresh();
        $this->assertSame('09:30', (string) $rule->time_slots[0]['start_time']);
        $this->assertSame('11:00', (string) $rule->time_slots[0]['end_time']);
    }

    /**
     * GIVEN: an existing rule with 2 saved time-slot rows.
     * WHEN:  the vendor adds a 3rd row (Filament generates a UUID-like state key
     *        for it), types valid HH:MM, and clicks Save.
     * THEN:  the persisted JSON has 3 entries — the new row is not silently
     *        dropped because of the non-numeric state key.
     */
    public function test_save_persists_newly_added_repeater_row(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '03:00', 'end_time' => '06:00', 'capacity' => 5],
                ['start_time' => '08:00', 'end_time' => '11:00', 'capacity' => 14],
            ],
            'is_active' => true,
        ]);

        // Append a fully-disjoint 3rd row at 13:00 — the scenario from the
        // staging report (vendor adds a new slot at a non-overlapping time).
        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'time_slots' => [
                    ['start_time' => '03:00', 'end_time' => '06:00', 'capacity' => 5],
                    ['start_time' => '08:00', 'end_time' => '11:00', 'capacity' => 14],
                    ['start_time' => '13:00', 'end_time' => '15:00', 'capacity' => 2],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $rule->refresh();
        $this->assertCount(3, $rule->time_slots, 'Newly added 3rd row must persist.');
        $this->assertSame('13:00', (string) $rule->time_slots[2]['start_time']);
        $this->assertSame('15:00', (string) $rule->time_slots[2]['end_time']);
    }

    public function test_save_rejects_malformed_time_string(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'time_slots' => [
                    ['start_time' => 'abc', 'end_time' => '12:00', 'capacity' => 5],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['time_slots.0.start_time']);
    }

    /**
     * GIVEN: an AvailabilityRule whose time_slots JSON column already contains
     *        H:i:s formatted strings (the format the seeder, the previous Claude
     *        session's writes, and CalculateAvailabilityJob's normaliseTime()
     *        all produce).
     * WHEN:  the vendor opens the edit form for that rule.
     * THEN:  the Repeater state hydrates with H:i values only — no trailing
     *        seconds. Otherwise Flatpickr (TimePicker w/ ->seconds(false))
     *        reports "Invalid value" the moment the form renders, blocking save
     *        on every existing multi-slot rule.
     *
     * Regression test for the production-staging bug observed on app.djerbafun.com:
     * row 1 of an existing rule shows "Invalid value" tooltip on Start Time
     * because the JSON stores 09:00:00 and the picker expects 09:00.
     */
    /**
     * GIVEN: the Filament edit form for an AvailabilityRule.
     * WHEN:  the End Time TimePicker is inspected.
     * THEN:  its `->after()` validation rule is configured via Closure (not the
     *        plain string form). Inside a Repeater item, the string form
     *        `->after('start_time')` makes the Laravel validator look for an
     *        absolute-rooted field, fail to resolve, and surface a generic
     *        client-side error (the original Safari "Invalid value" tooltip).
     *        The Closure form `->after(fn (Get $get) => $get('start_time'))`
     *        resolves the SIBLING `start_time` within the same Repeater row.
     */
    public function test_end_time_after_rule_uses_closure_for_repeater_sibling(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertSuccessful();

        $repeaterField = $component->instance()->getForm('form')->getFlatFields(withHidden: true)['time_slots'] ?? null;
        $this->assertNotNull($repeaterField, 'time_slots Repeater field must exist on the form.');

        $childForm = $repeaterField->getChildComponentContainers()[0] ?? null;
        $this->assertNotNull($childForm, 'Repeater must have at least one rendered row.');

        $endTimePicker = $childForm->getFlatFields(withHidden: true)['end_time'] ?? null;
        // After Track 12 the field is a TextInput, not a TimePicker — but the
        // closure-form `->after()` rule still applies to it.
        $this->assertInstanceOf(\Filament\Forms\Components\TextInput::class, $endTimePicker);

        // The validation rules array on the field must contain an `after:` rule.
        // For the Closure form, Filament evaluates the closure at validation
        // time and the resulting rule string contains the resolved sibling
        // value (a time, not a literal field name).
        $rules = $endTimePicker->getValidationRules();
        $afterRule = collect($rules)->first(
            fn ($rule) => is_string($rule) && str_starts_with($rule, 'after:')
        );
        $this->assertNotNull($afterRule, 'End Time picker must carry an `after:` validation rule.');
        $this->assertNotSame(
            'after:start_time',
            $afterRule,
            'after: rule resolved to literal "start_time" — that means the Closure form is missing and Laravel will treat "start_time" as a date string, not as the sibling field value. This was the original Safari "Invalid value" cause.'
        );
    }

    /**
     * GIVEN: a rule whose end_time is BEFORE start_time inside one Repeater row.
     * WHEN:  the form is saved.
     * THEN:  Filament reports the after() rule failure as a Filament form error
     *        (server-side, with the localized message). This proves the after()
     *        Closure resolves the sibling correctly — if it didn't, the rule
     *        would either silently pass (sibling not found) or fail with a
     *        non-comparable date string error.
     */
    public function test_end_time_before_start_time_inside_repeater_row_fails_after_rule(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'time_slots' => [
                    ['start_time' => '14:00', 'end_time' => '09:00', 'capacity' => 5],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['time_slots.0.end_time']);
    }

    public function test_edit_form_strips_seconds_from_multi_slot_time_slots_json(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
                ['start_time' => '14:30:00', 'end_time' => '17:00:00', 'capacity' => 8],
            ],
            'is_active' => true,
        ]);

        // Simulate the user clicking Save without changing anything. The non-native
        // TimePicker hydrates form state as a full datetime ("2026-04-28 09:00:00")
        // for its internal Carbon-based picker, but on dehydration its built-in
        // formatter normalises back to the configured `seconds(false)` H:i shape
        // before persisting. This test pins that round-trip so a future widget
        // change cannot accidentally store full datetimes in the time_slots JSON.
        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $rule->refresh();
        $entries = array_values($rule->time_slots);

        $this->assertCount(2, $entries);
        $this->assertSame('09:00', (string) $entries[0]['start_time']);
        $this->assertSame('12:00', (string) $entries[0]['end_time']);
        $this->assertSame(5, (int) $entries[0]['capacity']);
        $this->assertSame('14:30', (string) $entries[1]['start_time']);
        $this->assertSame('17:00', (string) $entries[1]['end_time']);
        $this->assertSame(8, (int) $entries[1]['capacity']);
    }
}
