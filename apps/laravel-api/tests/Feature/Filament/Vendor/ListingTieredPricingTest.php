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
 * BDD coverage for tiered pricing on the Filament Vendor EditListing form.
 * Asserts the Livewire form-state path directly (wizards lazy-render steps,
 * so browser assertions are impractical — same approach as ListingUnitLabelTest).
 */
final class ListingTieredPricingTest extends TestCase
{
    use RefreshDatabase;

    private User $vendor;

    private Listing $draftListing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create(['role' => UserRole::VENDOR->value]);

        $this->draftListing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::NAUTICAL,
            'status' => ListingStatus::DRAFT,
            'pricing' => [
                'pricing_model' => 'per_person',
                'person_types' => [
                    ['key' => 'adult', 'label' => ['fr' => 'Adulte', 'en' => 'Adult'], 'tnd_price' => 100, 'eur_price' => 35],
                ],
            ],
        ]);

        $this->actingAs($this->vendor);
    }

    public function test_vendor_can_switch_to_tiered_and_save_group_totals(): void
    {
        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->set('data.pricing.pricing_strategy', 'tiered')
            ->set('data.pricing.tiers', [
                ['tnd_total' => 100, 'eur_total' => 30],
                ['tnd_total' => 180, 'eur_total' => 54],
                ['tnd_total' => 180, 'eur_total' => 54],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $reloaded = Listing::find($this->draftListing->id);

        $this->assertSame('tiered', data_get($reloaded->pricing, 'pricing_strategy'));

        $tiers = array_values(data_get($reloaded->pricing, 'tiers') ?? []);
        $this->assertCount(3, $tiers);
        $this->assertEqualsWithDelta(100, (float) ($tiers[0]['tnd_total'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(30, (float) ($tiers[0]['eur_total'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(180, (float) ($tiers[1]['tnd_total'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(54, (float) ($tiers[2]['eur_total'] ?? 0), 0.001);
    }

    public function test_tiered_form_hydrates_existing_tiers(): void
    {
        $this->draftListing->pricing = [
            'pricing_strategy' => 'tiered',
            'tiers' => [
                ['tnd_total' => 100, 'eur_total' => 30],
                ['tnd_total' => 180, 'eur_total' => 54],
            ],
        ];
        $this->draftListing->save();

        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->assertFormSet(function (array $state) {
                $this->assertSame('tiered', data_get($state['pricing'], 'pricing_strategy'));
                $tiers = array_values(data_get($state['pricing'], 'tiers') ?? []);
                $this->assertCount(2, $tiers);
            });
    }
}
