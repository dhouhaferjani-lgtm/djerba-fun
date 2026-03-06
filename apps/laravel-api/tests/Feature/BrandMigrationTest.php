<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BDD Tests for Djerba Fun Brand Migration
 *
 * These tests verify that the brand migration from "Go Adventure" / "Evasion Djerba"
 * to "Djerba Fun" is complete across all configurations.
 *
 * Brand: Djerba Fun
 * Domain: djerbafun.com
 * Email: contact@djerba.fun
 */
class BrandMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PlatformSettingsSeeder::class);
    }

    /** @test */
    public function platform_settings_returns_djerba_fun_brand(): void
    {
        $response = $this->getJson('/api/v1/platform/settings?locale=en');

        $response->assertStatus(200);

        // Verify brand name is "Djerba Fun"
        $data = $response->json('data');

        // Check platform identity
        $this->assertStringContainsString('Djerba Fun', $data['platform']['name'] ?? '');

        // Verify no old brand names remain
        $jsonContent = json_encode($data);
        $this->assertStringNotContainsString('Go Adventure', $jsonContent);
        $this->assertStringNotContainsString('Evasion Djerba', $jsonContent);
        $this->assertStringNotContainsString('evasiondjerba', $jsonContent);
    }

    /** @test */
    public function platform_settings_uses_djerba_fun_email_domain(): void
    {
        $response = $this->getJson('/api/v1/platform/settings?locale=en');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Check contact emails use @djerba.fun domain
        $supportEmail = $data['contact']['supportEmail'] ?? '';
        $generalEmail = $data['contact']['generalEmail'] ?? '';

        if (!empty($supportEmail)) {
            $this->assertStringContainsString('@djerba.fun', $supportEmail);
        }
        if (!empty($generalEmail)) {
            $this->assertStringContainsString('@djerba.fun', $generalEmail);
        }
    }

    /** @test */
    public function cors_allows_djerbafun_domain(): void
    {
        // Test that CORS config allows djerbafun.com
        $allowedPatterns = config('cors.allowed_origins_patterns');

        $djerbafunAllowed = false;
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, 'https://www.djerbafun.com')) {
                $djerbafunAllowed = true;
                break;
            }
        }

        $this->assertTrue($djerbafunAllowed, 'CORS should allow djerbafun.com domain');

        // Verify old domain is NOT allowed
        $evasionDjerbaAllowed = false;
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, 'https://www.evasiondjerba.com')) {
                $evasionDjerbaAllowed = true;
                break;
            }
        }

        $this->assertFalse($evasionDjerbaAllowed, 'CORS should NOT allow evasiondjerba.com domain');
    }

    /** @test */
    public function app_name_is_djerba_fun(): void
    {
        $appName = config('app.name');

        $this->assertEquals('Djerba Fun', $appName);
    }

    /** @test */
    public function frontend_url_is_djerbafun(): void
    {
        $frontendUrl = config('app.frontend_url');

        $this->assertStringContainsString('djerbafun.com', $frontendUrl);
        $this->assertStringNotContainsString('evasiondjerba', $frontendUrl);
    }

    /** @test */
    public function mail_from_address_uses_djerba_fun_domain(): void
    {
        $mailFromAddress = config('mail.from.address');

        $this->assertStringContainsString('@djerba.fun', $mailFromAddress);
        $this->assertStringNotContainsString('evasiondjerba', $mailFromAddress);
        $this->assertStringNotContainsString('go-adventure', $mailFromAddress);
    }

    /** @test */
    public function mail_translations_use_djerba_fun_brand(): void
    {
        // Test English mail translations
        $brandName = __('mail.go_adventure', [], 'en');
        $this->assertEquals('Djerba Fun', $brandName);

        $teamName = __('mail.the_team', [], 'en');
        $this->assertStringContainsString('Djerba Fun', $teamName);
        $this->assertStringNotContainsString('Evasion Djerba', $teamName);
        $this->assertStringNotContainsString('Go Adventure', $teamName);

        // Test French mail translations
        $brandNameFr = __('mail.go_adventure', [], 'fr');
        $this->assertEquals('Djerba Fun', $brandNameFr);

        $teamNameFr = __('mail.the_team', [], 'fr');
        $this->assertStringContainsString('Djerba Fun', $teamNameFr);
    }

    /** @test */
    public function no_legacy_brand_references_in_platform_settings_seeder(): void
    {
        // Read the seeder file and check for legacy references
        $seederPath = database_path('seeders/PlatformSettingsSeeder.php');
        $seederContent = file_get_contents($seederPath);

        $this->assertStringNotContainsString('Go Adventure', $seederContent);
        $this->assertStringNotContainsString('Evasion Djerba', $seederContent);
        $this->assertStringNotContainsString('evasiondjerba.com', $seederContent);
        $this->assertStringContainsString('Djerba Fun', $seederContent);
        $this->assertStringContainsString('djerbafun.com', $seederContent);
    }

    /** @test */
    public function french_locale_returns_djerba_fun_brand(): void
    {
        $response = $this->getJson('/api/v1/platform/settings?locale=fr');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify brand name in French context
        $this->assertStringContainsString('Djerba Fun', $data['platform']['name'] ?? '');

        // Verify no old brand names in French response
        $jsonContent = json_encode($data);
        $this->assertStringNotContainsString('Go Adventure', $jsonContent);
        $this->assertStringNotContainsString('Evasion Djerba', $jsonContent);
    }
}
