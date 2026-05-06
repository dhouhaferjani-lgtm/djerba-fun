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
 * BDD coverage for the new pricing.unit_label field on the Filament Vendor
 * EditListing page.
 *
 * The Playwright spec covers public-site rendering. Filament wizards lazy-render
 * inactive steps which makes browser-driven assertions on form state
 * impractical, so we assert the Livewire form-state path directly here.
 */
final class ListingUnitLabelTest extends TestCase
{
    use RefreshDatabase;

    private User $vendor;

    private Listing $draftListing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create([
            'role' => UserRole::VENDOR->value,
        ]);

        // Filament policy forbids editing PUBLISHED listings — use DRAFT.
        $this->draftListing = Listing::factory()->create([
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
     * GIVEN: a draft nautical listing with no pricing.unit_label
     * WHEN:  the vendor opens the edit form
     * THEN:  the form state for pricing.unit_label.{fr,en} is empty (not
     *        an error, not pre-filled with a stale value).
     */
    public function test_edit_form_hydrates_empty_unit_label(): void
    {
        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->assertFormSet(function (array $state) {
                $this->assertArrayHasKey('pricing', $state);
                $unitLabel = data_get($state['pricing'], 'unit_label');
                // Either null/missing or empty translatable map — both render
                // as empty inputs on the form.
                $this->assertTrue(
                    $unitLabel === null
                        || (is_array($unitLabel) && empty(array_filter(
                            $unitLabel,
                            static fn ($v) => is_string($v) && trim($v) !== '',
                        ))),
                    'Expected empty pricing.unit_label, got: ' . json_encode($unitLabel),
                );
            });
    }

    /**
     * GIVEN: a draft listing being edited
     * WHEN:  the vendor sets pricing.unit_label.fr/en and saves
     * THEN:  the values persist to the listings.pricing JSON column and
     *        survive a fresh fetch from the DB.
     */
    public function test_save_persists_unit_label(): void
    {
        // fillForm() doesn't drill into nested array paths inside Filament
        // wizards — use direct state assignment instead.
        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->set('data.pricing.unit_label.fr', 'par jetski')
            ->set('data.pricing.unit_label.en', 'per jetski')
            ->call('save')
            ->assertHasNoFormErrors();

        $reloaded = Listing::find($this->draftListing->id);
        $this->assertSame('par jetski', data_get($reloaded->pricing, 'unit_label.fr'));
        $this->assertSame('per jetski', data_get($reloaded->pricing, 'unit_label.en'));
    }

    /**
     * GIVEN: a draft listing with pricing.unit_label set
     * WHEN:  the vendor clears both fields and saves
     * THEN:  the values are removed (or stored empty) so the public-site
     *        helper falls back to "par personne" / "per person".
     */
    public function test_save_clears_unit_label(): void
    {
        // Pre-set unit_label
        $this->draftListing->pricing = array_merge($this->draftListing->pricing, [
            'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
        ]);
        $this->draftListing->save();

        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->set('data.pricing.unit_label.fr', '')
            ->set('data.pricing.unit_label.en', '')
            ->call('save')
            ->assertHasNoFormErrors();

        $reloaded = Listing::find($this->draftListing->id);
        // After clearing, the FE-facing helper resolves null. Whether the field
        // is absent, null, or an empty-string map, PricingUnitLabel::resolve()
        // returns null for any locale.
        $stored = data_get($reloaded->pricing, 'unit_label');
        $this->assertTrue(
            $stored === null || (is_array($stored) && empty(array_filter(
                $stored,
                static fn ($v) => is_string($v) && trim($v) !== '',
            ))),
            'Expected cleared unit_label, got: ' . json_encode($stored),
        );
    }
}
