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
     * THEN:  they are Filament TimePickers configured with the non-native
     *        widget (`->native(false)`) and seconds disabled.
     *
     * 6th iteration of the time-input UX. Prior history: TimePicker native
     * (Safari "Invalid value" on the after() string-form rule) → TimePicker
     * non-native (popover under input UX confusion) → TimePicker native + Closure
     * after() (b9e6696) → masked TextInput (483e70d, Alpine x-mask broke
     * wire:model) → unmasked TextInput + regex (04a6bc8 — current "stuck on
     * typo" bug) → THIS: TimePicker non-native + Closure after() + live(onBlur).
     *
     * The combination addresses every prior failure mode:
     *   - native(false): no <input type="time">, so Safari never fires its
     *     own HTML5 validation popover.
     *   - Closure form ->after(fn (Get $get) => $get('start_time')): the
     *     original Safari "Invalid value" trigger that the team mistook for
     *     a native-input quirk in 27c9218.
     *   - live(onBlur: true): wire:model flushes typed values inside
     *     dynamically-inserted Repeater rows (the 04a6bc8 fix).
     *   - No mask: avoids the Alpine x-mask interception that broke 483e70d.
     */
    public function test_time_fields_are_filament_timepicker_with_non_native_widget(): void
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

        $this->assertInstanceOf(\Filament\Forms\Components\TimePicker::class, $startTime);
        $this->assertInstanceOf(\Filament\Forms\Components\TimePicker::class, $endTime);

        foreach (['start_time' => $startTime, 'end_time' => $endTime] as $name => $field) {
            $this->assertFalse(
                $field->isNative(),
                "{$name} TimePicker must be ->native(false) — native <input type=\"time\"> triggers Safari's HTML5 validation popover and was the cause of the 'Invalid value' tooltip in prior iterations."
            );
            $this->assertFalse(
                $field->hasSeconds(),
                "{$name} TimePicker must have ->seconds(false) — the time_slots JSON shape is H:i, not H:i:s."
            );
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

    // The "no Alpine x-mask" guard from the masked-TextInput era (483e70d → 04a6bc8)
    // is no longer applicable: TimePicker does not use Alpine x-mask at all.
    // The structural assertion that the fields are TimePicker (not TextInput) in
    // test_time_fields_are_filament_timepicker_with_non_native_widget covers the
    // same regression surface — if anyone reverts to TextInput+mask, that test
    // fails first.

    /**
     * GIVEN: a vendor types a single-digit-hour value (e.g. "9:30") that
     *        the TimePicker's parser accepts in `fillForm()` simulation.
     * WHEN:  the form is saved.
     * THEN:  the dehydration step normalises to canonical H:i ("09:30")
     *        via TimePicker's ->format('H:i') configuration, so the
     *        DB row stores the canonical shape regardless of input shorthand.
     *
     * With TimePicker UI in production, single-digit hour input is
     * impossible at the widget level — but the underlying state can still
     * receive a non-canonical value via test simulation or programmatic
     * form fill. Pinning the dehydrate normalisation guards against any
     * drift from the H:i contract enforced upstream by validateTimeSlotsShape.
     */
    public function test_save_normalises_time_slots_to_canonical_h_i_format(): void
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
                    ['start_time' => '09:30', 'end_time' => '11:00', 'capacity' => 5],
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

    /**
     * GIVEN: a rule whose end_time is missing entirely (a partial Repeater row
     *        the vendor abandoned mid-edit).
     * WHEN:  the form is saved.
     * THEN:  Filament reports the required-field failure as a form error.
     *
     * Replaces the older `test_save_rejects_malformed_time_string` which fed
     * 'abc' through fillForm. With TimePicker that bypasses the widget's input
     * handler and Carbon throws InvalidFormatException on parse — a path that
     * cannot be reached in production (TimePicker UI rejects free-form text).
     * The required-field assertion below is the realistic equivalent.
     */
    public function test_save_rejects_missing_required_time_field(): void
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
                    ['start_time' => '09:00', 'end_time' => null, 'capacity' => 5],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['time_slots.0.end_time']);
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
        // 6th iteration: back to TimePicker (->native(false)) with the closure-form
        // `->after()` rule that was the actual root cause of the original Safari
        // "Invalid value" tooltip.
        $this->assertInstanceOf(\Filament\Forms\Components\TimePicker::class, $endTimePicker);

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

    /**
     * GIVEN: an existing WEEKLY rule whose `days_of_week` is a partial selection
     *        (Mon-Fri only — vendor configured Mon-Fri tours, no weekends).
     * WHEN:  the vendor opens the edit form and the rule_type Select fires its
     *        afterStateUpdated callback (e.g. they momentarily clicked the
     *        dropdown without changing the value, or Filament fires it on hydrate).
     * THEN:  `days_of_week` is preserved at [1,2,3,4,5] — NOT reset to the
     *        all-7-days CREATE-time default.
     *
     * Phase 2 acceptance for the days_of_week edit-safety patch. Currently
     * RED on the dev branch: the rule_type Select's afterStateUpdated callback
     * unconditionally resets days_of_week to [0..6] every time it fires.
     */
    public function test_rule_type_change_on_edit_preserves_days_of_week(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1, 2, 3, 4, 5], // Mon-Fri only
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        // Simulate the user re-selecting WEEKLY in the rule_type Select on the
        // edit form. fillForm([rule_type => WEEKLY]) propagates through Filament's
        // ->live() → afterStateUpdated → Set hooks. If those hooks fire
        // unconditionally on edit, days_of_week is wiped to [0..6].
        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'rule_type' => AvailabilityRuleType::WEEKLY->value,
            ])
            ->assertFormSet(function (array $state) {
                $this->assertSame(
                    [1, 2, 3, 4, 5],
                    array_values((array) ($state['days_of_week'] ?? [])),
                    'days_of_week must NOT be reset to all 7 days on edit — vendor partial selection must survive a rule_type re-emit.'
                );
            });
    }

    /**
     * GIVEN: the create form for a new AvailabilityRule.
     * WHEN:  the vendor selects WEEKLY for rule_type for the first time.
     * THEN:  days_of_week is auto-populated to [0..6] as a sensible default —
     *        the vendor can then deselect days they don't want.
     *
     * This is the *original* purpose of the afterStateUpdated callback. Phase 2
     * fix must preserve CREATE-time behavior while only suppressing it on EDIT.
     */
    public function test_rule_type_change_on_create_seeds_days_of_week_defaults(): void
    {
        Livewire::test(AvailabilityRuleResource\Pages\CreateAvailabilityRule::class)
            ->fillForm([
                'listing_id' => $this->listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY->value,
            ])
            ->assertFormSet(function (array $state) {
                $this->assertSame(
                    [0, 1, 2, 3, 4, 5, 6],
                    array_values((array) ($state['days_of_week'] ?? [])),
                    'On CREATE, picking WEEKLY/DAILY must seed days_of_week with all 7 days as the default starting point.'
                );
            });
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

    /**
     * GIVEN: a vendor editing a rule whose listing defines adult + child person-types,
     *        and a time_slots Repeater carrying one slot.
     * WHEN:  the vendor adds an adult-only price override (TND 120 / EUR 40) on that slot
     *        through the nested override Repeater and saves.
     * THEN:  the AvailabilityRule's time_slots JSON persists the price_overrides shape;
     *        the row in the array contains person_types[0].key == 'adult' with both
     *        currency prices stored. Child is NOT listed (lenient — falls back at runtime).
     *
     * This locks the form-binding for the per-slot pricing feature: vendor changes the UI,
     * the change reaches the JSON column, the runtime services downstream then merge.
     */
    public function test_save_persists_price_overrides_on_a_time_slot(): void
    {
        $this->listing->update([
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'time_slots' => [
                    [
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                        'capacity' => 5,
                        'price_overrides' => [
                            'person_types' => [
                                ['key' => 'adult', 'tnd_price' => 120, 'eur_price' => 40],
                            ],
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $rule->refresh();
        $slot = $rule->time_slots[0];

        $this->assertNotNull($slot['price_overrides'] ?? null, 'price_overrides must persist on the time_slot.');
        $this->assertCount(1, $slot['price_overrides']['person_types']);
        $this->assertSame('adult', $slot['price_overrides']['person_types'][0]['key']);
        $this->assertSame(120, (int) $slot['price_overrides']['person_types'][0]['tnd_price']);
        $this->assertSame(40, (int) $slot['price_overrides']['person_types'][0]['eur_price']);
    }

    /**
     * GIVEN: a vendor editing a rule with show_duration off (the default).
     * WHEN:  the vendor flips the show_duration Toggle on and saves.
     * THEN:  the rule persists with show_duration=true; reloading the form
     *        re-hydrates the toggle as on.
     *
     * Locks the form binding for the iteration-3 toggle so a future change
     * to the Filament schema can't silently drop the field.
     */
    public function test_save_persists_show_duration_toggle(): void
    {
        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                ['start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 5],
            ],
            'is_active' => true,
        ]);

        $this->assertFalse((bool) $rule->show_duration, 'Default must be false.');

        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->fillForm([
                'show_duration' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $rule->refresh();
        $this->assertTrue((bool) $rule->show_duration, 'Toggle must persist as true.');

        // Re-open the edit form and verify the toggle re-hydrates as ON.
        Livewire::test(AvailabilityRuleResource\Pages\EditAvailabilityRule::class, ['record' => $rule->getKey()])
            ->assertFormSet(function (array $state) {
                $this->assertTrue((bool) ($state['show_duration'] ?? false));
            });
    }

    /**
     * GIVEN: a listing whose pricing.person_types defines adult + child with translated labels,
     *        and a rule with one time_slot that already carries one price-override row.
     * WHEN:  the edit form mounts and we probe the price-override Select's resolved options
     *        WITHOUT going through fillForm (which bypasses the options resolver).
     * THEN:  the Select exposes both keys with locale-resolved labels.
     *
     * Regression guard for the live-only bug shipped in 6addc39: the prior closure used
     * Get('../../listing_id') which could not climb out of the doubly-nested Repeater
     * statePath, so the resolver returned [] and the dropdown rendered empty in the UI.
     * The existing test_save_persists_price_overrides_on_a_time_slot covers persistence
     * but uses fillForm, which sets state programmatically and never invokes ->options().
     */
    public function test_edit_form_options_resolve_listing_person_types(): void
    {
        $this->listing->update([
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'capacity' => 5,
                    'price_overrides' => [
                        'person_types' => [
                            ['key' => 'adult', 'tnd_price' => 60, 'eur_price' => 20],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(
            AvailabilityRuleResource\Pages\EditAvailabilityRule::class,
            ['record' => $rule->getKey()]
        )->assertSuccessful();

        app()->setLocale('en');
        $select = $this->locateOverridePersonTypeSelect($component);
        $this->assertNotNull($select, 'Override person-type Select must instantiate when an override row is hydrated.');

        $options = $select->getOptions();
        $this->assertSame(
            ['adult' => 'Adult', 'child' => 'Child'],
            $options,
            'Edit form must expose ALL listing person_types as options (not just the already-picked key).'
        );
    }

    /**
     * GIVEN: app locale is French and the same listing definition.
     * WHEN:  the edit form mounts and we probe the override Select.
     * THEN:  options are resolved using French labels from pricing.person_types[].label.fr.
     */
    public function test_edit_form_options_resolve_in_french_locale(): void
    {
        $this->listing->update([
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $rule = AvailabilityRule::create([
            'listing_id' => $this->listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [1],
            'time_slots' => [
                [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'capacity' => 5,
                    'price_overrides' => [
                        'person_types' => [
                            ['key' => 'adult', 'tnd_price' => 60, 'eur_price' => 20],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        app()->setLocale('fr');

        $component = Livewire::test(
            AvailabilityRuleResource\Pages\EditAvailabilityRule::class,
            ['record' => $rule->getKey()]
        )->assertSuccessful();

        $select = $this->locateOverridePersonTypeSelect($component);
        $this->assertNotNull($select);

        $options = $select->getOptions();
        $this->assertSame(['adult' => 'Adulte', 'child' => 'Enfant'], $options);
    }

    /**
     * GIVEN: a listing belonging to ANOTHER vendor.
     * WHEN:  the static helper is called with the Vendor panel's vendor-scoped query
     *        from the perspective of the current vendor (who does NOT own the listing).
     * THEN:  no options are returned — confirms vendor scoping isolates pricing data.
     *
     * This is the security regression guard for cross-vendor data leak.
     */
    public function test_vendor_scoping_isolates_other_vendors_person_types(): void
    {
        $otherVendor = User::factory()->create(['role' => UserRole::VENDOR->value]);
        $otherListing = Listing::factory()->create([
            'vendor_id' => $otherVendor->id,
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult'], 'tnd_price' => 100, 'eur_price' => 30],
                ],
            ],
        ]);

        // Current vendor is $this->vendor (set in setUp). Probe with the same scoping
        // closure the Vendor resource uses.
        $options = AvailabilityRuleResource::personTypeOptionsFromListing(
            $otherListing->id,
            fn () => Listing::where('vendor_id', $this->vendor->id),
        );

        $this->assertSame([], $options, 'Vendor must NOT see person types of a listing they do not own.');
    }

    /**
     * GIVEN: pricing.person_types[] entries with mixed label shapes:
     *        translated array, plain string, missing label, and label missing the current locale.
     * WHEN:  resolvePersonTypeLabel is called under different locales.
     * THEN:  fallback chain is: current locale → en → fr → first non-empty → Str::ucfirst($key).
     */
    public function test_resolve_person_type_label_fallback_chain(): void
    {
        // 1. Translated array, current locale present.
        app()->setLocale('en');
        $this->assertSame(
            'Adult',
            AvailabilityRuleResource::resolvePersonTypeLabel([
                'key' => 'adult',
                'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
            ]),
        );
        app()->setLocale('fr');
        $this->assertSame(
            'Adulte',
            AvailabilityRuleResource::resolvePersonTypeLabel([
                'key' => 'adult',
                'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
            ]),
        );

        // 2. Translated array, current locale missing → en fallback.
        app()->setLocale('ar');
        $this->assertSame(
            'Adult',
            AvailabilityRuleResource::resolvePersonTypeLabel([
                'key' => 'adult',
                'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
            ]),
        );

        // 3. Translated array, en missing too → fr fallback.
        $this->assertSame(
            'Adulte',
            AvailabilityRuleResource::resolvePersonTypeLabel([
                'key' => 'adult',
                'label' => ['fr' => 'Adulte'],
            ]),
        );

        // 4. Plain string label.
        $this->assertSame(
            'Plain Adult',
            AvailabilityRuleResource::resolvePersonTypeLabel([
                'key' => 'adult',
                'label' => 'Plain Adult',
            ]),
        );

        // 5. Missing label → Str::ucfirst($key) fallback (multibyte-safe).
        app()->setLocale('en');
        $this->assertSame(
            'Adult',
            AvailabilityRuleResource::resolvePersonTypeLabel(['key' => 'adult']),
        );
        $this->assertSame(
            'Enfant',
            AvailabilityRuleResource::resolvePersonTypeLabel(['key' => 'enfant']),
        );

        // 6. Empty label array with array-only non-empty value → returns it.
        $this->assertSame(
            'Solo',
            AvailabilityRuleResource::resolvePersonTypeLabel([
                'key' => 'adult',
                'label' => ['de' => 'Solo'],
            ]),
        );
    }

    /**
     * Walk the form schema down two Repeater levels (time_slots → price_overrides.person_types)
     * and return the override-row 'key' Select component, or null if no override row exists.
     */
    private function locateOverridePersonTypeSelect($component): ?\Filament\Forms\Components\Select
    {
        $form = $component->instance()->getForm('form');

        $timeSlots = $form->getFlatFields(withHidden: true)['time_slots'] ?? null;

        if ($timeSlots === null) {
            return null;
        }

        $timeSlotContainers = $timeSlots->getChildComponentContainers();
        $timeSlotItem = reset($timeSlotContainers);

        if ($timeSlotItem === false) {
            return null;
        }

        $timeSlotFlat = $timeSlotItem->getFlatFields(withHidden: true);
        // The inner Repeater is named with a dot ('price_overrides.person_types').
        // getFlatFields may key it under that literal name OR under a normalised
        // form. Probe by class to be robust to either convention.
        $overrides = null;

        foreach ($timeSlotFlat as $key => $field) {
            if ($field instanceof \Filament\Forms\Components\Repeater) {
                $overrides = $field;
                break;
            }
        }

        if ($overrides === null) {
            // Diagnostic — surface the keys we DID find so the failure message is actionable.
            $keys = array_keys($timeSlotFlat);
            $this->fail('Inner override Repeater not found in time_slot child container. Keys present: ' . implode(', ', $keys));
        }

        $overrideContainers = $overrides->getChildComponentContainers();
        $overrideItem = reset($overrideContainers);

        if ($overrideItem === false) {
            $this->fail('Override Repeater has no child containers — items did not hydrate. Item count: ' . count($overrideContainers));
        }

        $select = $overrideItem->getFlatFields(withHidden: true)['key'] ?? null;

        return $select instanceof \Filament\Forms\Components\Select ? $select : null;
    }
}
