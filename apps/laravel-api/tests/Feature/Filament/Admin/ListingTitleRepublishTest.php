<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\ListingResource\Pages\EditListing;
use App\Filament\Vendor\Resources\ListingResource\Pages\EditListing as VendorEditListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Client ticket (live djerbafun.com): a published listing is set to DRAFT, its
 * FRENCH title is edited in the vendor panel, then re-published from the admin
 * panel. The public French site keeps showing the OLD title, stuck — while the
 * slug reflects the new title.
 *
 * These BDD tests isolate the two save hops to prove WHICH one drops the
 * freshly-edited French title:
 *   - vendor hop: vendor EditListing save persists title.fr
 *   - admin  hop: admin EditListing republish must NOT revert title.fr
 */
final class ListingTitleRepublishTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $this->vendor = User::factory()->create(['role' => UserRole::VENDOR->value]);
    }

    /**
     * Build a DRAFT tour listing that is otherwise fully valid for publishing
     * (real person_types pricing, both summaries, a location) with a known
     * OLD title in both locales.
     */
    private function makePublishableDraft(): Listing
    {
        return Listing::factory()->draft()->create([
            'vendor_id' => $this->vendor->id,
            'service_type' => ServiceType::TOUR,
            'title' => ['en' => 'Old EN Title', 'fr' => 'Ancien Titre FR'],
            'summary' => ['en' => 'Old summary', 'fr' => 'Ancien résumé'],
            'pricing' => [
                'pricing_model' => 'per_person',
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
                        'tnd_price' => 100,
                        'eur_price' => 30,
                        'min_quantity' => 1,
                    ],
                ],
            ],
        ]);
    }

    /**
     * MOUNT.
     *
     * GIVEN an existing listing with both locales set, WHEN the vendor opens
     * the edit form, THEN every per-locale field (title.en/fr, summary.en/fr)
     * is populated.
     *
     * Regression for a bug found via browser testing: the Spatie Translatable
     * plugin hydrates translatable attributes with only the ACTIVE locale's
     * STRING value, so the explicit per-locale fields rendered EMPTY — the
     * vendor could not see/edit existing translations, and the subsequent save
     * dropped the untouched locale.
     */
    public function test_vendor_edit_form_populates_both_locales(): void
    {
        $this->actingAs($this->vendor);

        $listing = $this->makePublishableDraft();

        $component = Livewire::test(VendorEditListing::class, ['record' => $listing->getRouteKey()])
            ->assertSuccessful();

        $this->assertSame('Old EN Title', $component->get('data.title.en'));
        $this->assertSame('Ancien Titre FR', $component->get('data.title.fr'));
        $this->assertSame('Old summary', $component->get('data.summary.en'));
        $this->assertSame('Ancien résumé', $component->get('data.summary.fr'));
    }

    /**
     * VENDOR HOP.
     *
     * GIVEN a draft listing, WHEN the vendor edits the French title and saves,
     * THEN the new French title is persisted and the English title is intact.
     */
    public function test_vendor_edit_persists_french_title(): void
    {
        $this->actingAs($this->vendor);

        $listing = $this->makePublishableDraft();

        Livewire::test(VendorEditListing::class, ['record' => $listing->getRouteKey()])
            ->assertSuccessful()
            ->set('data.title.fr', 'Nouveau Titre FR')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'Nouveau Titre FR',
            $listing->fresh()->getTranslation('title', 'fr'),
            'Vendor save did not persist the edited French title.'
        );
        $this->assertSame(
            'Old EN Title',
            $listing->fresh()->getTranslation('title', 'en'),
            'Vendor save clobbered the untouched English title.'
        );
    }

    /**
     * ADMIN HOP (the reported scenario).
     *
     * GIVEN the admin EditListing component is mounted while the DB still holds
     *       the OLD French title,
     * WHEN  the vendor edits the French title out-of-band (another tab) and the
     *       admin then republishes (status DRAFT -> PUBLISHED) from the still-
     *       mounted component,
     * THEN  the vendor's new French title must survive (admin must not write a
     *       stale title snapshot back over it).
     */
    public function test_admin_republish_preserves_vendor_edited_french_title(): void
    {
        $this->actingAs($this->admin);

        $listing = $this->makePublishableDraft();

        // Admin mounts the edit page — data.title.* hydrates with the OLD title.
        $component = Livewire::test(EditListing::class, ['record' => $listing->getRouteKey()])
            ->assertSuccessful();

        // Vendor edits the French title in another tab (simulated as a direct,
        // out-of-band DB write the mounted admin component cannot see).
        DB::table('listings')->where('id', $listing->id)->update([
            'title' => json_encode(['en' => 'Old EN Title', 'fr' => 'Nouveau Titre FR']),
        ]);

        $this->assertSame(
            'Nouveau Titre FR',
            $listing->fresh()->getTranslation('title', 'fr'),
            'Out-of-band French title update did not land — test setup is wrong.'
        );

        // Admin republishes from the still-mounted component.
        $component
            ->set('data.status', ListingStatus::PUBLISHED->value)
            ->call('save')
            ->assertHasNoFormErrors();

        // The listing must actually publish...
        $this->assertSame(
            ListingStatus::PUBLISHED,
            $listing->fresh()->status,
            'Listing did not transition to PUBLISHED.'
        );

        // ...and the vendor's freshly-edited French title must NOT be reverted.
        $this->assertSame(
            'Nouveau Titre FR',
            $listing->fresh()->getTranslation('title', 'fr'),
            'Admin republish reverted the vendor-edited French title to the stale form snapshot.'
        );
    }

    /**
     * END-TO-END / cache: the public French listing endpoint must reflect a
     * title change immediately, not after the 5-minute show cache TTL.
     *
     * Guards the secondary gap where ListingController::show cached the
     * payload with no invalidation on update.
     */
    public function test_public_french_endpoint_reflects_title_update_immediately(): void
    {
        $listing = Listing::factory()->published()->create([
            'title' => ['en' => 'Cache EN', 'fr' => 'Titre Cache FR'],
        ]);

        // Prime the per-listing show cache for the French locale.
        $this->getJson('/api/v1/listings/' . $listing->slug, ['Accept-Language' => 'fr'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Titre Cache FR');

        // Correct the French title (as the vendor edit now does).
        $listing->setTranslation('title', 'fr', 'Titre Cache FR v2');
        $listing->save();

        // Must be served fresh — the saved hook busted the show cache.
        $this->getJson('/api/v1/listings/' . $listing->slug, ['Accept-Language' => 'fr'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Titre Cache FR v2');
    }
}
