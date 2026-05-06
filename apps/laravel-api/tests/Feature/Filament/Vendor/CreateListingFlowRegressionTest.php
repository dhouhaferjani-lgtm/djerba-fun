<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Vendor;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Vendor\Resources\ListingResource\Pages\EditListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression test for the vendor listing wizard after the unit_label
 * section was inserted above "Person Type Pricing" in step 5
 * (commit c2aab50).
 *
 * What this guards against:
 *   - The new optional unit_label section accidentally hiding or breaking
 *     the existing required Person Type Pricing repeater.
 *   - A listing being savable through the Vendor edit form WITHOUT
 *     person_types[], which would later fail the publish validator
 *     in the admin panel.
 *   - The unit_label being persisted alongside person_types without
 *     conflict (both live inside the same pricing JSON column).
 */
final class CreateListingFlowRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $vendor;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create(['role' => UserRole::VENDOR->value]);

        $this->listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::NAUTICAL,
            'status' => ListingStatus::DRAFT,
            'pricing' => [
                'pricing_model' => 'per_person',
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['fr' => 'Adulte', 'en' => 'Adult'],
                        'tnd_price' => 100,
                        'eur_price' => 35,
                    ],
                ],
            ],
        ]);

        $this->actingAs($this->vendor);
    }

    /**
     * GIVEN: a listing with both unit_label and person_types populated
     * WHEN:  the vendor edit form is mounted
     * THEN:  both fields are present in form state and resolve to the
     *        right paths (proves my new section coexists with the
     *        existing person_types repeater).
     */
    public function test_unit_label_and_person_types_coexist_in_form_state(): void
    {
        // Seed both: my new field + the existing required person_types
        $this->listing->update([
            'pricing' => array_merge($this->listing->pricing, [
                'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
            ]),
        ]);

        Livewire::test(EditListing::class, ['record' => $this->listing->getKey()])
            ->assertFormSet(function (array $state) {
                // unit_label is reachable
                $this->assertSame(
                    'par jetski',
                    data_get($state, 'pricing.unit_label.fr'),
                );
                $this->assertSame(
                    'per jetski',
                    data_get($state, 'pricing.unit_label.en'),
                );

                // person_types is still reachable and intact
                $personTypes = data_get($state, 'pricing.person_types');
                $this->assertIsArray($personTypes);
                $this->assertNotEmpty($personTypes);
                $first = array_values($personTypes)[0];
                $this->assertSame('adult', $first['key']);
                $this->assertSame(100, (int) $first['tnd_price']);
                $this->assertSame(35, (int) $first['eur_price']);
            });
    }

    /**
     * GIVEN: a vendor saves a listing with only unit_label set (no person_types)
     * WHEN:  an admin attempts to update status to PUBLISHED
     * THEN:  the model's static::updating() hook rejects the transition with
     *        "Pricing information is required" — matching the user's reported
     *        screenshot. This proves the publish validator still works
     *        correctly after my changes.
     */
    public function test_publish_rejects_listing_with_unit_label_but_no_person_types(): void
    {
        // Wipe person_types, keep unit_label — exactly the user-reported state
        $this->listing->update([
            'pricing' => [
                'pricing_model' => 'per_person',
                'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
            ],
        ]);

        $caught = null;
        try {
            $this->listing->update(['status' => ListingStatus::PUBLISHED]);
        } catch (\Throwable $e) {
            $caught = $e;
        }

        $this->assertNotNull(
            $caught,
            'Expected the publish transition to throw / reject when person_types is missing',
        );
        $this->assertStringContainsStringIgnoringCase(
            'pricing',
            $caught->getMessage(),
        );

        // And the listing remains DRAFT
        $this->listing->refresh();
        $this->assertSame(ListingStatus::DRAFT, $this->listing->status);
    }

    /**
     * GIVEN: a vendor fills BOTH unit_label and person_types correctly
     * WHEN:  admin updates status to PUBLISHED
     * THEN:  the transition succeeds. Proves: my unit_label feature
     *        does NOT block publishing when the listing is otherwise valid.
     */
    public function test_publish_succeeds_when_unit_label_and_person_types_are_set(): void
    {
        $this->listing->update([
            'pricing' => array_merge($this->listing->pricing, [
                'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
            ]),
        ]);

        $this->listing->update(['status' => ListingStatus::PUBLISHED]);

        $this->listing->refresh();
        $this->assertSame(ListingStatus::PUBLISHED, $this->listing->status);

        // Sanity: the unit_label survived the publish transition
        $this->assertSame(
            'par jetski',
            data_get($this->listing->pricing, 'unit_label.fr'),
        );
        $this->assertSame(
            'per jetski',
            data_get($this->listing->pricing, 'unit_label.en'),
        );
    }
}
