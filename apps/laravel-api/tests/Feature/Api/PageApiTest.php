<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the CMS pages API endpoints.
 *
 * These tests verify the /api/v1/pages endpoints return
 * correctly structured and localized page data including
 * destination-style content sections.
 */
class PageApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test pages index endpoint returns expected structure.
     */
    public function test_pages_index_returns_list_of_pages(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Test Page', 'fr' => 'Page de Test'],
            'slug' => ['en' => 'test-page', 'fr' => 'page-de-test'],
            'intro' => ['en' => 'Test intro', 'fr' => 'Intro de test'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'title',
                    ],
                ],
            ]);
    }

    /**
     * Test page show endpoint returns expected structure.
     */
    public function test_page_show_returns_expected_structure(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'About Us', 'fr' => 'À Propos'],
            'slug' => ['en' => 'about', 'fr' => 'a-propos'],
            'intro' => ['en' => 'Learn about us', 'fr' => 'En savoir plus'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/about?locale=en');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'slug',
                    'title',
                    'intro',
                    'description',
                    'link',
                    'heroImage',
                    'heroImageCopyright',
                    'heroImageTitle',
                    'heroCallToActions',
                    'seoTitle',
                    'seoDescription',
                    'seoText',
                    'highlights',
                    'keyFacts',
                    'gallery',
                    'pointsOfInterest',
                    'contentBlocks',
                    'createdAt',
                    'updatedAt',
                ],
            ]);
    }

    /**
     * Test page show returns localized content for French.
     */
    public function test_page_show_returns_french_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Contact', 'fr' => 'Nous Contacter'],
            'slug' => ['en' => 'contact', 'fr' => 'nous-contacter'],
            'intro' => ['en' => 'Get in touch', 'fr' => 'Prenez contact'],
            'description_en' => '<p>English description</p>',
            'description_fr' => '<p>Description en français</p>',
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/nous-contacter?locale=fr');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Nous Contacter')
            ->assertJsonPath('data.slug', 'nous-contacter')
            ->assertJsonPath('data.intro', 'Prenez contact')
            ->assertJsonPath('data.description', '<p>Description en français</p>');
    }

    /**
     * Test page show returns localized content for English.
     */
    public function test_page_show_returns_english_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Contact', 'fr' => 'Nous Contacter'],
            'slug' => ['en' => 'contact', 'fr' => 'nous-contacter'],
            'intro' => ['en' => 'Get in touch', 'fr' => 'Prenez contact'],
            'description_en' => '<p>English description</p>',
            'description_fr' => '<p>Description en français</p>',
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/contact?locale=en');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Contact')
            ->assertJsonPath('data.slug', 'contact')
            ->assertJsonPath('data.intro', 'Get in touch')
            ->assertJsonPath('data.description', '<p>English description</p>');
    }

    /**
     * Test page show returns 404 for non-existent page.
     */
    public function test_page_show_returns_404_for_non_existent_page(): void
    {
        // Act
        $response = $this->getJson('/api/v1/pages/non-existent-page?locale=en');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test page show by code returns correct page.
     */
    public function test_page_show_by_code_returns_correct_page(): void
    {
        // Arrange
        Page::create([
            'code' => 'HOME',
            'title' => ['en' => 'Home', 'fr' => 'Accueil'],
            'slug' => ['en' => 'home', 'fr' => 'accueil'],
            'intro' => ['en' => 'Welcome', 'fr' => 'Bienvenue'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/code/HOME?locale=en');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'HOME')
            ->assertJsonPath('data.title', 'Home');
    }

    /**
     * Test page with highlights returns localized highlights.
     */
    public function test_page_with_highlights_returns_localized_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Djerba Island', 'fr' => 'Île de Djerba'],
            'slug' => ['en' => 'djerba-island', 'fr' => 'ile-de-djerba'],
            'publishing_begins_at' => now()->subDay(),
            'highlights' => [
                [
                    'icon' => 'sun',
                    'title_en' => 'Beautiful Beaches',
                    'title_fr' => 'Belles Plages',
                    'description_en' => 'Crystal clear waters',
                    'description_fr' => 'Eaux cristallines',
                ],
                [
                    'icon' => 'palmtree',
                    'title_en' => 'Tropical Paradise',
                    'title_fr' => 'Paradis Tropical',
                    'description_en' => 'Palm trees everywhere',
                    'description_fr' => 'Palmiers partout',
                ],
            ],
        ]);

        // Act - French locale
        $responseFr = $this->getJson('/api/v1/pages/ile-de-djerba?locale=fr');

        // Assert - French content
        $responseFr->assertStatus(200)
            ->assertJsonPath('data.highlights.0.icon', 'sun')
            ->assertJsonPath('data.highlights.0.title', 'Belles Plages')
            ->assertJsonPath('data.highlights.0.description', 'Eaux cristallines')
            ->assertJsonPath('data.highlights.1.title', 'Paradis Tropical');

        // Act - English locale
        $responseEn = $this->getJson('/api/v1/pages/djerba-island?locale=en');

        // Assert - English content
        $responseEn->assertStatus(200)
            ->assertJsonPath('data.highlights.0.title', 'Beautiful Beaches')
            ->assertJsonPath('data.highlights.0.description', 'Crystal clear waters');
    }

    /**
     * Test page with key facts returns localized content.
     */
    public function test_page_with_key_facts_returns_localized_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'About Djerba', 'fr' => 'À Propos de Djerba'],
            'slug' => ['en' => 'about-djerba', 'fr' => 'a-propos-de-djerba'],
            'publishing_begins_at' => now()->subDay(),
            'key_facts' => [
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
            ],
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/a-propos-de-djerba?locale=fr');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.keyFacts.0.icon', 'users')
            ->assertJsonPath('data.keyFacts.0.label', 'Population')
            ->assertJsonPath('data.keyFacts.0.value', '163,000')
            ->assertJsonPath('data.keyFacts.1.label', 'Superficie');
    }

    /**
     * Test page with gallery returns localized content.
     */
    public function test_page_with_gallery_returns_localized_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Gallery', 'fr' => 'Galerie'],
            'slug' => ['en' => 'gallery', 'fr' => 'galerie'],
            'publishing_begins_at' => now()->subDay(),
            'gallery' => [
                [
                    'image' => '/storage/gallery/beach.jpg',
                    'alt_en' => 'Sandy beach',
                    'alt_fr' => 'Plage de sable',
                    'caption_en' => 'Beautiful sunset',
                    'caption_fr' => 'Beau coucher de soleil',
                ],
            ],
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/galerie?locale=fr');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.gallery.0.image', '/storage/gallery/beach.jpg')
            ->assertJsonPath('data.gallery.0.alt', 'Plage de sable')
            ->assertJsonPath('data.gallery.0.caption', 'Beau coucher de soleil');
    }

    /**
     * Test page with points of interest returns localized content.
     */
    public function test_page_with_pois_returns_localized_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Things to Do', 'fr' => 'Choses à Faire'],
            'slug' => ['en' => 'things-to-do', 'fr' => 'choses-a-faire'],
            'publishing_begins_at' => now()->subDay(),
            'points_of_interest' => [
                [
                    'name_en' => 'El Ghriba Synagogue',
                    'name_fr' => 'Synagogue El Ghriba',
                    'description_en' => 'Historic synagogue dating back to 586 BCE',
                    'description_fr' => 'Synagogue historique datant de 586 avant JC',
                ],
                [
                    'name_en' => 'Houmt Souk',
                    'name_fr' => 'Houmt Souk',
                    'description_en' => 'Traditional market town',
                    'description_fr' => 'Ville de marché traditionnelle',
                ],
            ],
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/choses-a-faire?locale=fr');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.pointsOfInterest.0.name', 'Synagogue El Ghriba')
            ->assertJsonPath('data.pointsOfInterest.0.description', 'Synagogue historique datant de 586 avant JC')
            ->assertJsonPath('data.pointsOfInterest.1.name', 'Houmt Souk');
    }

    /**
     * Test page with SEO fields returns localized SEO content.
     */
    public function test_page_with_seo_fields_returns_localized_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'SEO Page', 'fr' => 'Page SEO'],
            'slug' => ['en' => 'seo-page', 'fr' => 'page-seo'],
            'seo_title_en' => 'SEO Title English',
            'seo_title_fr' => 'Titre SEO Français',
            'seo_description_en' => 'SEO description in English',
            'seo_description_fr' => 'Description SEO en français',
            'seo_text_en' => '<p>SEO text English</p>',
            'seo_text_fr' => '<p>Texte SEO français</p>',
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act - French
        $responseFr = $this->getJson('/api/v1/pages/page-seo?locale=fr');

        // Assert - French
        $responseFr->assertStatus(200)
            ->assertJsonPath('data.seoTitle', 'Titre SEO Français')
            ->assertJsonPath('data.seoDescription', 'Description SEO en français')
            ->assertJsonPath('data.seoText', '<p>Texte SEO français</p>');

        // Act - English
        $responseEn = $this->getJson('/api/v1/pages/seo-page?locale=en');

        // Assert - English
        $responseEn->assertStatus(200)
            ->assertJsonPath('data.seoTitle', 'SEO Title English')
            ->assertJsonPath('data.seoDescription', 'SEO description in English');
    }

    /**
     * Test unpublished pages are not returned in index.
     */
    public function test_unpublished_pages_not_returned_in_index(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Published Page', 'fr' => 'Page Publiée'],
            'slug' => ['en' => 'published', 'fr' => 'publiee'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        Page::create([
            'title' => ['en' => 'Future Page', 'fr' => 'Page Future'],
            'slug' => ['en' => 'future', 'fr' => 'future'],
            'publishing_begins_at' => now()->addDay(),
        ]);

        Page::create([
            'title' => ['en' => 'Expired Page', 'fr' => 'Page Expirée'],
            'slug' => ['en' => 'expired', 'fr' => 'expiree'],
            'publishing_begins_at' => now()->subWeek(),
            'publishing_ends_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages?locale=en');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Published Page', $data[0]['title']);
    }

    /**
     * Test Accept-Language header is respected for locale.
     */
    public function test_accept_language_header_is_respected(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Test Page', 'fr' => 'Page de Test'],
            'slug' => ['en' => 'test', 'fr' => 'test'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act - with Accept-Language: fr
        $responseFr = $this->getJson('/api/v1/pages/test', [
            'Accept-Language' => 'fr',
        ]);

        // Assert
        $responseFr->assertStatus(200)
            ->assertJsonPath('data.title', 'Page de Test');

        // Act - with Accept-Language: en
        $responseEn = $this->getJson('/api/v1/pages/test', [
            'Accept-Language' => 'en',
        ]);

        // Assert
        $responseEn->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Page');
    }

    /**
     * Test page returns empty arrays for missing content sections.
     */
    public function test_page_returns_empty_arrays_for_missing_sections(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Simple Page', 'fr' => 'Page Simple'],
            'slug' => ['en' => 'simple', 'fr' => 'simple'],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/simple?locale=en');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.highlights', [])
            ->assertJsonPath('data.keyFacts', [])
            ->assertJsonPath('data.gallery', [])
            ->assertJsonPath('data.pointsOfInterest', [])
            ->assertJsonPath('data.contentBlocks', []);
    }

    /**
     * Test page with hero call to actions returns localized button labels.
     */
    public function test_page_with_hero_ctas_returns_localized_content(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'CTA Page', 'fr' => 'Page CTA'],
            'slug' => ['en' => 'cta-page', 'fr' => 'page-cta'],
            'hero_call_to_actions' => [
                [
                    'cta_model' => 'url',
                    'url' => '/contact',
                    'button_style' => 'primary',
                    'button_label' => ['en' => 'Contact Us', 'fr' => 'Nous Contacter'],
                    'button_open_new_window' => false,
                ],
                [
                    'cta_model' => 'url',
                    'url' => '/explore',
                    'button_style' => 'secondary',
                    'button_label' => ['en' => 'Explore', 'fr' => 'Explorer'],
                    'button_open_new_window' => true,
                ],
            ],
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act - French
        $responseFr = $this->getJson('/api/v1/pages/page-cta?locale=fr');

        // Assert
        $responseFr->assertStatus(200)
            ->assertJsonPath('data.heroCallToActions.0.buttonLabel', 'Nous Contacter')
            ->assertJsonPath('data.heroCallToActions.0.buttonStyle', 'primary')
            ->assertJsonPath('data.heroCallToActions.1.buttonLabel', 'Explorer')
            ->assertJsonPath('data.heroCallToActions.1.buttonOpenNewWindow', true);

        // Act - English
        $responseEn = $this->getJson('/api/v1/pages/cta-page?locale=en');

        // Assert
        $responseEn->assertStatus(200)
            ->assertJsonPath('data.heroCallToActions.0.buttonLabel', 'Contact Us')
            ->assertJsonPath('data.heroCallToActions.1.buttonLabel', 'Explore');
    }

    /**
     * Test page link field is returned.
     */
    public function test_page_link_field_is_returned(): void
    {
        // Arrange
        Page::create([
            'title' => ['en' => 'Link Page', 'fr' => 'Page Lien'],
            'slug' => ['en' => 'link-page', 'fr' => 'page-lien'],
            'link' => '/explore-djerba',
            'publishing_begins_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/link-page?locale=en');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.link', '/explore-djerba');
    }
}
