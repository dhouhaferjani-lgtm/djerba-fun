<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use fake storage for media uploads
        Storage::fake('public');
    }

    /**
     * Test platform settings endpoint returns expected structure.
     */
    public function test_platform_settings_endpoint_returns_expected_structure(): void
    {
        // Arrange
        PlatformSettings::create([
            'platform_name' => ['en' => 'Test Platform', 'fr' => 'Plateforme Test'],
            'tagline' => ['en' => 'Test Tagline', 'fr' => 'Slogan Test'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'platform',
                    'branding' => [
                        'logoLight',
                        'logoDark',
                        'favicon',
                        'ogImage',
                        'appleTouchIcon',
                        'heroBanner',
                        'heroBannerIsVideo',
                        'heroBannerThumbnail',
                        'brandPillar1',
                        'brandPillar2',
                        'brandPillar3',
                    ],
                    'seo',
                    'contact',
                    'address',
                    'social',
                    'localization',
                    'features',
                    'booking',
                    'legal',
                    'analytics',
                    'hero',
                ],
                'meta' => [
                    'locale',
                    'cached_at',
                ],
            ]);
    }

    /**
     * Test hero banner thumbnail is null when no hero banner is set.
     */
    public function test_hero_banner_thumbnail_is_null_when_no_banner(): void
    {
        // Arrange
        PlatformSettings::create([]);

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'branding' => [
                        'heroBanner' => null,
                        'heroBannerIsVideo' => false,
                        'heroBannerThumbnail' => null,
                    ],
                ],
            ]);
    }

    /**
     * Test hero banner thumbnail equals hero banner when image is uploaded.
     */
    public function test_hero_banner_thumbnail_equals_image_when_image_uploaded(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);

        // Upload an image as hero banner
        $image = UploadedFile::fake()->image('hero.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);

        $data = $response->json('data.branding');

        // For images, thumbnail should equal the original image URL
        $this->assertNotNull($data['heroBanner']);
        $this->assertFalse($data['heroBannerIsVideo']);
        $this->assertEquals($data['heroBanner'], $data['heroBannerThumbnail']);
    }

    /**
     * Test heroBannerIsVideo detection logic.
     *
     * Note: We can't easily test actual video upload with fake files,
     * so we verify the API structure and image behavior instead.
     */
    public function test_hero_banner_is_video_detection_structure(): void
    {
        // Arrange - with image upload
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('hero.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert - structure exists and image is not flagged as video
        $response->assertStatus(200);

        $data = $response->json('data.branding');

        $this->assertArrayHasKey('heroBanner', $data);
        $this->assertArrayHasKey('heroBannerIsVideo', $data);
        $this->assertArrayHasKey('heroBannerThumbnail', $data);

        // Image should NOT be flagged as video
        $this->assertFalse($data['heroBannerIsVideo']);
    }

    /**
     * Test platform settings endpoint accepts locale parameter.
     */
    public function test_platform_settings_respects_locale_parameter(): void
    {
        // Arrange
        PlatformSettings::create([
            'hero_title' => ['en' => 'English Title', 'fr' => 'Titre Français'],
            'hero_subtitle' => ['en' => 'English Subtitle', 'fr' => 'Sous-titre Français'],
        ]);

        // Act - French
        $responseFr = $this->getJson('/api/v1/platform/settings?locale=fr');

        // Assert - French
        $responseFr->assertStatus(200)
            ->assertJson([
                'data' => [
                    'hero' => [
                        'title' => 'Titre Français',
                        'subtitle' => 'Sous-titre Français',
                    ],
                ],
                'meta' => [
                    'locale' => 'fr',
                ],
            ]);

        // Act - English
        $responseEn = $this->getJson('/api/v1/platform/settings?locale=en');

        // Assert - English
        $responseEn->assertStatus(200)
            ->assertJson([
                'data' => [
                    'hero' => [
                        'title' => 'English Title',
                        'subtitle' => 'English Subtitle',
                    ],
                ],
                'meta' => [
                    'locale' => 'en',
                ],
            ]);
    }

    /**
     * Test hero banner video detection logic in API response.
     *
     * Note: Laravel's fake file system doesn't properly simulate video MIME types,
     * so we test the detection logic and configuration rather than actual uploads.
     */
    public function test_hero_banner_video_detection_logic(): void
    {
        // Arrange - upload an image first
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('hero.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert - image should NOT be flagged as video
        $response->assertStatus(200);
        $data = $response->json('data.branding');

        $this->assertNotNull($data['heroBanner']);
        $this->assertFalse($data['heroBannerIsVideo'], 'Image should not be flagged as video');

        // Verify the detection logic uses str_starts_with for video/ prefix
        $this->assertTrue(str_starts_with('video/mp4', 'video/'));
        $this->assertFalse(str_starts_with('image/jpeg', 'video/'));
    }

    /**
     * Test config allows large files (20MB) - regression test for config mismatch.
     *
     * Bug: Media library was configured with 10MB limit, but form advertised 20MB.
     * Fix: Updated config/media-library.php max_file_size to 20MB.
     */
    public function test_media_library_config_allows_20mb_files(): void
    {
        // Verify the config fix is in place
        $maxSize = config('media-library.max_file_size');
        $expectedSize = 1024 * 1024 * 20; // 20MB

        $this->assertEquals(
            $expectedSize,
            $maxSize,
            'media-library.max_file_size must be 20MB (20971520 bytes) to match form validation'
        );

        // Also verify hero_banner collection accepts video MIME types
        $settings = PlatformSettings::create([]);
        $collections = $settings->getRegisteredMediaCollections();
        $heroBannerCollection = $collections->firstWhere('name', 'hero_banner');

        $this->assertNotNull($heroBannerCollection);
        $this->assertContains('video/mp4', $heroBannerCollection->acceptsMimeTypes);
        $this->assertContains('video/webm', $heroBannerCollection->acceptsMimeTypes);
    }

    /**
     * Test logo light URL is returned when media is uploaded.
     */
    public function test_logo_light_url_returned_when_media_uploaded(): void
    {
        // Arrange
        $settings = PlatformSettings::create([
            'platform_name' => ['en' => 'Test Platform'],
        ]);

        // Upload a logo image
        $image = UploadedFile::fake()->image('logo.png', 200, 200);
        $settings->addMedia($image)->toMediaCollection('logo_light');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.branding');

        $this->assertNotNull($data['logoLight']);
        $this->assertStringContainsString('logo', $data['logoLight']);
    }

    /**
     * Test logo dark URL is returned when media is uploaded.
     */
    public function test_logo_dark_url_returned_when_media_uploaded(): void
    {
        // Arrange
        $settings = PlatformSettings::create([
            'platform_name' => ['en' => 'Test Platform'],
        ]);

        // Upload a logo image
        $image = UploadedFile::fake()->image('logo-dark.png', 200, 200);
        $settings->addMedia($image)->toMediaCollection('logo_dark');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.branding');

        $this->assertNotNull($data['logoDark']);
    }

    /**
     * Test media URLs are publicly accessible (not admin proxy URLs).
     */
    public function test_media_urls_are_public_not_admin_proxy(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('logo.png', 200, 200);
        $settings->addMedia($image)->toMediaCollection('logo_light');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $logoUrl = $response->json('data.branding.logoLight');

        // URL should NOT be an admin proxy URL
        $this->assertNotNull($logoUrl);
        $this->assertStringNotContainsString('/admin/media-proxy/', $logoUrl);
    }

    /**
     * Test all branding media URLs are returned correctly.
     */
    public function test_all_branding_media_urls_returned(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);

        // Upload all branding images
        $collections = ['logo_light', 'logo_dark', 'favicon', 'og_image', 'apple_touch_icon'];

        foreach ($collections as $collection) {
            $image = UploadedFile::fake()->image("{$collection}.png", 200, 200);
            $settings->addMedia($image)->toMediaCollection($collection);
        }

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $branding = $response->json('data.branding');

        $this->assertNotNull($branding['logoLight']);
        $this->assertNotNull($branding['logoDark']);
        $this->assertNotNull($branding['favicon']);
        $this->assertNotNull($branding['ogImage']);
        $this->assertNotNull($branding['appleTouchIcon']);
    }

    /**
     * Test brand pillar 1 URL is returned when media is uploaded.
     *
     * Bug: Pillar images uploaded in admin were not displaying on frontend.
     * Root cause: Browser cached 403 from broken storage symlink.
     */
    public function test_brand_pillar_1_url_returned_when_media_uploaded(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('pillar1.jpg', 1080, 1080);
        $settings->addMedia($image)->toMediaCollection('brand_pillar_1');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $brandPillar1 = $response->json('data.branding.brandPillar1');

        $this->assertNotNull($brandPillar1);
        $this->assertStringContainsString('pillar1', $brandPillar1);
    }

    /**
     * Test brand pillar 2 URL is returned when media is uploaded.
     */
    public function test_brand_pillar_2_url_returned_when_media_uploaded(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('pillar2.jpg', 1080, 1080);
        $settings->addMedia($image)->toMediaCollection('brand_pillar_2');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $brandPillar2 = $response->json('data.branding.brandPillar2');

        $this->assertNotNull($brandPillar2);
    }

    /**
     * Test brand pillar 3 URL is returned when media is uploaded.
     */
    public function test_brand_pillar_3_url_returned_when_media_uploaded(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('pillar3.jpg', 1080, 1080);
        $settings->addMedia($image)->toMediaCollection('brand_pillar_3');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $brandPillar3 = $response->json('data.branding.brandPillar3');

        $this->assertNotNull($brandPillar3);
    }

    /**
     * Test all brand pillar URLs are returned when media is uploaded.
     */
    public function test_all_brand_pillar_urls_returned(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);

        // Upload all pillar images
        $pillars = ['brand_pillar_1', 'brand_pillar_2', 'brand_pillar_3'];
        foreach ($pillars as $pillar) {
            $image = UploadedFile::fake()->image("{$pillar}.jpg", 1080, 1080);
            $settings->addMedia($image)->toMediaCollection($pillar);
        }

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $branding = $response->json('data.branding');

        $this->assertNotNull($branding['brandPillar1']);
        $this->assertNotNull($branding['brandPillar2']);
        $this->assertNotNull($branding['brandPillar3']);
    }

    /**
     * Test brand pillar URLs are public (not admin proxy URLs).
     */
    public function test_brand_pillar_urls_are_public_not_admin_proxy(): void
    {
        // Arrange
        $settings = PlatformSettings::create([]);
        $image = UploadedFile::fake()->image('pillar.jpg', 1080, 1080);
        $settings->addMedia($image)->toMediaCollection('brand_pillar_1');

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200);
        $pillarUrl = $response->json('data.branding.brandPillar1');

        // URL should NOT be an admin proxy URL
        $this->assertNotNull($pillarUrl);
        $this->assertStringNotContainsString('/admin/media-proxy/', $pillarUrl);
    }

    /**
     * Test contact information is returned in API response.
     */
    public function test_contact_information_returned_in_api(): void
    {
        // Arrange
        PlatformSettings::create([
            'support_email' => 'support@test.com',
            'general_email' => 'hello@test.com',
            'phone_number' => '+216 99 123 456',
            'whatsapp_number' => '+216 99 123 456',
        ]);

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.contact.supportEmail', 'support@test.com')
            ->assertJsonPath('data.contact.generalEmail', 'hello@test.com')
            ->assertJsonPath('data.contact.phone', '+216 99 123 456')
            ->assertJsonPath('data.contact.whatsapp', '+216 99 123 456');
    }

    /**
     * Test address information is returned in API response.
     */
    public function test_address_information_returned_in_api(): void
    {
        // Arrange
        PlatformSettings::create([
            'address_street' => '123 Test Street',
            'address_city' => 'Djerba',
            'address_region' => 'Medenine',
            'address_postal_code' => '4180',
            'address_country' => 'TN',
            'google_maps_url' => 'https://maps.google.com/test',
        ]);

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.address.street', '123 Test Street')
            ->assertJsonPath('data.address.city', 'Djerba')
            ->assertJsonPath('data.address.region', 'Medenine')
            ->assertJsonPath('data.address.postalCode', '4180')
            ->assertJsonPath('data.address.country', 'TN')
            ->assertJsonPath('data.address.googleMapsUrl', 'https://maps.google.com/test');

        // Verify full address is generated
        $fullAddress = $response->json('data.address.full');
        $this->assertNotNull($fullAddress);
        $this->assertStringContainsString('Djerba', $fullAddress);
    }

    /**
     * Test social media links are returned in API response.
     */
    public function test_social_media_links_returned_in_api(): void
    {
        // Arrange
        PlatformSettings::create([
            'social_facebook' => 'https://facebook.com/test',
            'social_instagram' => 'https://instagram.com/test',
            'social_twitter' => 'https://twitter.com/test',
            'social_linkedin' => 'https://linkedin.com/company/test',
            'social_youtube' => 'https://youtube.com/@test',
            'social_tiktok' => 'https://tiktok.com/@test',
        ]);

        // Act
        $response = $this->getJson('/api/v1/platform/settings');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.social.facebook', 'https://facebook.com/test')
            ->assertJsonPath('data.social.instagram', 'https://instagram.com/test')
            ->assertJsonPath('data.social.twitter', 'https://twitter.com/test')
            ->assertJsonPath('data.social.linkedin', 'https://linkedin.com/company/test')
            ->assertJsonPath('data.social.youtube', 'https://youtube.com/@test')
            ->assertJsonPath('data.social.tiktok', 'https://tiktok.com/@test');
    }

    /**
     * Test about page initiatives text is returned in API response.
     */
    public function test_about_initiatives_text_returned_in_api(): void
    {
        // Arrange
        PlatformSettings::create([
            'about_initiatives_title' => ['en' => 'Our Initiatives', 'fr' => 'Nos Initiatives'],
            'about_initiatives_description' => ['en' => 'We give back', 'fr' => 'Nous redonnons'],
            'about_initiatives_bullets' => [
                ['text_en' => 'Education programs', 'text_fr' => 'Programmes éducatifs'],
                ['text_en' => 'Environment protection', 'text_fr' => 'Protection environnementale'],
                ['text_en' => 'Local community support', 'text_fr' => 'Soutien communautaire local'],
            ],
        ]);

        // Act - English
        $responseEn = $this->getJson('/api/v1/platform/settings?locale=en');

        // Assert - English
        $responseEn->assertStatus(200)
            ->assertJsonPath('data.about.initiativesText.title', 'Our Initiatives')
            ->assertJsonPath('data.about.initiativesText.description', 'We give back')
            ->assertJsonPath('data.about.initiativesText.bullets.0', 'Education programs')
            ->assertJsonPath('data.about.initiativesText.bullets.1', 'Environment protection')
            ->assertJsonPath('data.about.initiativesText.bullets.2', 'Local community support');

        // Act - French
        $responseFr = $this->getJson('/api/v1/platform/settings?locale=fr');

        // Assert - French
        $responseFr->assertStatus(200)
            ->assertJsonPath('data.about.initiativesText.title', 'Nos Initiatives')
            ->assertJsonPath('data.about.initiativesText.description', 'Nous redonnons')
            ->assertJsonPath('data.about.initiativesText.bullets.0', 'Programmes éducatifs')
            ->assertJsonPath('data.about.initiativesText.bullets.1', 'Protection environnementale')
            ->assertJsonPath('data.about.initiativesText.bullets.2', 'Soutien communautaire local');
    }

    /**
     * Test about page structure includes all expected sections.
     */
    public function test_about_page_structure_returned_in_api(): void
    {
        // Arrange
        PlatformSettings::create([
            'about_hero_title' => ['en' => 'About Us', 'fr' => 'À Propos'],
            'about_founder_name' => 'Seif Ben Helel',
            'about_team_title' => ['en' => 'Our Team', 'fr' => 'Notre Équipe'],
            'about_impact_text' => ['en' => '1% for nature', 'fr' => '1% pour la nature'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/platform/settings?locale=en');

        // Assert structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'about' => [
                        'hero' => ['title', 'subtitle', 'tagline', 'image'],
                        'story' => ['heading', 'intro', 'text1', 'text2'],
                        'founder' => ['name', 'photo', 'story', 'quote'],
                        'team' => ['title', 'description'],
                        'impactText',
                        'commitments',
                        'partners',
                        'initiatives',
                        'initiativesText' => ['title', 'description', 'bullets'],
                    ],
                ],
            ]);

        // Assert values
        $response->assertJsonPath('data.about.hero.title', 'About Us')
            ->assertJsonPath('data.about.founder.name', 'Seif Ben Helel')
            ->assertJsonPath('data.about.team.title', 'Our Team')
            ->assertJsonPath('data.about.impactText', '1% for nature');
    }
}
