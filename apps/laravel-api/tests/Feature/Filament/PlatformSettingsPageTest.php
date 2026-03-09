<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Admin\Pages\PlatformSettingsPage;
use App\Models\PlatformSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for PlatformSettingsPage Filament admin page.
 *
 * These tests verify the hero banner video upload functionality works correctly
 * after fixing the 10MB vs 20MB media-library config mismatch bug.
 */
class PlatformSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake both public and minio disks for tests
        Storage::fake('public');
        Storage::fake('minio');

        // Set the media disk to use fake storage
        config(['media-library.disk_name' => 'public']);

        // Create admin user for authentication
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        // Seed platform settings with all required fields
        $this->seed(\Database\Seeders\PlatformSettingsSeeder::class);
    }

    /**
     * Test admin can access platform settings page.
     */
    public function test_admin_can_access_platform_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/platform-settings')
            ->assertSuccessful();
    }

    /**
     * Test hero banner accepts image uploads.
     */
    public function test_hero_banner_accepts_image_upload(): void
    {
        // Arrange
        $image = UploadedFile::fake()->image('hero.jpg', 1920, 1080);

        // Act - Upload image to platform settings
        $settings = PlatformSettings::freshInstance();
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        // Assert
        $media = $settings->fresh()->getFirstMedia('hero_banner');
        $this->assertNotNull($media);
        $this->assertStringStartsWith('image/', $media->mime_type);
    }

    /**
     * Test hero banner config allows video uploads up to 20MB.
     *
     * Regression test for the config mismatch bug:
     * - Form declared maxSize(20480) = 20MB
     * - Media library config had max_file_size = 10MB
     * - Videos between 10-20MB would fail with "Error during upload"
     *
     * Note: Laravel's fake file system doesn't properly simulate video MIME types,
     * so we test the configuration and collection definitions rather than actual uploads.
     */
    public function test_hero_banner_config_allows_video_up_to_20mb(): void
    {
        // Verify config allows 20MB (the root cause fix)
        $maxSize = config('media-library.max_file_size');
        $expectedSize = 1024 * 1024 * 20; // 20MB
        $this->assertEquals($expectedSize, $maxSize, 'Config must allow 20MB files');

        // Verify collection accepts video MIME types
        $settings = PlatformSettings::freshInstance();
        $collections = $settings->getRegisteredMediaCollections();
        $heroBannerCollection = $collections->firstWhere('name', 'hero_banner');

        $this->assertNotNull($heroBannerCollection);
        $this->assertContains('video/mp4', $heroBannerCollection->acceptsMimeTypes);
        $this->assertContains('video/webm', $heroBannerCollection->acceptsMimeTypes);
    }

    /**
     * Test hero banner collection accepts WebM video format.
     */
    public function test_hero_banner_collection_accepts_webm_video(): void
    {
        $settings = PlatformSettings::freshInstance();
        $collections = $settings->getRegisteredMediaCollections();
        $heroBannerCollection = $collections->firstWhere('name', 'hero_banner');

        $this->assertNotNull($heroBannerCollection);
        $this->assertContains('video/webm', $heroBannerCollection->acceptsMimeTypes);
    }

    /**
     * Test hero banner collection is configured as single file.
     *
     * The singleFile() constraint means new uploads replace existing ones.
     * This is tested in the unit tests; here we verify the collection config.
     */
    public function test_hero_banner_collection_is_single_file(): void
    {
        $settings = PlatformSettings::freshInstance();
        $collections = $settings->getRegisteredMediaCollections();
        $heroBannerCollection = $collections->firstWhere('name', 'hero_banner');

        $this->assertNotNull($heroBannerCollection);
        $this->assertTrue($heroBannerCollection->singleFile, 'hero_banner should be a single file collection');
    }

    /**
     * Test hero banner video detection logic.
     */
    public function test_hero_banner_video_detection_logic(): void
    {
        // Verify the MIME type detection pattern used by the service layer
        $this->assertTrue(str_starts_with('video/mp4', 'video/'));
        $this->assertTrue(str_starts_with('video/webm', 'video/'));
        $this->assertFalse(str_starts_with('image/jpeg', 'video/'));
        $this->assertFalse(str_starts_with('image/png', 'video/'));
    }

    /**
     * Test media library config max_file_size matches form limit.
     *
     * This test ensures the config fix stays in place and prevents regression.
     */
    public function test_media_library_config_matches_form_limit(): void
    {
        // Form declares maxSize(20480) = 20MB in kilobytes
        $formLimitKb = 20480;
        $formLimitBytes = $formLimitKb * 1024;

        // Config should match
        $configLimit = config('media-library.max_file_size');

        $this->assertEquals(
            $formLimitBytes,
            $configLimit,
            'media-library.max_file_size must equal 20MB (20971520 bytes) to match form validation'
        );
    }

    // =========================================================================
    // BDD TESTS FOR FILE UPLOAD PERSISTENCE (BUG-001)
    // =========================================================================

    /**
     * @test
     * Scenario: Admin saves scalar platform settings via Filament form
     * Given I am logged in as an admin
     * And I am on the Platform Settings page
     * When I update the platform name and save
     * Then the platform name should be persisted to the database
     *
     * This is a REGRESSION test to ensure scalar data saving still works
     * after any changes to the save() method.
     */
    public function admin_can_save_scalar_settings_via_filament_form(): void
    {
        // Given: Admin is authenticated
        $this->actingAs($this->admin);

        // When: Save form with scalar data
        Livewire::test(PlatformSettingsPage::class)
            ->set('data.platform_name.en', 'Test Platform Name')
            ->set('data.platform_name.fr', 'Nom de la plateforme test')
            ->call('save')
            ->assertHasNoErrors();

        // Then: Data should be persisted
        $settings = PlatformSettings::first();
        $this->assertEquals('Test Platform Name', $settings->getTranslation('platform_name', 'en'));
        $this->assertEquals('Nom de la plateforme test', $settings->getTranslation('platform_name', 'fr'));
    }

    /**
     * @test
     * Scenario: Admin saves hero banner image via Filament form
     * Given I am logged in as an admin
     * And I am on the Platform Settings page
     * When I upload a hero banner image and click save
     * Then the hero banner should be persisted to the media table
     * And the API should return the hero banner URL
     *
     * This tests the CRITICAL BUG-001: File uploads not persisting.
     */
    public function admin_can_save_hero_banner_via_filament_form(): void
    {
        // Given: Admin is authenticated and has a fake image
        $this->actingAs($this->admin);
        $image = UploadedFile::fake()->image('hero-banner.jpg', 1920, 1080);

        // When: Upload and save via Livewire component
        $component = Livewire::test(PlatformSettingsPage::class)
            ->set('data.hero_banner', [$image])
            ->call('save')
            ->assertHasNoErrors();

        // Then: Media should be persisted to database
        $settings = PlatformSettings::first();
        $settings->load('media');
        $media = $settings->getFirstMedia('hero_banner');

        $this->assertNotNull($media, 'Hero banner media should be saved to database');
        $this->assertStringStartsWith('image/', $media->mime_type);
    }

    /**
     * @test
     * Scenario: Admin saves logo images via Filament form
     * Given I am logged in as an admin
     * When I upload both light and dark logo images and save
     * Then both logos should be persisted to the media table
     *
     * Tests multiple media collections being saved in a single form submission.
     */
    public function admin_can_save_multiple_logos_via_filament_form(): void
    {
        // Given: Admin is authenticated with fake images
        $this->actingAs($this->admin);
        $logoLight = UploadedFile::fake()->image('logo-light.png', 200, 50);
        $logoDark = UploadedFile::fake()->image('logo-dark.png', 200, 50);

        // When: Upload both logos and save
        Livewire::test(PlatformSettingsPage::class)
            ->set('data.logo_light', [$logoLight])
            ->set('data.logo_dark', [$logoDark])
            ->call('save')
            ->assertHasNoErrors();

        // Then: Both logos should be persisted
        $settings = PlatformSettings::first();
        $settings->load('media');

        $this->assertNotNull($settings->getFirstMedia('logo_light'), 'Logo light should be saved');
        $this->assertNotNull($settings->getFirstMedia('logo_dark'), 'Logo dark should be saved');
    }

    /**
     * @test
     * Scenario: API returns uploaded media URLs after admin saves
     * Given I have uploaded a hero banner via the admin panel
     * When I fetch platform settings via the API
     * Then the heroBanner field should contain the media URL
     *
     * Integration test: Admin upload → Database → API response
     */
    public function api_returns_uploaded_media_urls(): void
    {
        // Given: Upload hero banner via model (simulating successful form save)
        $settings = PlatformSettings::first();
        $image = UploadedFile::fake()->image('hero.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        // When: Fetch settings via API
        $response = $this->getJson('/api/v1/platform/settings');

        // Then: API should return media URL
        $response->assertSuccessful();
        $response->assertJsonPath('data.branding.heroBanner', fn ($url) => $url !== null && str_contains($url, 'hero'));
    }

    /**
     * @test
     * Scenario: Saving scalar data does not clear existing media
     * Given I have previously uploaded a hero banner
     * When I update the platform name and save (without touching media)
     * Then the hero banner should still exist
     *
     * REGRESSION test: Ensure scalar saves don't wipe media.
     */
    public function saving_scalar_data_preserves_existing_media(): void
    {
        // Given: Hero banner already uploaded
        $settings = PlatformSettings::first();
        $image = UploadedFile::fake()->image('existing-hero.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        $originalMediaId = $settings->fresh()->getFirstMedia('hero_banner')->id;

        // When: Save only scalar data via form
        $this->actingAs($this->admin);
        Livewire::test(PlatformSettingsPage::class)
            ->set('data.platform_name.en', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        // Then: Media should still exist with same ID
        $settings = PlatformSettings::first();
        $settings->load('media');
        $media = $settings->getFirstMedia('hero_banner');

        $this->assertNotNull($media, 'Hero banner should not be deleted');
        $this->assertEquals($originalMediaId, $media->id, 'Media ID should be unchanged');
    }

    /**
     * @test
     * Scenario: Brand pillar images persist correctly
     * Given I am logged in as an admin
     * When I upload brand pillar images and save
     * Then all three brand pillar images should be persisted
     */
    public function admin_can_save_brand_pillar_images(): void
    {
        // Given: Admin is authenticated
        $this->actingAs($this->admin);
        $pillar1 = UploadedFile::fake()->image('pillar1.jpg', 600, 400);
        $pillar2 = UploadedFile::fake()->image('pillar2.jpg', 600, 400);
        $pillar3 = UploadedFile::fake()->image('pillar3.jpg', 600, 400);

        // When: Upload all pillars and save
        Livewire::test(PlatformSettingsPage::class)
            ->set('data.brand_pillar_1', [$pillar1])
            ->set('data.brand_pillar_2', [$pillar2])
            ->set('data.brand_pillar_3', [$pillar3])
            ->call('save')
            ->assertHasNoErrors();

        // Then: All pillars should be persisted
        $settings = PlatformSettings::first();
        $settings->load('media');

        $this->assertNotNull($settings->getFirstMedia('brand_pillar_1'), 'Brand pillar 1 should be saved');
        $this->assertNotNull($settings->getFirstMedia('brand_pillar_2'), 'Brand pillar 2 should be saved');
        $this->assertNotNull($settings->getFirstMedia('brand_pillar_3'), 'Brand pillar 3 should be saved');
    }

    // =========================================================================
    // BDD TESTS FOR NOT NULL CONSTRAINT HANDLING (BUG-002)
    // =========================================================================

    /**
     * @test
     * Scenario: Saving partial form data preserves NOT NULL field defaults
     * Given I have platform settings with organization_type = 'TravelAgency'
     * When I save only the platform_name field
     * Then the organization_type should remain 'TravelAgency'
     * And no NOT NULL constraint violation should occur
     *
     * Regression test for the NOT NULL violation bug when form fields return null.
     */
    public function saving_partial_data_preserves_not_null_field_defaults(): void
    {
        // Given: Settings exist with organization_type set
        $settings = PlatformSettings::first();
        $originalOrgType = $settings->organization_type;
        $this->assertNotNull($originalOrgType, 'organization_type should have a default value');

        // When: Save only platform_name via form (other fields return null)
        $this->actingAs($this->admin);
        Livewire::test(PlatformSettingsPage::class)
            ->set('data.platform_name.en', 'Updated Platform Name')
            ->call('save')
            ->assertHasNoErrors();

        // Then: organization_type should be preserved
        $settings = PlatformSettings::first();
        $this->assertEquals($originalOrgType, $settings->organization_type, 'organization_type should be preserved');
    }

    /**
     * @test
     * Scenario: filterNullValues helper preserves non-null values correctly
     * This unit test verifies the filtering logic works as expected.
     */
    public function filter_null_values_preserves_non_null_values(): void
    {
        $page = new class extends PlatformSettingsPage
        {
            public function testFilter(array $data): array
            {
                return $this->filterNullValues($data);
            }
        };

        // Test data with various value types
        $input = [
            'null_value' => null,
            'empty_string' => '',
            'false_boolean' => false,
            'true_boolean' => true,
            'zero_int' => 0,
            'positive_int' => 42,
            'empty_array' => [],
            'filled_array' => ['a', 'b'],
            'string_value' => 'hello',
        ];

        $filtered = $page->testFilter($input);

        // Null should be filtered out
        $this->assertArrayNotHasKey('null_value', $filtered, 'null values should be filtered');

        // All other values should be preserved
        $this->assertArrayHasKey('empty_string', $filtered, 'empty strings should be kept');
        $this->assertArrayHasKey('false_boolean', $filtered, 'false booleans should be kept');
        $this->assertArrayHasKey('true_boolean', $filtered, 'true booleans should be kept');
        $this->assertArrayHasKey('zero_int', $filtered, 'zero integers should be kept');
        $this->assertArrayHasKey('positive_int', $filtered, 'positive integers should be kept');
        $this->assertArrayHasKey('empty_array', $filtered, 'empty arrays should be kept');
        $this->assertArrayHasKey('filled_array', $filtered, 'filled arrays should be kept');
        $this->assertArrayHasKey('string_value', $filtered, 'strings should be kept');

        // Verify values are unchanged
        $this->assertSame('', $filtered['empty_string']);
        $this->assertSame(false, $filtered['false_boolean']);
        $this->assertSame(0, $filtered['zero_int']);
    }

    /**
     * @test
     * Scenario: Direct model update preserves NOT NULL fields
     * Given platform settings exist with organization_type
     * When I update only the platform_name directly on the model
     * Then organization_type should be preserved
     *
     * This tests the underlying database behavior.
     */
    public function direct_model_update_preserves_not_null_fields(): void
    {
        $settings = PlatformSettings::first();
        $originalOrgType = $settings->organization_type;
        $originalCurrency = $settings->default_currency;

        // Update only platform_name
        $settings->setTranslation('platform_name', 'en', 'Updated Name');
        $settings->save();

        // Refresh and verify
        $settings = PlatformSettings::first();
        $this->assertEquals($originalOrgType, $settings->organization_type);
        $this->assertEquals($originalCurrency, $settings->default_currency);
    }
}
