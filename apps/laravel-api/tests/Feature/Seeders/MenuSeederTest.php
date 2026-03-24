<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Statikbe\FilamentFlexibleContentBlockPages\Models\Menu;
use Statikbe\FilamentFlexibleContentBlockPages\Models\MenuItem;
use Tests\TestCase;

/**
 * BDD tests for MenuSeeder.
 *
 * These tests verify that the MenuSeeder creates the exact navigation
 * structure currently hardcoded in Header.tsx and Footer.tsx.
 */
class MenuSeederTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HEADER MENU TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: MenuSeeder creates header menu with 7 items
     * Given the database is empty
     * When I run the MenuSeeder
     * Then a menu with code "header" should exist
     * And the header menu should have exactly 7 items
     */
    public function menu_seeder_creates_header_menu_with_correct_item_count(): void
    {
        // Given: Database is empty
        $this->assertEquals(0, Menu::count(), 'Database should start empty');

        // When: Run MenuSeeder
        $this->seed(MenuSeeder::class);

        // Then: Header menu should exist with 7 items
        $headerMenu = Menu::where('code', 'header')->first();

        $this->assertNotNull($headerMenu, 'Header menu should be created');
        $this->assertEquals('Header Menu', $headerMenu->name);
        $this->assertCount(7, $headerMenu->allMenuItems);
    }

    /**
     * @test
     * Scenario: Header menu items match Header.tsx navLinks exactly
     * Given the MenuSeeder has run
     * When I check the header menu items
     * Then they should match the hardcoded navigation structure
     */
    public function header_menu_items_match_expected_navigation(): void
    {
        // Given
        $this->seed(MenuSeeder::class);

        // When
        $items = Menu::where('code', 'header')->first()
            ->allMenuItems()
            ->orderBy('order')
            ->get();

        // Then: Verify each item matches expected structure
        $expectedItems = [
            ['order' => 1, 'label_en' => 'Home', 'label_fr' => 'Accueil', 'url' => '/'],
            ['order' => 2, 'label_en' => 'Activities', 'label_fr' => 'Activités', 'url' => '/listings?type=tour'],
            ['order' => 3, 'label_en' => 'Nautical', 'label_fr' => 'Nautique', 'url' => '/listings?type=nautical'],
            ['order' => 4, 'label_en' => 'Accommodations', 'label_fr' => 'Hébergements', 'url' => '/listings?type=accommodation'],
            ['order' => 5, 'label_en' => 'Events', 'label_fr' => 'Événements', 'url' => '/listings?type=event'],
            ['order' => 6, 'label_en' => 'Blog', 'label_fr' => 'Blog', 'url' => '/blog'],
            ['order' => 7, 'label_en' => 'Request Custom Trip', 'label_fr' => 'Voyage Sur Mesure', 'url' => '/custom-trip'],
        ];

        foreach ($expectedItems as $index => $expected) {
            $this->assertEquals($expected['order'], $items[$index]->order);
            $this->assertEquals($expected['label_en'], $items[$index]->getTranslation('label', 'en'));
            $this->assertEquals($expected['label_fr'], $items[$index]->getTranslation('label', 'fr'));
            $this->assertEquals($expected['url'], $items[$index]->getTranslation('url', 'en'));
        }
    }

    // =========================================================================
    // FOOTER COMPANY MENU TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: MenuSeeder creates footer-company menu with 4 items
     * Given the database is empty
     * When I run the MenuSeeder
     * Then a menu with code "footer-company" should exist
     * And the menu should have exactly 4 items
     */
    public function menu_seeder_creates_footer_company_menu(): void
    {
        // Given & When
        $this->seed(MenuSeeder::class);

        // Then
        $menu = Menu::where('code', 'footer-company')->first();

        $this->assertNotNull($menu, 'Footer company menu should be created');
        $this->assertEquals('Footer Company', $menu->name);
        $this->assertCount(4, $menu->allMenuItems);
    }

    /**
     * @test
     * Scenario: Footer company menu items match Footer.tsx company links
     */
    public function footer_company_menu_items_match_expected(): void
    {
        // Given
        $this->seed(MenuSeeder::class);

        // When
        $items = Menu::where('code', 'footer-company')->first()
            ->allMenuItems()
            ->orderBy('order')
            ->get();

        // Then
        $this->assertEquals('About Us', $items[0]->getTranslation('label', 'en'));
        $this->assertEquals('Qui sommes-nous', $items[0]->getTranslation('label', 'fr'));
        $this->assertEquals('/about', $items[0]->getTranslation('url', 'en'));

        $this->assertEquals('Blog', $items[1]->getTranslation('label', 'en'));
        $this->assertEquals('Activities', $items[2]->getTranslation('label', 'en'));
        $this->assertEquals('Events', $items[3]->getTranslation('label', 'en'));
    }

    // =========================================================================
    // FOOTER SUPPORT MENU TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: MenuSeeder creates footer-support menu with 3 items
     */
    public function menu_seeder_creates_footer_support_menu(): void
    {
        // Given & When
        $this->seed(MenuSeeder::class);

        // Then
        $menu = Menu::where('code', 'footer-support')->first();

        $this->assertNotNull($menu, 'Footer support menu should be created');
        $this->assertCount(3, $menu->allMenuItems);
    }

    /**
     * @test
     * Scenario: Footer support menu items match Footer.tsx support links
     */
    public function footer_support_menu_items_match_expected(): void
    {
        // Given
        $this->seed(MenuSeeder::class);

        // When
        $items = Menu::where('code', 'footer-support')->first()
            ->allMenuItems()
            ->orderBy('order')
            ->get();

        // Then
        $this->assertEquals('My Account', $items[0]->getTranslation('label', 'en'));
        $this->assertEquals('Mon compte', $items[0]->getTranslation('label', 'fr'));
        $this->assertEquals('/dashboard', $items[0]->getTranslation('url', 'en'));

        $this->assertEquals('Terms & Conditions', $items[1]->getTranslation('label', 'en'));
        $this->assertEquals('CGU', $items[1]->getTranslation('label', 'fr'));

        $this->assertEquals('Contact Us', $items[2]->getTranslation('label', 'en'));
        $this->assertEquals('Nous contacter', $items[2]->getTranslation('label', 'fr'));
    }

    // =========================================================================
    // FOOTER LEGAL MENU TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: MenuSeeder creates footer-legal menu with 2 items
     */
    public function menu_seeder_creates_footer_legal_menu(): void
    {
        // Given & When
        $this->seed(MenuSeeder::class);

        // Then
        $menu = Menu::where('code', 'footer-legal')->first();

        $this->assertNotNull($menu, 'Footer legal menu should be created');
        $this->assertCount(2, $menu->allMenuItems);
    }

    /**
     * @test
     * Scenario: Footer legal menu items are Terms and Privacy
     */
    public function footer_legal_menu_items_match_expected(): void
    {
        // Given
        $this->seed(MenuSeeder::class);

        // When
        $items = Menu::where('code', 'footer-legal')->first()
            ->allMenuItems()
            ->orderBy('order')
            ->get();

        // Then
        $this->assertEquals('Terms & Conditions', $items[0]->getTranslation('label', 'en'));
        $this->assertEquals('CGU', $items[0]->getTranslation('label', 'fr'));
        $this->assertEquals('/terms', $items[0]->getTranslation('url', 'en'));

        $this->assertEquals('Privacy Policy', $items[1]->getTranslation('label', 'en'));
        $this->assertEquals('Confidentialité', $items[1]->getTranslation('label', 'fr'));
        $this->assertEquals('/privacy', $items[1]->getTranslation('url', 'en'));
    }

    // =========================================================================
    // IDEMPOTENCY TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: MenuSeeder is idempotent (can run multiple times safely)
     * Given the MenuSeeder has already run
     * When I run the MenuSeeder again
     * Then only 4 menus should exist (not duplicated)
     * And each menu should have the correct item count
     */
    public function menu_seeder_is_idempotent(): void
    {
        // Given: Run seeder once
        $this->seed(MenuSeeder::class);
        $this->assertEquals(4, Menu::count(), 'Should have 4 menus after first seed');

        // When: Run seeder again
        $this->seed(MenuSeeder::class);

        // Then: Still only 4 menus
        $this->assertEquals(4, Menu::count(), 'Should still have 4 menus after second seed');

        // And correct item counts
        $this->assertCount(7, Menu::where('code', 'header')->first()->allMenuItems);
        $this->assertCount(4, Menu::where('code', 'footer-company')->first()->allMenuItems);
        $this->assertCount(3, Menu::where('code', 'footer-support')->first()->allMenuItems);
        $this->assertCount(2, Menu::where('code', 'footer-legal')->first()->allMenuItems);
    }

    // =========================================================================
    // MENU ITEM CONFIGURATION TESTS
    // =========================================================================

    /**
     * @test
     * Scenario: All menu items have correct link type and target
     */
    public function menu_items_have_correct_configuration(): void
    {
        // Given
        $this->seed(MenuSeeder::class);

        // When: Get all menu items
        $items = MenuItem::all();

        // Then: All items should have correct configuration
        foreach ($items as $item) {
            $this->assertEquals(MenuItem::LINK_TYPE_URL, $item->link_type);
            $this->assertEquals('_self', $item->target);
            $this->assertTrue($item->is_visible);
        }
    }

    /**
     * @test
     * Scenario: Total menu items count is correct (7 + 4 + 3 + 2 = 16)
     */
    public function total_menu_items_count_is_correct(): void
    {
        // Given & When
        $this->seed(MenuSeeder::class);

        // Then
        $this->assertEquals(16, MenuItem::count(), 'Total menu items should be 16 (7+4+3+2)');
    }
}
