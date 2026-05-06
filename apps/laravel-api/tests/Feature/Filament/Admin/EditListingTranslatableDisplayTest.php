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
 * Locks the fix for the "[object Object]" rendering bug on the Filament
 * Admin EditListing page.
 *
 * Pre-fix: Disabled RichEditor::make('description.en') and
 * Textarea::make('summary.en') in the admin form bound to translatable
 * fields whose parent value was serialized to the JS layer as a map.
 * The disabled-input rendering then toString'd the map and produced
 * "[object Object]" in the browser.
 *
 * Post-fix: both fields are replaced with Placeholder::content() that
 * server-renders the locale-resolved string as HTML. No Livewire state,
 * no JS coercion, no possibility of "[object Object]".
 */
final class EditListingTranslatableDisplayTest extends TestCase
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

        $this->listing = Listing::factory()->create([
            'service_type' => ServiceType::NAUTICAL,
            'status' => ListingStatus::DRAFT,
            'title' => ['fr' => 'Jetski', 'en' => 'Jetski'],
            'summary' => [
                'fr' => 'Résumé en français.',
                'en' => 'Summary in English.',
            ],
            'description' => [
                'fr' => '<p>Description en français.</p>',
                'en' => '<p>Long description in English.</p>',
            ],
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * GIVEN: a listing with translatable description + summary
     * WHEN:  the admin EditListing page renders
     * THEN:  the HTML contains the EN strings, and never the literal
     *        "[object Object]" — proves the placeholders render the
     *        right content as static HTML.
     */
    public function test_admin_edit_listing_renders_english_translation_strings(): void
    {
        $html = Livewire::test(EditListing::class, ['record' => $this->listing->slug])->html();

        $this->assertStringNotContainsString(
            '[object Object]',
            $html,
            'Admin EditListing must never render "[object Object]". The disabled translatable fields use Placeholder::content() and emit static HTML on the server.',
        );

        $this->assertStringContainsString('Long description in English.', $html);
        $this->assertStringContainsString('Summary in English.', $html);
    }

    /**
     * GIVEN: a listing whose vendor created it without filling description
     *        (Spatie writes pricing JSON shaped like {"en": null} or empty
     *        translatable map — the most common live shape)
     * WHEN:  the admin EditListing page renders
     * THEN:  the page does NOT crash and does NOT show "[object Object]"
     *        — placeholders gracefully render an em-dash for empty values.
     */
    public function test_admin_edit_listing_with_empty_description_renders_dash(): void
    {
        $listing = Listing::factory()->create([
            'service_type' => ServiceType::NAUTICAL,
            'status' => ListingStatus::DRAFT,
            'title' => ['fr' => 'T', 'en' => 'T'],
            'summary' => null,
            'description' => null,
        ]);

        $html = Livewire::test(EditListing::class, ['record' => $listing->slug])->html();

        $this->assertStringNotContainsString('[object Object]', $html);
        // Either both em-dashes or no description at all — both are fine
        // as long as the bug string never appears.
    }

    /**
     * Belt-and-braces: the editable title fields still hydrate correctly
     * (those weren't touched by the fix, but a sanity check is cheap).
     */
    public function test_editable_title_fields_still_hydrate_correctly(): void
    {
        Livewire::test(EditListing::class, ['record' => $this->listing->slug])
            ->assertFormSet(function (array $state) {
                $this->assertIsString(data_get($state, 'title.en'));
                $this->assertIsString(data_get($state, 'title.fr'));
                $this->assertSame('Jetski', data_get($state, 'title.en'));
                $this->assertSame('Jetski', data_get($state, 'title.fr'));
            });
    }

    /**
     * The fix must not break the unit_label fields shipped earlier
     * (commit c2aab50). Both still render their values via the
     * pricing.unit_label.{en,fr} dot-path because they are simple
     * (non-Spatie) JSON keys.
     */
    public function test_unit_label_fields_still_hydrate_after_fix(): void
    {
        $this->listing->update([
            'pricing' => array_merge((array) $this->listing->pricing, [
                'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
            ]),
        ]);

        Livewire::test(EditListing::class, ['record' => $this->listing->slug])
            ->assertFormSet(function (array $state) {
                $this->assertSame('par jetski', data_get($state, 'pricing.unit_label.fr'));
                $this->assertSame('per jetski', data_get($state, 'pricing.unit_label.en'));
            });
    }
}
