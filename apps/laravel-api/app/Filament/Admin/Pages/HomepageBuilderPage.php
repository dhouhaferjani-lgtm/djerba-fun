<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\PlatformSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class HomepageBuilderPage extends Page
{
    protected static ?string $navigationIcon = null; // Icon removed - Content group has icon

    protected static string $view = 'filament.admin.pages.homepage-builder';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'homepage-builder';

    protected static ?string $navigationLabel = 'Homepage Builder';

    protected static ?string $title = 'Homepage Builder';

    public array $sections = [];

    public ?PlatformSettings $settings = null;

    /**
     * Section definitions with metadata.
     * Maps section IDs to their display info and edit routes.
     */
    protected array $sectionDefinitions = [
        'hero' => [
            'label' => 'Hero Banner',
            'description' => 'Main hero section with banner image/video and headline',
            'icon' => 'heroicon-o-photo',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'logo-branding',
            'required' => true,
        ],
        'marketing_mosaic' => [
            'label' => 'Brand Pillars / Marketing Mosaic',
            'description' => 'Three brand pillar cards with images and text',
            'icon' => 'heroicon-o-squares-plus',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'logo-branding',
            'required' => false,
        ],
        'featured_packages' => [
            'label' => 'Featured Packages',
            'description' => 'Up to 3 featured listings (mark listings as Featured)',
            'icon' => 'heroicon-o-star',
            'editRoute' => 'filament.admin.resources.listings.index',
            'editTab' => null,
            'editHint' => 'Mark listings as "Featured" in Listings management',
            'required' => false,
        ],
        'promo_banner' => [
            'label' => 'Event of the Year / Promo Banner',
            'description' => 'Promotional banner for featured event',
            'icon' => 'heroicon-o-megaphone',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'event-of-the-year',
            'required' => false,
        ],
        'experience_categories' => [
            'label' => 'Experience Categories',
            'description' => 'Activity type category cards',
            'icon' => 'heroicon-o-tag',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'experience-categories',
            'required' => false,
        ],
        'testimonials' => [
            'label' => 'Testimonials',
            'description' => 'Customer testimonials carousel',
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'testimonials',
            'required' => false,
        ],
        'destinations' => [
            'label' => 'Featured Destinations',
            'description' => 'Bento grid of featured destinations',
            'icon' => 'heroicon-o-map',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'destinations',
            'required' => false,
        ],
        'cta' => [
            'label' => 'Custom Trip Request',
            'description' => 'Contact section for custom trip inquiries',
            'icon' => 'heroicon-o-cursor-arrow-rays',
            'editRoute' => null,
            'editTab' => null,
            'editHint' => 'Text managed via translations (messages/en.json, fr.json)',
            'required' => false,
        ],
        'blog' => [
            'label' => 'Latest Blog Posts',
            'description' => 'Featured blog posts section',
            'icon' => 'heroicon-o-newspaper',
            'editRoute' => 'filament.admin.resources.blog-posts.index',
            'editTab' => null,
            'required' => false,
        ],
        'newsletter' => [
            'label' => 'Newsletter Signup',
            'description' => 'Email newsletter subscription form',
            'icon' => 'heroicon-o-envelope',
            'editRoute' => 'filament.admin.pages.platform-settings',
            'editTab' => 'newsletter',
            'required' => false,
        ],
    ];

    public function mount(): void
    {
        $this->settings = PlatformSettings::first() ?? PlatformSettings::create([]);
        $this->loadSections();
    }

    /**
     * Load sections from database or use defaults.
     */
    protected function loadSections(): void
    {
        $storedSections = $this->settings->homepage_sections['sections'] ?? null;

        if ($storedSections) {
            // Merge stored order with definitions
            $this->sections = collect($storedSections)
                ->map(fn ($s) => array_merge(
                    $this->sectionDefinitions[$s['id']] ?? [],
                    $s
                ))
                ->sortBy('order')
                ->values()
                ->toArray();
        } else {
            // Use default order from definitions
            $this->sections = collect($this->sectionDefinitions)
                ->map(fn ($def, $id) => array_merge($def, [
                    'id' => $id,
                    'enabled' => true,
                    'order' => array_search($id, array_keys($this->sectionDefinitions)),
                ]))
                ->values()
                ->toArray();
        }
    }

    /**
     * Update section order after drag-drop.
     */
    public function updateSectionOrder(array $orderedIds): void
    {
        $this->sections = collect($orderedIds)
            ->map(fn ($id, $index) => array_merge(
                collect($this->sections)->firstWhere('id', $id) ?? [],
                ['order' => $index]
            ))
            ->toArray();

        $this->saveSettings();
    }

    /**
     * Toggle section visibility.
     */
    public function toggleSection(string $sectionId): void
    {
        $this->sections = collect($this->sections)
            ->map(function ($section) use ($sectionId) {
                if ($section['id'] === $sectionId) {
                    // Prevent disabling required sections
                    if ($section['required'] ?? false) {
                        Notification::make()
                            ->warning()
                            ->title('Cannot disable required section')
                            ->body('The Hero section cannot be disabled.')
                            ->send();

                        return $section;
                    }
                    $section['enabled'] = ! ($section['enabled'] ?? true);
                }

                return $section;
            })
            ->toArray();

        $this->saveSettings();
    }

    /**
     * Save section configuration to database.
     */
    protected function saveSettings(): void
    {
        $sectionsData = collect($this->sections)
            ->map(fn ($s) => [
                'id' => $s['id'],
                'enabled' => $s['enabled'] ?? true,
                'order' => $s['order'] ?? 0,
            ])
            ->toArray();

        $this->settings->homepage_sections = ['sections' => $sectionsData];
        $this->settings->save();

        PlatformSettings::clearCache();

        Notification::make()
            ->success()
            ->title('Homepage layout saved')
            ->send();
    }

    /**
     * Get edit URL for a section.
     */
    public function getEditUrl(string $sectionId): ?string
    {
        $section = collect($this->sections)->firstWhere('id', $sectionId);
        $route = $section['editRoute'] ?? null;

        if (! $route) {
            return null;
        }

        try {
            $url = route($route);

            // Append tab parameter for PlatformSettings
            if ($section['editTab'] ?? null) {
                $url .= '?activeTab=' . urlencode($section['editTab']);
            }

            return $url;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview Homepage')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(config('app.frontend_url', 'http://localhost:3000'))
                ->openUrlInNewTab(),

            Action::make('reset')
                ->label('Reset to Default')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Homepage Layout')
                ->modalDescription('This will reset all sections to their default order and visibility. This cannot be undone.')
                ->action(function () {
                    $this->settings->homepage_sections = null;
                    $this->settings->save();
                    PlatformSettings::clearCache();
                    $this->loadSections();

                    Notification::make()
                        ->success()
                        ->title('Homepage layout reset to defaults')
                        ->send();
                }),
        ];
    }
}
