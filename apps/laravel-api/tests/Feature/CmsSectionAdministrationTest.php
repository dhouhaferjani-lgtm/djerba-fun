<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlatformSettings;
use App\Services\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BDD Tests for CMS Section Administration
 *
 * These tests verify that all homepage sections can be administered
 * via the PlatformSettings model and exposed through the API.
 *
 * Sections to administer:
 * 1. Experience Categories Section (title, subtitle, enabled)
 * 2. Blog Section (title, subtitle, enabled, post limit)
 * 3. Featured Packages Section (title, subtitle, enabled, limit)
 * 4. Custom Experience CTA Section (title, description, button text, link, enabled)
 * 5. Newsletter Section (title, subtitle, button text, enabled)
 * 6. About Page (hero, founder, commitments, partners, initiatives)
 */
class CmsSectionAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = PlatformSettings::firstOrCreate([]);
    }

    // =========================================================================
    // Experience Categories Section
    // =========================================================================

    public function test_experience_categories_section_has_required_fields(): void
    {
        $this->settings->fill([
            'experience_categories_enabled' => true,
            'experience_categories_title' => ['en' => 'Explore Our Experiences', 'fr' => 'Explorez Nos Expériences'],
            'experience_categories_subtitle' => ['en' => 'Find your perfect adventure', 'fr' => 'Trouvez votre aventure parfaite'],
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertTrue($this->settings->experience_categories_enabled);
        $this->assertEquals('Explore Our Experiences', $this->settings->getTranslation('experience_categories_title', 'en'));
        $this->assertEquals('Explorez Nos Expériences', $this->settings->getTranslation('experience_categories_title', 'fr'));
    }

    public function test_experience_categories_section_exposed_in_api(): void
    {
        $this->settings->fill([
            'experience_categories_enabled' => true,
            'experience_categories_title' => ['en' => 'Our Experiences', 'fr' => 'Nos Expériences'],
            'experience_categories_subtitle' => ['en' => 'Discover', 'fr' => 'Découvrez'],
        ]);
        $this->settings->save();

        $service = app(PlatformSettingsService::class);
        $publicSettings = $service->getPublicSettings('en');

        $this->assertArrayHasKey('experienceCategories', $publicSettings);
        $this->assertTrue($publicSettings['experienceCategories']['enabled']);
        $this->assertEquals('Our Experiences', $publicSettings['experienceCategories']['title']);
        $this->assertEquals('Discover', $publicSettings['experienceCategories']['subtitle']);
    }

    // =========================================================================
    // Blog Section
    // =========================================================================

    public function test_blog_section_has_required_fields(): void
    {
        $this->settings->fill([
            'blog_section_enabled' => true,
            'blog_section_title' => ['en' => 'Latest from the Blog', 'fr' => 'Dernières actualités'],
            'blog_section_subtitle' => ['en' => 'Travel tips and stories', 'fr' => 'Conseils et récits de voyage'],
            'blog_section_post_limit' => 3,
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertTrue($this->settings->blog_section_enabled);
        $this->assertEquals(3, $this->settings->blog_section_post_limit);
    }

    public function test_blog_section_exposed_in_api(): void
    {
        $this->settings->fill([
            'blog_section_enabled' => true,
            'blog_section_title' => ['en' => 'Blog', 'fr' => 'Blog'],
            'blog_section_subtitle' => ['en' => 'Stories', 'fr' => 'Histoires'],
            'blog_section_post_limit' => 6,
        ]);
        $this->settings->save();

        $service = app(PlatformSettingsService::class);
        $publicSettings = $service->getPublicSettings('en');

        $this->assertArrayHasKey('blogSection', $publicSettings);
        $this->assertTrue($publicSettings['blogSection']['enabled']);
        $this->assertEquals(6, $publicSettings['blogSection']['postLimit']);
    }

    // =========================================================================
    // Featured Packages Section
    // =========================================================================

    public function test_featured_packages_section_has_required_fields(): void
    {
        $this->settings->fill([
            'featured_packages_enabled' => true,
            'featured_packages_title' => ['en' => 'Upcoming Adventures', 'fr' => 'Aventures à venir'],
            'featured_packages_subtitle' => ['en' => 'Book your next trip', 'fr' => 'Réservez votre prochain voyage'],
            'featured_packages_limit' => 3,
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertTrue($this->settings->featured_packages_enabled);
        $this->assertEquals(3, $this->settings->featured_packages_limit);
    }

    public function test_featured_packages_section_exposed_in_api(): void
    {
        $this->settings->fill([
            'featured_packages_enabled' => true,
            'featured_packages_title' => ['en' => 'Adventures', 'fr' => 'Aventures'],
            'featured_packages_subtitle' => ['en' => 'Explore', 'fr' => 'Explorez'],
            'featured_packages_limit' => 4,
        ]);
        $this->settings->save();

        $service = app(PlatformSettingsService::class);
        $publicSettings = $service->getPublicSettings('en');

        $this->assertArrayHasKey('featuredPackages', $publicSettings);
        $this->assertTrue($publicSettings['featuredPackages']['enabled']);
        $this->assertEquals(4, $publicSettings['featuredPackages']['limit']);
    }

    // =========================================================================
    // Custom Experience CTA Section
    // =========================================================================

    public function test_custom_experience_section_has_required_fields(): void
    {
        $this->settings->fill([
            'custom_experience_enabled' => true,
            'custom_experience_title' => ['en' => 'Create Your Adventure', 'fr' => 'Créez Votre Aventure'],
            'custom_experience_description' => ['en' => 'Tell us your dream trip', 'fr' => 'Décrivez-nous votre voyage de rêve'],
            'custom_experience_button_text' => ['en' => 'Get Started', 'fr' => 'Commencer'],
            'custom_experience_link' => '/custom-trip',
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertTrue($this->settings->custom_experience_enabled);
        $this->assertEquals('/custom-trip', $this->settings->custom_experience_link);
    }

    public function test_custom_experience_section_exposed_in_api(): void
    {
        $this->settings->fill([
            'custom_experience_enabled' => true,
            'custom_experience_title' => ['en' => 'Custom Trip', 'fr' => 'Voyage Sur Mesure'],
            'custom_experience_description' => ['en' => 'Plan with us', 'fr' => 'Planifiez avec nous'],
            'custom_experience_button_text' => ['en' => 'Contact', 'fr' => 'Contactez'],
            'custom_experience_link' => '/contact',
        ]);
        $this->settings->save();

        $service = app(PlatformSettingsService::class);
        $publicSettings = $service->getPublicSettings('en');

        $this->assertArrayHasKey('customExperience', $publicSettings);
        $this->assertTrue($publicSettings['customExperience']['enabled']);
        $this->assertEquals('Custom Trip', $publicSettings['customExperience']['title']);
        $this->assertEquals('/contact', $publicSettings['customExperience']['link']);
    }

    // =========================================================================
    // Newsletter Section
    // =========================================================================

    public function test_newsletter_section_has_required_fields(): void
    {
        $this->settings->fill([
            'newsletter_enabled' => true,
            'newsletter_title' => ['en' => 'Stay Updated', 'fr' => 'Restez Informé'],
            'newsletter_subtitle' => ['en' => 'Get the latest deals', 'fr' => 'Recevez les dernières offres'],
            'newsletter_button_text' => ['en' => 'Subscribe', 'fr' => 'S\'abonner'],
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertTrue($this->settings->newsletter_enabled);
        $this->assertEquals('Stay Updated', $this->settings->getTranslation('newsletter_title', 'en'));
    }

    public function test_newsletter_section_exposed_in_api(): void
    {
        $this->settings->fill([
            'newsletter_enabled' => true,
            'newsletter_title' => ['en' => 'Newsletter', 'fr' => 'Newsletter'],
            'newsletter_subtitle' => ['en' => 'Join us', 'fr' => 'Rejoignez-nous'],
            'newsletter_button_text' => ['en' => 'Sign Up', 'fr' => 'Inscription'],
        ]);
        $this->settings->save();

        $service = app(PlatformSettingsService::class);
        $publicSettings = $service->getPublicSettings('en');

        $this->assertArrayHasKey('newsletter', $publicSettings);
        $this->assertTrue($publicSettings['newsletter']['enabled']);
        $this->assertEquals('Newsletter', $publicSettings['newsletter']['title']);
    }

    // =========================================================================
    // About Page
    // =========================================================================

    public function test_about_page_hero_has_required_fields(): void
    {
        $this->settings->fill([
            'about_hero_title' => ['en' => 'About Us', 'fr' => 'À Propos'],
            'about_hero_subtitle' => ['en' => 'Our story', 'fr' => 'Notre histoire'],
            'about_hero_tagline' => ['en' => 'Adventure awaits', 'fr' => 'L\'aventure vous attend'],
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertEquals('About Us', $this->settings->getTranslation('about_hero_title', 'en'));
        $this->assertEquals('À Propos', $this->settings->getTranslation('about_hero_title', 'fr'));
    }

    public function test_about_page_founder_has_required_fields(): void
    {
        $this->settings->fill([
            'about_founder_name' => 'Seif Ben Helel',
            'about_founder_story' => ['en' => 'Founder story...', 'fr' => 'Histoire du fondateur...'],
            'about_founder_quote' => ['en' => 'Adventure is life', 'fr' => 'L\'aventure c\'est la vie'],
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertEquals('Seif Ben Helel', $this->settings->about_founder_name);
        $this->assertEquals('Adventure is life', $this->settings->getTranslation('about_founder_quote', 'en'));
    }

    public function test_about_page_commitments_as_json_array(): void
    {
        $commitments = [
            [
                'icon' => 'sustainable',
                'title_en' => 'Sustainable Travel',
                'title_fr' => 'Tourisme Responsable',
                'description_en' => 'Eco-friendly adventures',
                'description_fr' => 'Aventures éco-responsables',
            ],
            [
                'icon' => 'active',
                'title_en' => 'Active Lifestyle',
                'title_fr' => 'Style de vie actif',
                'description_en' => 'Stay active on your trip',
                'description_fr' => 'Restez actif pendant votre voyage',
            ],
        ];

        $this->settings->fill([
            'about_commitments' => $commitments,
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertCount(2, $this->settings->about_commitments);
        $this->assertEquals('sustainable', $this->settings->about_commitments[0]['icon']);
    }

    public function test_about_page_partners_as_json_array(): void
    {
        $partners = [
            ['name' => 'Partner 1', 'logo' => '/images/partners/p1.png'],
            ['name' => 'Partner 2', 'logo' => '/images/partners/p2.png'],
        ];

        $this->settings->fill([
            'about_partners' => $partners,
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertCount(2, $this->settings->about_partners);
    }

    public function test_about_page_initiatives_as_json_array(): void
    {
        $initiatives = [
            ['image' => '/images/init1.jpg', 'alt_en' => 'Workshop', 'alt_fr' => 'Atelier'],
            ['image' => '/images/init2.jpg', 'alt_en' => 'Sports', 'alt_fr' => 'Sports'],
        ];

        $this->settings->fill([
            'about_initiatives' => $initiatives,
        ]);
        $this->settings->save();

        $this->settings->refresh();

        $this->assertCount(2, $this->settings->about_initiatives);
    }

    public function test_about_page_exposed_in_api(): void
    {
        $this->settings->fill([
            'about_hero_title' => ['en' => 'About Djerba Fun', 'fr' => 'À Propos de Djerba Fun'],
            'about_hero_subtitle' => ['en' => 'Our journey', 'fr' => 'Notre parcours'],
            'about_hero_tagline' => ['en' => 'Since 2015', 'fr' => 'Depuis 2015'],
            'about_founder_name' => 'Seif Ben Helel',
            'about_founder_story' => ['en' => 'Story...', 'fr' => 'Histoire...'],
            'about_founder_quote' => ['en' => 'Quote', 'fr' => 'Citation'],
            'about_commitments' => [
                ['icon' => 'sustainable', 'title_en' => 'Sustainable', 'title_fr' => 'Durable', 'description_en' => 'Desc', 'description_fr' => 'Desc'],
            ],
            'about_partners' => [
                ['name' => 'Partner', 'logo' => '/logo.png'],
            ],
            'about_initiatives' => [
                ['image' => '/init.jpg', 'alt_en' => 'Initiative', 'alt_fr' => 'Initiative'],
            ],
        ]);
        $this->settings->save();

        $service = app(PlatformSettingsService::class);
        $publicSettings = $service->getPublicSettings('en');

        $this->assertArrayHasKey('about', $publicSettings);
        $this->assertEquals('About Djerba Fun', $publicSettings['about']['hero']['title']);
        $this->assertEquals('Seif Ben Helel', $publicSettings['about']['founder']['name']);
        $this->assertCount(1, $publicSettings['about']['commitments']);
        $this->assertCount(1, $publicSettings['about']['partners']);
        $this->assertCount(1, $publicSettings['about']['initiatives']);
    }
}
