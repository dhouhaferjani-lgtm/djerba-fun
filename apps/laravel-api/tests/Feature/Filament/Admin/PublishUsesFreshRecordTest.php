<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\ListingResource\Pages\EditListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression test for: admin publish reads stale Livewire-held record.
 *
 * Real client scenario (2026-05-06, listing slug `per-machine` on prod):
 *   1. Vendor creates a fresh DRAFT tour listing with empty pricing.
 *   2. Admin opens /admin/listings/{slug}/edit — Livewire mounts and
 *      hydrates $this->record with pricing = []. Admin does NOT save yet.
 *   3. In another tab, the vendor opens the wizard, fills step 5
 *      (person_types), and saves. The DB row now has a valid
 *      pricing.person_types[] array.
 *   4. Admin returns to the still-mounted admin tab (no refresh) and
 *      sets Status = PUBLISHED, clicks Save.
 *
 * Without the fix, mutateFormDataBeforeSave reads $this->record->pricing
 * — the stale empty array from step 2 — and the publish validator
 * rejects with "Pricing information is required" even though the DB
 * already has the required pricing.
 *
 * The fix is a single $this->record->refresh() call at the top of
 * mutateFormDataBeforeSave that forces a fresh DB read before any
 * validator runs.
 */
final class PublishUsesFreshRecordTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        // Fresh DRAFT tour listing with empty pricing — the exact state of
        // the user's `per-machine` listing on prod at the moment they first
        // opened admin EditListing.
        $this->listing = Listing::factory()->draft()->create([
            'service_type' => ServiceType::TOUR,
            'pricing' => [],
        ]);
    }

    /**
     * GIVEN: admin EditListing component mounted with stale empty pricing.
     * WHEN:  the DB pricing is updated out-of-band (simulating the vendor
     *        wizard save in another tab) and admin then saves with
     *        status=PUBLISHED without re-mounting.
     * THEN:  publish succeeds, no "Cannot Publish" notification fires,
     *        and the listing is actually PUBLISHED.
     */
    public function test_publish_reads_fresh_pricing_after_out_of_band_vendor_save(): void
    {
        $this->actingAs($this->admin);

        // Step 1 — admin mounts the page. $this->record is hydrated with
        // pricing = [].
        $component = Livewire::test(EditListing::class, [
            'record' => $this->listing->getRouteKey(),
        ])->assertSuccessful();

        // Step 2 — vendor saves wizard step 5 in another tab. We simulate
        // that by writing person_types directly to the DB. The mounted
        // component above does NOT see this change in its $this->record.
        Listing::query()->whereKey($this->listing->id)->update([
            'pricing' => [
                'pricing_model' => 'per_person',
                'person_types' => [
                    [
                        'key' => 'jetski',
                        'label' => ['en' => 'Jetski', 'fr' => 'Jet-ski'],
                        'tnd_price' => 155,
                        'eur_price' => 30,
                        'min_age' => 18,
                        'min_quantity' => 1,
                    ],
                ],
            ],
        ]);

        // Sanity: the DB really has the pricing now.
        $this->assertNotEmpty(
            $this->listing->fresh()->pricing['person_types'] ?? null,
            'Out-of-band pricing update did not land in the DB — test setup is wrong.'
        );

        // Step 3 — admin clicks Save with status=PUBLISHED in the same
        // still-mounted Livewire component.
        $component
            ->set('data.status', ListingStatus::PUBLISHED->value)
            ->call('save')
            ->assertHasNoFormErrors();

        // Step 4 — assert the publish actually went through. Without the
        // refresh() fix, mutateFormDataBeforeSave reverts data.status back
        // to draft (line 111) and the listing stays DRAFT.
        $this->assertSame(
            ListingStatus::PUBLISHED,
            $this->listing->fresh()->status,
            'Listing did not transition to PUBLISHED — admin still reading stale $this->record->pricing.'
        );

        $this->assertNotNull(
            $this->listing->fresh()->published_at,
            'published_at was not set even though status moved to PUBLISHED.'
        );
    }

    /**
     * Negative regression — when the DB genuinely has no pricing at the
     * moment of save, publish must STILL be rejected. This guards against
     * an over-eager refresh() somehow short-circuiting the validator.
     */
    public function test_publish_still_rejects_when_db_pricing_is_truly_empty(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditListing::class, [
            'record' => $this->listing->getRouteKey(),
        ])
            ->assertSuccessful()
            ->set('data.status', ListingStatus::PUBLISHED->value)
            ->call('save');

        // Status must NOT have moved to PUBLISHED — the validator
        // correctly rejected the empty-pricing publish attempt.
        $this->assertSame(
            ListingStatus::DRAFT,
            $this->listing->fresh()->status,
            'Listing was published even though pricing is genuinely empty — validator regression.'
        );
    }
}
