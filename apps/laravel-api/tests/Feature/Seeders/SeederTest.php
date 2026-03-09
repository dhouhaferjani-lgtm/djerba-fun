<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\PlatformSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\VendorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * BDD tests for database seeders.
 *
 * These tests verify that seeders create expected data correctly,
 * preventing issues where admin users or platform settings are missing.
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // ADMIN USER SEEDING TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: VendorSeeder creates admin user with correct credentials
     * Given the database is empty
     * When I run the VendorSeeder
     * Then an admin user should exist with email "admin@djerba.fun"
     * And the admin user password should be "password"
     * And the admin user should have role "admin"
     * And the admin user should have status "active"
     */
    public function vendor_seeder_creates_admin_user_with_correct_credentials(): void
    {
        // Given: Database is empty (RefreshDatabase trait handles this)
        $this->assertDatabaseCount('users', 0);

        // When: Run VendorSeeder
        $this->seed(VendorSeeder::class);

        // Then: Admin user should exist with correct attributes
        $admin = User::where('email', 'admin@djerba.fun')->first();

        $this->assertNotNull($admin, 'Admin user should be created');
        $this->assertEquals('admin@djerba.fun', $admin->email);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertEquals(UserStatus::ACTIVE, $admin->status);
        $this->assertNotNull($admin->email_verified_at, 'Admin email should be verified');

        // Verify password is correct
        $this->assertTrue(
            Hash::check('password', $admin->password),
            'Admin password should be "password"'
        );
    }

    /**
     * @test
     * Scenario: VendorSeeder creates test traveler for E2E tests
     * Given the database is empty
     * When I run the VendorSeeder
     * Then a traveler user should exist with email "traveler@test.com"
     * And the traveler should have role "traveler"
     */
    public function vendor_seeder_creates_test_traveler(): void
    {
        // Given & When
        $this->seed(VendorSeeder::class);

        // Then
        $traveler = User::where('email', 'traveler@test.com')->first();

        $this->assertNotNull($traveler, 'Test traveler should be created');
        $this->assertEquals(UserRole::TRAVELER, $traveler->role);
        $this->assertEquals(UserStatus::ACTIVE, $traveler->status);
        $this->assertTrue(
            Hash::check('TestPassword123!', $traveler->password),
            'Test traveler password should be "TestPassword123!"'
        );
    }

    /**
     * @test
     * Scenario: VendorSeeder creates vendor users with profiles
     * Given the database is empty
     * When I run the VendorSeeder
     * Then vendor users should exist with vendor profiles
     */
    public function vendor_seeder_creates_vendors_with_profiles(): void
    {
        // Given & When
        $this->seed(VendorSeeder::class);

        // Then
        $vendor = User::where('email', 'vendor@djerba.fun')->first();

        $this->assertNotNull($vendor, 'Main vendor should be created');
        $this->assertEquals(UserRole::VENDOR, $vendor->role);
        $this->assertNotNull($vendor->vendorProfile, 'Vendor should have a profile');
        $this->assertEquals('Djerba Fun', $vendor->vendorProfile->company_name);
    }

    // =========================================================================
    // PLATFORM SETTINGS SEEDING TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: PlatformSettingsSeeder creates platform settings
     * Given the database is empty
     * When I run the PlatformSettingsSeeder
     * Then platform settings should exist
     * And platform_name should be set for both locales
     * And organization_type should not be null
     */
    public function platform_settings_seeder_creates_settings_with_required_fields(): void
    {
        // Given: Database is empty
        $this->assertDatabaseCount('platform_settings', 0);

        // When: Run PlatformSettingsSeeder
        $this->seed(PlatformSettingsSeeder::class);

        // Then: Platform settings should exist with required fields
        $settings = PlatformSettings::first();

        $this->assertNotNull($settings, 'Platform settings should be created');
        $this->assertEquals('Djerba Fun', $settings->getTranslation('platform_name', 'en'));
        $this->assertEquals('Djerba Fun', $settings->getTranslation('platform_name', 'fr'));
        $this->assertEquals('TravelAgency', $settings->organization_type);
        $this->assertEquals('TND', $settings->default_currency);
        $this->assertNotNull($settings->support_email);
    }

    /**
     * @test
     * Scenario: PlatformSettingsSeeder is idempotent
     * Given platform settings already exist
     * When I run the PlatformSettingsSeeder again
     * Then only one platform settings record should exist
     */
    public function platform_settings_seeder_is_idempotent(): void
    {
        // Given: Seed once
        $this->seed(PlatformSettingsSeeder::class);
        $this->assertDatabaseCount('platform_settings', 1);

        // When: Seed again
        $this->seed(PlatformSettingsSeeder::class);

        // Then: Still only one record
        $this->assertDatabaseCount('platform_settings', 1);
    }

    // =========================================================================
    // DATABASE SEEDER INTEGRATION TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: DatabaseSeeder creates all required data
     * Given the database is empty
     * When I run the full DatabaseSeeder
     * Then admin user should exist
     * And platform settings should exist
     * And test traveler should exist
     */
    public function database_seeder_creates_all_required_data(): void
    {
        // Given: Database is empty
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('platform_settings', 0);

        // When: Run full DatabaseSeeder
        $this->seed(DatabaseSeeder::class);

        // Then: All required data should exist
        $this->assertDatabaseHas('users', ['email' => 'admin@djerba.fun']);
        $this->assertDatabaseHas('users', ['email' => 'traveler@test.com']);
        $this->assertDatabaseHas('users', ['email' => 'vendor@djerba.fun']);
        $this->assertDatabaseCount('platform_settings', 1);
    }

    /**
     * @test
     * Scenario: PlatformSettingsSeeder runs before VendorSeeder in DatabaseSeeder
     * This ensures proper ordering of dependencies.
     */
    public function database_seeder_includes_platform_settings_seeder(): void
    {
        // When: Run full DatabaseSeeder
        $this->seed(DatabaseSeeder::class);

        // Then: Platform settings should exist (proves it's included)
        $this->assertDatabaseCount('platform_settings', 1);

        $settings = PlatformSettings::first();
        $this->assertNotNull($settings->organization_type, 'organization_type should be set');
    }

    // =========================================================================
    // ADMIN LOGIN VERIFICATION TEST
    // =========================================================================

    /**
     * @test
     * Scenario: Admin can access Filament dashboard after seeding
     * Given the database has been seeded
     * When admin is authenticated
     * Then admin should be able to access the admin dashboard
     */
    public function admin_can_access_filament_dashboard_after_seeding(): void
    {
        // Given: Database is seeded
        $this->seed(VendorSeeder::class);

        $admin = User::where('email', 'admin@djerba.fun')->first();
        $this->assertNotNull($admin, 'Admin user should exist after seeding');

        // When: Admin accesses the dashboard
        $response = $this->actingAs($admin)->get('/admin');

        // Then: Should be able to access (200 or redirect to a panel page)
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            'Admin should be able to access the dashboard'
        );
    }

    /**
     * @test
     * Scenario: Admin password hash is valid after seeding
     * Given the database has been seeded
     * When I check the admin password hash
     * Then "password" should validate against the stored hash
     */
    public function admin_password_is_valid_after_seeding(): void
    {
        // Given: Database is seeded
        $this->seed(VendorSeeder::class);

        // When: Get admin user
        $admin = User::where('email', 'admin@djerba.fun')->first();

        // Then: Password should be valid
        $this->assertNotNull($admin);
        $this->assertTrue(
            Hash::check('password', $admin->password),
            'Password "password" should match the stored hash'
        );
    }
}
