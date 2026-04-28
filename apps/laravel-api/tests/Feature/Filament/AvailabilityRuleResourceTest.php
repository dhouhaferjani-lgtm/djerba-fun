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
