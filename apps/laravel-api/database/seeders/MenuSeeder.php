<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Statikbe\FilamentFlexibleContentBlockPages\Models\Menu;
use Statikbe\FilamentFlexibleContentBlockPages\Models\MenuItem;

/**
 * Seeds CMS-managed menus with the exact current navigation structure.
 * This ensures zero regressions when migrating from hardcoded menus to CMS-managed menus.
 *
 * Menus created:
 * - header: Main navigation menu
 * - footer-company: Footer company links section
 * - footer-support: Footer support links section
 * - footer-legal: Footer bottom bar legal links
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHeaderMenu();
        $this->seedFooterCompanyMenu();
        $this->seedFooterSupportMenu();
        $this->seedFooterLegalMenu();
    }

    /**
     * Header navigation menu - matches Header.tsx navLinks array exactly.
     */
    private function seedHeaderMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['code' => 'header'],
            [
                'name' => 'Header Menu',
                'description' => 'Main navigation menu displayed in the site header',
            ]
        );

        // Delete existing items to ensure clean state
        $menu->allMenuItems()->delete();

        $items = [
            [
                'order' => 1,
                'label' => ['en' => 'Home', 'fr' => 'Accueil'],
                'url' => ['en' => '/', 'fr' => '/'],
            ],
            [
                'order' => 2,
                'label' => ['en' => 'Activities', 'fr' => 'Activités'],
                'url' => ['en' => '/listings?type=tour', 'fr' => '/listings?type=tour'],
            ],
            [
                'order' => 3,
                'label' => ['en' => 'Nautical', 'fr' => 'Nautique'],
                'url' => ['en' => '/listings?type=nautical', 'fr' => '/listings?type=nautical'],
            ],
            [
                'order' => 4,
                'label' => ['en' => 'Accommodations', 'fr' => 'Hébergements'],
                'url' => ['en' => '/listings?type=accommodation', 'fr' => '/listings?type=accommodation'],
            ],
            [
                'order' => 5,
                'label' => ['en' => 'Events', 'fr' => 'Événements'],
                'url' => ['en' => '/listings?type=event', 'fr' => '/listings?type=event'],
            ],
            [
                'order' => 6,
                'label' => ['en' => 'Blog', 'fr' => 'Blog'],
                'url' => ['en' => '/blog', 'fr' => '/blog'],
            ],
            [
                'order' => 7,
                'label' => ['en' => 'Request Custom Trip', 'fr' => 'Voyage Sur Mesure'],
                'url' => ['en' => '/custom-trip', 'fr' => '/custom-trip'],
            ],
        ];

        $this->createMenuItems($menu, $items);
    }

    /**
     * Footer company section - matches Footer.tsx company links.
     */
    private function seedFooterCompanyMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['code' => 'footer-company'],
            [
                'name' => 'Footer Company',
                'description' => 'Company links in the footer (About, Blog, etc.)',
            ]
        );

        $menu->allMenuItems()->delete();

        $items = [
            [
                'order' => 1,
                'label' => ['en' => 'About Us', 'fr' => 'Qui sommes-nous'],
                'url' => ['en' => '/about', 'fr' => '/about'],
            ],
            [
                'order' => 2,
                'label' => ['en' => 'Blog', 'fr' => 'Blog'],
                'url' => ['en' => '/blog', 'fr' => '/blog'],
            ],
            [
                'order' => 3,
                'label' => ['en' => 'Activities', 'fr' => 'Activités'],
                'url' => ['en' => '/listings?type=tour', 'fr' => '/listings?type=tour'],
            ],
            [
                'order' => 4,
                'label' => ['en' => 'Events', 'fr' => 'Événements'],
                'url' => ['en' => '/listings?type=event', 'fr' => '/listings?type=event'],
            ],
        ];

        $this->createMenuItems($menu, $items);
    }

    /**
     * Footer support section - matches Footer.tsx support links.
     */
    private function seedFooterSupportMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['code' => 'footer-support'],
            [
                'name' => 'Footer Support',
                'description' => 'Support links in the footer (Account, Terms, Contact)',
            ]
        );

        $menu->allMenuItems()->delete();

        $items = [
            [
                'order' => 1,
                'label' => ['en' => 'My Account', 'fr' => 'Mon compte'],
                'url' => ['en' => '/dashboard', 'fr' => '/dashboard'],
            ],
            [
                'order' => 2,
                'label' => ['en' => 'Terms & Conditions', 'fr' => 'CGU'],
                'url' => ['en' => '/terms', 'fr' => '/terms'],
            ],
            [
                'order' => 3,
                'label' => ['en' => 'Contact Us', 'fr' => 'Nous contacter'],
                'url' => ['en' => '/contact', 'fr' => '/contact'],
            ],
        ];

        $this->createMenuItems($menu, $items);
    }

    /**
     * Footer legal links - bottom bar links (Terms, Privacy).
     */
    private function seedFooterLegalMenu(): void
    {
        $menu = Menu::updateOrCreate(
            ['code' => 'footer-legal'],
            [
                'name' => 'Footer Legal',
                'description' => 'Legal links in the footer bottom bar',
            ]
        );

        $menu->allMenuItems()->delete();

        $items = [
            [
                'order' => 1,
                'label' => ['en' => 'Terms & Conditions', 'fr' => 'CGU'],
                'url' => ['en' => '/terms', 'fr' => '/terms'],
            ],
            [
                'order' => 2,
                'label' => ['en' => 'Privacy Policy', 'fr' => 'Confidentialité'],
                'url' => ['en' => '/privacy', 'fr' => '/privacy'],
            ],
        ];

        $this->createMenuItems($menu, $items);
    }

    /**
     * Helper to create menu items for a menu.
     */
    private function createMenuItems(Menu $menu, array $items): void
    {
        foreach ($items as $itemData) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'link_type' => MenuItem::LINK_TYPE_URL,
                'label' => $itemData['label'],
                'url' => $itemData['url'],
                'target' => '_self',
                'is_visible' => true,
                'use_model_title' => false,
                'order' => $itemData['order'],
                'parent_id' => 0,
            ]);
        }
    }
}
