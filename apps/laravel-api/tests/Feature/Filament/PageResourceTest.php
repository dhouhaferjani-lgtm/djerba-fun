<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\PageResource;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Filament Page resource.
 *
 * These tests verify the Page admin panel functionality including
 * CRUD operations and destination-style content sections.
 */
class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for authentication
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
    }

    /**
     * Test resource has correct navigation settings.
     */
    public function test_resource_navigation_settings(): void
    {
        // Assert
        $this->assertEquals('Content', PageResource::getNavigationGroup());
        $this->assertEquals('Pages', PageResource::getPluralModelLabel());
    }

    /**
     * Test resource uses custom Page model.
     */
    public function test_resource_uses_custom_page_model(): void
    {
        // Assert
        $this->assertEquals(Page::class, PageResource::getModel());
    }

    /**
     * Test page can be created with basic fields.
     */
    public function test_can_create_page_with_basic_fields(): void
    {
        // Act
        $page = Page::create([
            'title' => ['en' => 'Test Page', 'fr' => 'Page de Test'],
            'slug' => ['en' => 'test-page', 'fr' => 'page-de-test'],
            'intro' => ['en' => 'Test intro', 'fr' => 'Intro de test'],
        ]);

        // Assert
        $this->assertNotNull($page->id);
        $this->assertEquals('Test Page', $page->getTranslation('title', 'en'));
        $this->assertEquals('Page de Test', $page->getTranslation('title', 'fr'));
    }

    /**
     * Test page can be created with destination-style description.
     */
    public function test_can_create_page_with_description_columns(): void
    {
        // Act
        $page = Page::create([
            'title' => ['en' => 'Test', 'fr' => 'Test'],
            'slug' => ['en' => 'test', 'fr' => 'test'],
            'description_en' => '<p>English description</p>',
            'description_fr' => '<p>Description en français</p>',
        ]);

        // Assert
        $this->assertEquals('<p>English description</p>', $page->getDescription('en'));
        $this->assertEquals('<p>Description en français</p>', $page->getDescription('fr'));
    }

    /**
     * Test page can be created with SEO columns.
     */
    public function test_can_create_page_with_seo_columns(): void
    {
        // Act
        $page = Page::create([
            'title' => ['en' => 'SEO Test', 'fr' => 'Test SEO'],
            'slug' => ['en' => 'seo-test', 'fr' => 'test-seo'],
            'seo_title_en' => 'SEO Title EN',
            'seo_title_fr' => 'Titre SEO FR',
            'seo_description_en' => 'SEO description English',
            'seo_description_fr' => 'Description SEO français',
            'seo_text_en' => '<p>SEO text English</p>',
            'seo_text_fr' => '<p>Texte SEO français</p>',
        ]);

        // Assert
        $this->assertEquals('SEO Title EN', $page->getSeoTitleForLocale('en'));
        $this->assertEquals('Titre SEO FR', $page->getSeoTitleForLocale('fr'));
        $this->assertEquals('SEO description English', $page->getSeoDescriptionForLocale('en'));
        $this->assertEquals('Description SEO français', $page->getSeoDescriptionForLocale('fr'));
        $this->assertEquals('<p>SEO text English</p>', $page->getSeoTextForLocale('en'));
        $this->assertEquals('<p>Texte SEO français</p>', $page->getSeoTextForLocale('fr'));
    }

    /**
     * Test page can be created with highlights.
     */
    public function test_can_create_page_with_highlights(): void
    {
        // Arrange
        $highlights = [
            [
                'icon' => 'sun',
                'title_en' => 'Beautiful Weather',
                'title_fr' => 'Beau Temps',
                'description_en' => 'Sunny all year round',
                'description_fr' => 'Ensoleillé toute l\'année',
            ],
            [
                'icon' => 'palmtree',
                'title_en' => 'Beach Life',
                'title_fr' => 'Vie de Plage',
                'description_en' => 'Amazing beaches',
                'description_fr' => 'Plages magnifiques',
            ],
        ];

        // Act
        $page = Page::create([
            'title' => ['en' => 'Highlights Test', 'fr' => 'Test Highlights'],
            'slug' => ['en' => 'highlights-test', 'fr' => 'test-highlights'],
            'highlights' => $highlights,
        ]);

        // Assert
        $this->assertCount(2, $page->highlights);
        $this->assertEquals('sun', $page->highlights[0]['icon']);

        // Check localized highlights
        $enHighlights = $page->getLocalizedHighlights('en');
        $this->assertEquals('Beautiful Weather', $enHighlights[0]['title']);
        $this->assertEquals('Beach Life', $enHighlights[1]['title']);

        $frHighlights = $page->getLocalizedHighlights('fr');
        $this->assertEquals('Beau Temps', $frHighlights[0]['title']);
        $this->assertEquals('Vie de Plage', $frHighlights[1]['title']);
    }

    /**
     * Test page can be created with key facts.
     */
    public function test_can_create_page_with_key_facts(): void
    {
        // Arrange
        $keyFacts = [
            [
                'icon' => 'users',
                'label_en' => 'Population',
                'label_fr' => 'Population',
                'value' => '163,000',
            ],
            [
                'icon' => 'map',
                'label_en' => 'Area',
                'label_fr' => 'Superficie',
                'value' => '514 km²',
            ],
        ];

        // Act
        $page = Page::create([
            'title' => ['en' => 'Key Facts Test', 'fr' => 'Test Key Facts'],
            'slug' => ['en' => 'key-facts-test', 'fr' => 'test-key-facts'],
            'key_facts' => $keyFacts,
        ]);

        // Assert
        $this->assertCount(2, $page->key_facts);

        // Check localized key facts
        $enFacts = $page->getLocalizedKeyFacts('en');
        $this->assertEquals('Population', $enFacts[0]['label']);
        $this->assertEquals('163,000', $enFacts[0]['value']);
        $this->assertEquals('Area', $enFacts[1]['label']);

        $frFacts = $page->getLocalizedKeyFacts('fr');
        $this->assertEquals('Superficie', $frFacts[1]['label']);
    }

    /**
     * Test page can be created with gallery.
     */
    public function test_can_create_page_with_gallery(): void
    {
        // Arrange
        $gallery = [
            [
                'image' => '/storage/gallery/beach.jpg',
                'alt_en' => 'Sandy beach',
                'alt_fr' => 'Plage de sable',
                'caption_en' => 'Beautiful sunset',
                'caption_fr' => 'Beau coucher de soleil',
            ],
            [
                'image' => '/storage/gallery/palm.jpg',
                'alt_en' => 'Palm trees',
                'alt_fr' => 'Palmiers',
                'caption_en' => 'Tropical paradise',
                'caption_fr' => 'Paradis tropical',
            ],
        ];

        // Act
        $page = Page::create([
            'title' => ['en' => 'Gallery Test', 'fr' => 'Test Galerie'],
            'slug' => ['en' => 'gallery-test', 'fr' => 'test-galerie'],
            'gallery' => $gallery,
        ]);

        // Assert
        $this->assertCount(2, $page->gallery);

        // Check localized gallery
        $enGallery = $page->getLocalizedGallery('en');
        $this->assertEquals('/storage/gallery/beach.jpg', $enGallery[0]['image']);
        $this->assertEquals('Sandy beach', $enGallery[0]['alt']);
        $this->assertEquals('Beautiful sunset', $enGallery[0]['caption']);

        $frGallery = $page->getLocalizedGallery('fr');
        $this->assertEquals('Plage de sable', $frGallery[0]['alt']);
        $this->assertEquals('Beau coucher de soleil', $frGallery[0]['caption']);
    }

    /**
     * Test page can be created with points of interest.
     */
    public function test_can_create_page_with_points_of_interest(): void
    {
        // Arrange
        $pois = [
            [
                'name_en' => 'El Ghriba Synagogue',
                'name_fr' => 'Synagogue El Ghriba',
                'description_en' => 'Historic synagogue',
                'description_fr' => 'Synagogue historique',
            ],
            [
                'name_en' => 'Houmt Souk',
                'name_fr' => 'Houmt Souk',
                'description_en' => 'Traditional market',
                'description_fr' => 'Marché traditionnel',
            ],
        ];

        // Act
        $page = Page::create([
            'title' => ['en' => 'POI Test', 'fr' => 'Test POI'],
            'slug' => ['en' => 'poi-test', 'fr' => 'test-poi'],
            'points_of_interest' => $pois,
        ]);

        // Assert
        $this->assertCount(2, $page->points_of_interest);

        // Check localized POIs
        $enPois = $page->getLocalizedPointsOfInterest('en');
        $this->assertEquals('El Ghriba Synagogue', $enPois[0]['name']);
        $this->assertEquals('Historic synagogue', $enPois[0]['description']);

        $frPois = $page->getLocalizedPointsOfInterest('fr');
        $this->assertEquals('Synagogue El Ghriba', $frPois[0]['name']);
        $this->assertEquals('Synagogue historique', $frPois[0]['description']);
    }

    /**
     * Test page can be created with link field.
     */
    public function test_can_create_page_with_link(): void
    {
        // Act
        $page = Page::create([
            'title' => ['en' => 'Link Test', 'fr' => 'Test Lien'],
            'slug' => ['en' => 'link-test', 'fr' => 'test-lien'],
            'link' => '/explore-djerba',
        ]);

        // Assert
        $this->assertEquals('/explore-djerba', $page->link);
    }

    /**
     * Test page fillable array includes all destination-style fields.
     */
    public function test_page_fillable_includes_destination_fields(): void
    {
        // Arrange
        $page = new Page();
        $fillable = $page->getFillable();

        // Assert - destination-style fields are fillable
        $this->assertContains('description_en', $fillable);
        $this->assertContains('description_fr', $fillable);
        $this->assertContains('link', $fillable);
        $this->assertContains('seo_title_en', $fillable);
        $this->assertContains('seo_title_fr', $fillable);
        $this->assertContains('seo_description_en', $fillable);
        $this->assertContains('seo_description_fr', $fillable);
        $this->assertContains('seo_text_en', $fillable);
        $this->assertContains('seo_text_fr', $fillable);
        $this->assertContains('highlights', $fillable);
        $this->assertContains('key_facts', $fillable);
        $this->assertContains('gallery', $fillable);
        $this->assertContains('points_of_interest', $fillable);
    }

    /**
     * Test page casts JSON columns correctly.
     */
    public function test_page_casts_json_columns(): void
    {
        // Arrange
        $page = new Page();
        $casts = $page->getCasts();

        // Assert - JSON columns are cast to array
        $this->assertEquals('array', $casts['highlights']);
        $this->assertEquals('array', $casts['key_facts']);
        $this->assertEquals('array', $casts['gallery']);
        $this->assertEquals('array', $casts['points_of_interest']);
    }

    /**
     * Test page returns empty arrays for null JSON fields.
     */
    public function test_page_returns_empty_arrays_for_null_json_fields(): void
    {
        // Arrange
        $page = Page::create([
            'title' => ['en' => 'Empty Test', 'fr' => 'Test Vide'],
            'slug' => ['en' => 'empty-test', 'fr' => 'test-vide'],
        ]);

        // Assert - localized methods return empty arrays
        $this->assertEquals([], $page->getLocalizedHighlights('en'));
        $this->assertEquals([], $page->getLocalizedKeyFacts('en'));
        $this->assertEquals([], $page->getLocalizedGallery('en'));
        $this->assertEquals([], $page->getLocalizedPointsOfInterest('en'));
    }

    /**
     * Test page locale methods fallback gracefully.
     */
    public function test_page_locale_methods_fallback_gracefully(): void
    {
        // Arrange - Create page with only English content
        $page = Page::create([
            'title' => ['en' => 'English Only', 'fr' => 'Anglais Seulement'],
            'slug' => ['en' => 'english-only', 'fr' => 'anglais-seulement'],
            'description_en' => 'English description',
            'seo_title_en' => 'English SEO Title',
        ]);

        // Act - Request French content
        $frDescription = $page->getDescription('fr');
        $frSeoTitle = $page->getSeoTitleForLocale('fr');

        // Assert - Returns null for missing translations
        $this->assertNull($frDescription);
        $this->assertNull($frSeoTitle);

        // English should still work
        $this->assertEquals('English description', $page->getDescription('en'));
        $this->assertEquals('English SEO Title', $page->getSeoTitleForLocale('en'));
    }

    /**
     * Test page with code can be looked up.
     */
    public function test_page_with_code_can_be_looked_up(): void
    {
        // Arrange
        $page = Page::create([
            'code' => 'ABOUT',
            'title' => ['en' => 'About Us', 'fr' => 'À Propos'],
            'slug' => ['en' => 'about', 'fr' => 'a-propos'],
        ]);

        // Act
        $foundPage = Page::where('code', 'ABOUT')->first();

        // Assert
        $this->assertNotNull($foundPage);
        $this->assertEquals($page->id, $foundPage->id);
    }

    /**
     * Test published page appears in published scope.
     */
    public function test_published_page_appears_in_scope(): void
    {
        // Arrange
        $published = Page::create([
            'title' => ['en' => 'Published', 'fr' => 'Publié'],
            'slug' => ['en' => 'published', 'fr' => 'publie'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        $unpublished = Page::create([
            'title' => ['en' => 'Unpublished', 'fr' => 'Non Publié'],
            'slug' => ['en' => 'unpublished', 'fr' => 'non-publie'],
            'publishing_begins_at' => now()->addDay(),
        ]);

        // Act
        $publishedPages = Page::query()
            ->where(function ($query) {
                $query->whereNull('publishing_begins_at')
                    ->orWhere('publishing_begins_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('publishing_ends_at')
                    ->orWhere('publishing_ends_at', '>=', now());
            })
            ->get();

        // Assert
        $this->assertTrue($publishedPages->contains($published));
        $this->assertFalse($publishedPages->contains($unpublished));
    }

    /**
     * Test page with all destination fields can be saved and retrieved.
     */
    public function test_can_save_and_retrieve_full_destination_page(): void
    {
        // Arrange - Create a fully populated page
        $page = Page::create([
            'code' => 'DJERBA',
            'title' => ['en' => 'Discover Djerba', 'fr' => 'Découvrir Djerba'],
            'slug' => ['en' => 'djerba', 'fr' => 'djerba'],
            'intro' => ['en' => 'The island of dreams', 'fr' => 'L\'île des rêves'],
            'description_en' => '<p>English description</p>',
            'description_fr' => '<p>Description française</p>',
            'link' => '/explore',
            'seo_title_en' => 'Djerba Island - Tunisia',
            'seo_title_fr' => 'Île de Djerba - Tunisie',
            'seo_description_en' => 'Discover the beautiful island',
            'seo_description_fr' => 'Découvrez la belle île',
            'seo_text_en' => '<p>SEO content</p>',
            'seo_text_fr' => '<p>Contenu SEO</p>',
            'highlights' => [
                ['icon' => 'sun', 'title_en' => 'Sun', 'title_fr' => 'Soleil', 'description_en' => 'Sunny', 'description_fr' => 'Ensoleillé'],
            ],
            'key_facts' => [
                ['icon' => 'map', 'label_en' => 'Area', 'label_fr' => 'Superficie', 'value' => '514 km²'],
            ],
            'gallery' => [
                ['image' => '/img/beach.jpg', 'alt_en' => 'Beach', 'alt_fr' => 'Plage', 'caption_en' => 'Nice', 'caption_fr' => 'Beau'],
            ],
            'points_of_interest' => [
                ['name_en' => 'Synagogue', 'name_fr' => 'Synagogue', 'description_en' => 'Historic', 'description_fr' => 'Historique'],
            ],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act - Retrieve the page fresh
        $retrieved = Page::find($page->id);

        // Assert
        $this->assertEquals('DJERBA', $retrieved->code);
        $this->assertEquals('Discover Djerba', $retrieved->getTranslation('title', 'en'));
        $this->assertEquals('<p>English description</p>', $retrieved->getDescription('en'));
        $this->assertEquals('/explore', $retrieved->link);
        $this->assertEquals('Djerba Island - Tunisia', $retrieved->getSeoTitleForLocale('en'));
        $this->assertCount(1, $retrieved->getLocalizedHighlights('en'));
        $this->assertCount(1, $retrieved->getLocalizedKeyFacts('en'));
        $this->assertCount(1, $retrieved->getLocalizedGallery('en'));
        $this->assertCount(1, $retrieved->getLocalizedPointsOfInterest('en'));
    }
}
