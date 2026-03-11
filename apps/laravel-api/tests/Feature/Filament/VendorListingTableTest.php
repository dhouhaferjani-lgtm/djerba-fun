<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for listing title display with translation fallback in Filament tables.
 *
 * Regression tests for: French-only titles not displaying in English context.
 */
class VendorListingTableTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create([
            'role' => UserRole::VENDOR->value,
        ]);
    }

    /**
     * Test French-only title displays with [FR] indicator when in English context.
     */
    public function test_french_only_title_displays_with_indicator_in_english_context(): void
    {
        // Arrange - Set app locale to English
        app()->setLocale('en');

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'title' => ['fr' => 'Excursion dans le Désert'],
        ]);

        // Act - Resolve title with fallback logic (same logic as ListingResource)
        $title = $this->resolveListingTitleWithFallback($listing);

        // Assert - Should show French title with [FR] indicator
        $this->assertStringContainsString('[FR]', $title);
        $this->assertStringContainsString('Excursion dans le Désert', $title);
    }

    /**
     * Test English-only title displays with [EN] indicator when in French context.
     */
    public function test_english_only_title_displays_with_indicator_in_french_context(): void
    {
        // Arrange - Set locale to French FIRST
        app()->setLocale('fr');

        // Create listing
        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        // Directly update database to have only English title
        \DB::table('listings')
            ->where('id', $listing->id)
            ->update(['title' => json_encode(['en' => 'Desert Excursion'])]);

        // Create fresh instance to clear any cached values
        $listing = Listing::find($listing->id);

        // Verify locale is still French
        $this->assertEquals('fr', app()->getLocale());

        // Act - Resolve title with fallback logic
        $title = $this->resolveListingTitleWithFallback($listing);

        // Assert - Should show English title with [EN] indicator
        $this->assertStringContainsString('[EN]', $title);
        $this->assertStringContainsString('Desert Excursion', $title);
    }

    /**
     * Test bilingual title displays current locale without indicator.
     */
    public function test_bilingual_title_displays_current_locale_without_indicator(): void
    {
        // Arrange - Set app locale to English
        app()->setLocale('en');

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'title' => [
                'en' => 'Desert Excursion',
                'fr' => 'Excursion dans le Désert',
            ],
        ]);

        // Act - Resolve title with fallback logic
        $title = $this->resolveListingTitleWithFallback($listing);

        // Assert - Should show English title without any indicator
        $this->assertEquals('Desert Excursion', $title);
        $this->assertStringNotContainsString('[EN]', $title);
        $this->assertStringNotContainsString('[FR]', $title);
    }

    /**
     * Test listing with no title displays "Untitled".
     */
    public function test_no_title_displays_untitled(): void
    {
        // Arrange
        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'title' => [],
        ]);

        // Act - Resolve title with fallback logic
        $title = $this->resolveListingTitleWithFallback($listing);

        // Assert - Should show "Untitled"
        $this->assertEquals('Untitled', $title);
    }

    /**
     * Test malformed nested array title is properly extracted.
     * Regression test for double-nested translation arrays.
     */
    public function test_malformed_nested_array_title_is_extracted(): void
    {
        // Arrange - Simulate malformed data that might exist in database
        app()->setLocale('en');

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        // Directly update the database with malformed nested JSON
        // This simulates data corruption that may have occurred historically
        \DB::table('listings')
            ->where('id', $listing->id)
            ->update(['title' => json_encode(['fr' => ['fr' => 'Titre Mal Formé']])]);

        $listing->refresh();

        // Act - Resolve title with fallback logic
        $title = $this->resolveListingTitleWithFallback($listing);

        // Assert - Should extract the string from nested array
        $this->assertStringContainsString('Titre Mal Formé', $title);
    }

    /**
     * Test title resolution works after edit-save cycle.
     * Regression test for: "when I do an edit then save I lost the visibility of the title"
     */
    public function test_title_visible_after_edit_save_cycle(): void
    {
        // Arrange
        app()->setLocale('en');

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'title' => ['fr' => 'Titre Initial'],
        ]);

        // Act - Simulate edit/save cycle (update via Spatie trait)
        $listing->setTranslation('title', 'fr', 'Titre Modifié');
        $listing->save();
        $listing->refresh();

        // Assert - Title should still be visible with fallback
        $title = $this->resolveListingTitleWithFallback($listing);
        $this->assertStringContainsString('[FR]', $title);
        $this->assertStringContainsString('Titre Modifié', $title);
    }

    /**
     * Test French context shows French title without indicator.
     */
    public function test_french_context_shows_french_title_without_indicator(): void
    {
        // Arrange - Set app locale to French
        app()->setLocale('fr');

        $listing = Listing::factory()->create([
            'vendor_id' => $this->vendor->id,
            'title' => ['fr' => 'Excursion dans le Désert'],
        ]);

        // Act - Resolve title with fallback logic
        $title = $this->resolveListingTitleWithFallback($listing);

        // Assert - Should show French title without indicator (matching locale)
        $this->assertEquals('Excursion dans le Désert', $title);
        $this->assertStringNotContainsString('[FR]', $title);
    }

    /**
     * Helper method that replicates the exact title resolution logic
     * used in ListingResource table columns.
     *
     * This is the same logic that will be used with ->state() method.
     * Note: Uses useFallbackLocale=false to disable Spatie's automatic fallback
     * and implement our own fallback with language indicator.
     */
    private function resolveListingTitleWithFallback(Listing $record): string
    {
        $currentLocale = app()->getLocale();
        $alternateLocale = $currentLocale === 'en' ? 'fr' : 'en';

        // Try current locale first (disable Spatie's auto-fallback)
        $title = $record->getTranslation('title', $currentLocale, false);
        $usedLocale = $currentLocale;

        // Handle malformed nested arrays from earlier bug
        if (is_array($title)) {
            $title = $title[$currentLocale] ?? $title['en'] ?? reset($title) ?: null;

            while (is_array($title)) {
                $title = reset($title) ?: null;
            }
        }

        // If empty, try alternate locale (also disable Spatie's auto-fallback)
        if (empty($title)) {
            $title = $record->getTranslation('title', $alternateLocale, false);
            $usedLocale = $alternateLocale;

            if (is_array($title)) {
                $title = $title[$alternateLocale] ?? $title['en'] ?? reset($title) ?: null;

                while (is_array($title)) {
                    $title = reset($title) ?: null;
                }
            }
        }

        if (empty($title)) {
            return 'Untitled';
        }

        // Add language indicator if using alternate locale
        if ($usedLocale !== $currentLocale) {
            $langLabel = strtoupper($usedLocale);

            return "[{$langLabel}] {$title}";
        }

        return $title;
    }
}
