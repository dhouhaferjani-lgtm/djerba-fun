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
 * BDD coverage for the optional group-discount section on the Filament Vendor
 * EditListing form. A tiered listing keeps its per-person-type pricing; the
 * vendor may add optional flat totals for group sizes 2-5 (each independent).
 * Asserts the Livewire form-state path directly (wizards lazy-render steps).
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

    public function test_vendor_can_add_optional_group_discounts(): void
    {
        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->set('data.pricing.pricing_strategy', 'tiered')
            ->set('data.pricing.tiers', [
                ['group_size' => 2, 'tnd_total' => 180, 'eur_total' => 60],
                ['group_size' => 5, 'tnd_total' => 400, 'eur_total' => 140],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $reloaded = Listing::find($this->draftListing->id);

        $this->assertSame('tiered', data_get($reloaded->pricing, 'pricing_strategy'));
        // Person-type base pricing is preserved (group discounts are an overlay).
        $this->assertNotEmpty(data_get($reloaded->pricing, 'person_types'));

        $tiers = array_values(data_get($reloaded->pricing, 'tiers') ?? []);
        $this->assertCount(2, $tiers);
        $this->assertEqualsWithDelta(2, (int) ($tiers[0]['group_size'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(180, (float) ($tiers[0]['tnd_total'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(60, (float) ($tiers[0]['eur_total'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(5, (int) ($tiers[1]['group_size'] ?? 0), 0.001);
        $this->assertEqualsWithDelta(140, (float) ($tiers[1]['eur_total'] ?? 0), 0.001);
    }

    public function test_vendor_can_add_only_a_single_group_size_discount(): void
    {
        // The vendor wants to discount only pairs — a single "Group of 2" row.
        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->set('data.pricing.pricing_strategy', 'tiered')
            ->set('data.pricing.tiers', [
                ['group_size' => 2, 'tnd_total' => 180, 'eur_total' => 60],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $reloaded = Listing::find($this->draftListing->id);

        $tiers = array_values(data_get($reloaded->pricing, 'tiers') ?? []);
        $this->assertCount(1, $tiers);
        $this->assertEqualsWithDelta(2, (int) ($tiers[0]['group_size'] ?? 0), 0.001);
    }

    public function test_tiered_form_hydrates_existing_group_discounts(): void
    {
        $this->draftListing->pricing = [
            'pricing_model' => 'per_person',
            'pricing_strategy' => 'tiered',
            'person_types' => [
                ['key' => 'adult', 'label' => ['fr' => 'Adulte', 'en' => 'Adult'], 'tnd_price' => 100, 'eur_price' => 35],
            ],
            'tiers' => [
                ['group_size' => 2, 'tnd_total' => 180, 'eur_total' => 60],
                ['group_size' => 5, 'tnd_total' => 400, 'eur_total' => 140],
            ],
        ];
        $this->draftListing->save();

        Livewire::test(EditListing::class, ['record' => $this->draftListing->getKey()])
            ->assertFormSet(function (array $state) {
                $this->assertSame('tiered', data_get($state['pricing'], 'pricing_strategy'));
                $tiers = array_values(data_get($state['pricing'], 'tiers') ?? []);
                $this->assertCount(2, $tiers);
                $this->assertEqualsWithDelta(2, (int) ($tiers[0]['group_size'] ?? 0), 0.001);
            });
    }
}
