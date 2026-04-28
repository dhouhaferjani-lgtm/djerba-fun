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
                $this->assertStringStartsWith('10:00', (string) $entries[0]['start_time']);
                $this->assertStringStartsWith('15:00', (string) $entries[0]['end_time']);
                $this->assertSame(8, (int) $entries[0]['capacity']);
            });
    }
}
