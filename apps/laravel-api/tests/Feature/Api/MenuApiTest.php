<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Statikbe\FilamentFlexibleContentBlockPages\Models\Menu;
use Statikbe\FilamentFlexibleContentBlockPages\Models\MenuItem;
use Tests\TestCase;

/**
 * Tests for the CMS-managed menus API endpoint.
 *
 * These tests verify the /api/v1/menus/{menuCode} endpoint returns
 * correctly structured and localized menu data.
 */
class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test menu endpoint returns expected structure.
     */
    public function test_menu_endpoint_returns_expected_structure(): void
    {
        // Arrange
        $menu = Menu::create([
            'code' => 'test-menu',
            'name' => 'Test Menu',
            'description' => 'A test menu',
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'Home', 'fr' => 'Accueil'],
            'url' => ['en' => '/', 'fr' => '/'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 1,
            'parent_id' => 0,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/test-menu');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'name',
                'items' => [
                    '*' => [
                        'id',
                        'label',
                        'url',
                        'target',
                        'order',
                        'parent_id',
                    ],
                ],
            ]);
    }

    /**
     * Test menu endpoint returns localized labels for French.
     */
    public function test_menu_endpoint_returns_french_labels(): void
    {
        // Arrange
        $menu = Menu::create([
            'code' => 'header',
            'name' => 'Header Menu',
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'Home', 'fr' => 'Accueil'],
            'url' => ['en' => '/', 'fr' => '/'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 1,
            'parent_id' => 0,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'Activities', 'fr' => 'Activités'],
            'url' => ['en' => '/listings?type=tour', 'fr' => '/listings?type=tour'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 2,
            'parent_id' => 0,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/header?locale=fr');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('items.0.label', 'Accueil')
            ->assertJsonPath('items.1.label', 'Activités');
    }

    /**
     * Test menu endpoint returns localized labels for English.
     */
    public function test_menu_endpoint_returns_english_labels(): void
    {
        // Arrange
        $menu = Menu::create([
            'code' => 'header',
            'name' => 'Header Menu',
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'Home', 'fr' => 'Accueil'],
            'url' => ['en' => '/', 'fr' => '/'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 1,
            'parent_id' => 0,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/header?locale=en');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('items.0.label', 'Home');
    }

    /**
     * Test menu endpoint returns 404 for non-existent menu code.
     */
    public function test_menu_endpoint_returns_404_for_non_existent_menu(): void
    {
        // Act
        $response = $this->getJson('/api/v1/menus/non-existent-menu');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test menu items are ordered correctly.
     */
    public function test_menu_items_are_ordered_correctly(): void
    {
        // Arrange
        $menu = Menu::create([
            'code' => 'ordered-menu',
            'name' => 'Ordered Menu',
        ]);

        // Create items in reverse order to test sorting
        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'Third'],
            'url' => ['en' => '/third'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 3,
            'parent_id' => 0,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'First'],
            'url' => ['en' => '/first'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 1,
            'parent_id' => 0,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'Second'],
            'url' => ['en' => '/second'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 2,
            'parent_id' => 0,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/ordered-menu?locale=en');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('items');

        $this->assertEquals('First', $items[0]['label']);
        $this->assertEquals('Second', $items[1]['label']);
        $this->assertEquals('Third', $items[2]['label']);
    }

    /**
     * Test seeded header menu has correct number of items.
     */
    public function test_seeded_header_menu_has_correct_item_count(): void
    {
        // Arrange - run the MenuSeeder
        $this->seed(MenuSeeder::class);

        // Act
        $response = $this->getJson('/api/v1/menus/header?locale=en');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('items');

        // Header menu should have 7 items as per MenuSeeder
        $this->assertCount(7, $items);
    }

    /**
     * Test seeded footer-company menu has correct items.
     */
    public function test_seeded_footer_company_menu_has_correct_items(): void
    {
        // Arrange
        $this->seed(MenuSeeder::class);

        // Act
        $response = $this->getJson('/api/v1/menus/footer-company?locale=en');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('items');

        // Footer company should have 4 items
        $this->assertCount(4, $items);

        // Verify first item is About Us
        $this->assertEquals('About Us', $items[0]['label']);
        $this->assertEquals('/about', $items[0]['url']);
    }

    /**
     * Test seeded footer-support menu has correct items.
     */
    public function test_seeded_footer_support_menu_has_correct_items(): void
    {
        // Arrange
        $this->seed(MenuSeeder::class);

        // Act
        $response = $this->getJson('/api/v1/menus/footer-support?locale=en');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('items');

        // Footer support should have 3 items
        $this->assertCount(3, $items);

        // Verify items match expected
        $this->assertEquals('My Account', $items[0]['label']);
        $this->assertEquals('Terms & Conditions', $items[1]['label']);
        $this->assertEquals('Contact Us', $items[2]['label']);
    }

    /**
     * Test seeded footer-legal menu has correct items.
     */
    public function test_seeded_footer_legal_menu_has_correct_items(): void
    {
        // Arrange
        $this->seed(MenuSeeder::class);

        // Act
        $response = $this->getJson('/api/v1/menus/footer-legal?locale=en');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('items');

        // Footer legal should have 2 items
        $this->assertCount(2, $items);

        // Verify items
        $this->assertEquals('Terms & Conditions', $items[0]['label']);
        $this->assertEquals('Privacy Policy', $items[1]['label']);
    }

    /**
     * Test seeded header menu French labels match expected values.
     */
    public function test_seeded_header_menu_french_labels(): void
    {
        // Arrange
        $this->seed(MenuSeeder::class);

        // Act
        $response = $this->getJson('/api/v1/menus/header?locale=fr');

        // Assert
        $response->assertStatus(200);
        $items = $response->json('items');

        // Verify French labels
        $this->assertEquals('Accueil', $items[0]['label']);
        $this->assertEquals('Activités', $items[1]['label']);
        $this->assertEquals('Nautique', $items[2]['label']);
        $this->assertEquals('Hébergements', $items[3]['label']);
        $this->assertEquals('Événements', $items[4]['label']);
        $this->assertEquals('Blog', $items[5]['label']);
        $this->assertEquals('Voyage Sur Mesure', $items[6]['label']);
    }

    /**
     * Test menu endpoint defaults to English locale when not specified.
     */
    public function test_menu_endpoint_defaults_to_english_locale(): void
    {
        // Arrange
        $menu = Menu::create([
            'code' => 'test-default',
            'name' => 'Test Default',
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'English Label', 'fr' => 'Label Français'],
            'url' => ['en' => '/test', 'fr' => '/test'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 1,
            'parent_id' => 0,
        ]);

        // Act - no locale parameter
        $response = $this->getJson('/api/v1/menus/test-default');

        // Assert - should default to English
        $response->assertStatus(200)
            ->assertJsonPath('items.0.label', 'English Label');
    }

    /**
     * Test menu returns correct URLs for each locale.
     */
    public function test_menu_returns_localized_urls(): void
    {
        // Arrange
        $menu = Menu::create([
            'code' => 'url-test',
            'name' => 'URL Test Menu',
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'link_type' => MenuItem::LINK_TYPE_URL,
            'label' => ['en' => 'About', 'fr' => 'À Propos'],
            'url' => ['en' => '/about', 'fr' => '/about'],
            'target' => '_self',
            'is_visible' => true,
            'order' => 1,
            'parent_id' => 0,
        ]);

        // Act
        $responseFr = $this->getJson('/api/v1/menus/url-test?locale=fr');
        $responseEn = $this->getJson('/api/v1/menus/url-test?locale=en');

        // Assert
        $responseFr->assertStatus(200)
            ->assertJsonPath('items.0.url', '/about');

        $responseEn->assertStatus(200)
            ->assertJsonPath('items.0.url', '/about');
    }

    /**
     * Test all four seeded menus exist after running MenuSeeder.
     */
    public function test_all_seeded_menus_exist(): void
    {
        // Arrange
        $this->seed(MenuSeeder::class);

        // Act & Assert - all four menus should be accessible
        $this->getJson('/api/v1/menus/header')->assertStatus(200);
        $this->getJson('/api/v1/menus/footer-company')->assertStatus(200);
        $this->getJson('/api/v1/menus/footer-support')->assertStatus(200);
        $this->getJson('/api/v1/menus/footer-legal')->assertStatus(200);
    }
}
