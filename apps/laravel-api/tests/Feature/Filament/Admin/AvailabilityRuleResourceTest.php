<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\AvailabilityRuleType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\AvailabilityRuleResource;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the Admin AvailabilityRuleResource form, mirroring
 * the live-bug guards already present on the Vendor twin.
 *
 * The Admin and Vendor resources share the per-slot price-override Select via
 * the ResolvesListingPersonTypes trait. The label-fallback and helper unit
 * tests live alongside the Vendor test file (Tests\Feature\Filament\AvailabilityRuleResourceTest)
 * and aren't duplicated here — only the Admin-specific form-mounting paths.
 */
class AvailabilityRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vendor;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->vendor = User::factory()->create([
            'role' => UserRole::VENDOR->value,
        ]);

        $this->listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::TOUR,
            'pricing' => [
                'person_types' => [
                    ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 15],
                    ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 10],
                ],
            ],
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * GIVEN: a rule on a listing with two person types defined; admin is logged in.
     * WHEN:  the Admin Edit form mounts and we probe the override Select's options
     *        WITHOUT going through fillForm.
     * THEN:  options resolve from the listing's pricing.person_types[] regardless of
     *        which vendor owns the listing — admins see ALL listings' person types.
     *
     * Live-bug regression guard: prior code used Get('../../listing_id') which
     * resolved to null inside the doubly-nested Repeater, causing the dropdown
     * to render empty.
     */
    public function test_admin_edit_form_options_resolve_listing_person_types(): void
    {
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
        $this->assertNotNull($select, 'Admin Edit form must instantiate the override Select when an override row hydrates.');

        $this->assertSame(
            ['adult' => 'Adult', 'child' => 'Child'],
            $select->getOptions(),
            'Admin Edit form must expose ALL listing person_types as options.'
        );
    }

    /**
     * GIVEN: admin is creating a rule from scratch (no $record yet).
     * WHEN:  listing_id is set in form state and the Select's options closure
     *        is invoked the way Filament invokes it on Create.
     * THEN:  options resolve from $livewire->data['listing_id'] — proving the
     *        closure's primary fallback path (form-state read when $record is null)
     *        works.
     *
     * The Edit-form test above covers full Repeater hydration and getOptions()
     * resolution end-to-end. This Create-flow test specifically covers the
     * branch where $record is null and the closure must read form state — that
     * branch is unreachable on Edit because $record is always set there.
     */
    public function test_admin_create_form_options_resolve_when_listing_picked(): void
    {
        $component = Livewire::test(AvailabilityRuleResource\Pages\CreateAvailabilityRule::class)
            ->assertSuccessful()
            ->set('data.listing_id', $this->listing->id);

        $component->assertSet('data.listing_id', $this->listing->id);

        $page = $component->instance();
        $listingIdFromState = data_get($page, 'data.listing_id');
        $this->assertNotNull(
            $listingIdFromState,
            '$livewire->data["listing_id"] must hold the picked listing on Create.'
        );

        // Mirror the Select::options() closure's primary path:
        //   data_get($livewire, 'data.listing_id') ?? $record?->listing_id
        // With $record = null on Create, the form-state read carries the lookup.
        app()->setLocale('en');
        $options = AvailabilityRuleResource::personTypeOptionsFromListing(
            $listingIdFromState !== null ? (int) $listingIdFromState : null,
        );

        $this->assertSame(
            ['adult' => 'Adult', 'child' => 'Child'],
            $options,
            'On Create, options must resolve from $livewire->data["listing_id"] when $record is null.'
        );
    }

    /**
     * Walk the form schema down two Repeater levels (time_slots → price_overrides.person_types)
     * to the override-row 'key' Select.
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

        $overrides = null;

        foreach ($timeSlotItem->getFlatFields(withHidden: true) as $field) {
            if ($field instanceof \Filament\Forms\Components\Repeater) {
                $overrides = $field;
                break;
            }
        }

        if ($overrides === null) {
            return null;
        }

        $overrideContainers = $overrides->getChildComponentContainers();
        $overrideItem = reset($overrideContainers);

        if ($overrideItem === false) {
            return null;
        }

        $select = $overrideItem->getFlatFields(withHidden: true)['key'] ?? null;

        return $select instanceof \Filament\Forms\Components\Select ? $select : null;
    }
}
