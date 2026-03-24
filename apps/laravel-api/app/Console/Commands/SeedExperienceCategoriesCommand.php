<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformSettings;
use Illuminate\Console\Command;

class SeedExperienceCategoriesCommand extends Command
{
    protected $signature = 'platform:seed-categories {--force : Overwrite existing categories}';

    protected $description = 'Seed default experience categories into CMS (tours, nautical, accommodation, events)';

    public function handle(): int
    {
        $settings = PlatformSettings::first();

        if (! $settings) {
            $this->error('No platform settings found. Please run migrations and seeders first.');

            return self::FAILURE;
        }

        $existingCategories = $settings->experience_categories ?? [];
        $force = $this->option('force');

        if (! empty($existingCategories) && ! $force) {
            $this->info('Experience categories already exist. Use --force to overwrite.');
            $this->table(
                ['ID', 'Name (EN)', 'Name (FR)', 'Link'],
                collect($existingCategories)->map(fn ($c) => [
                    $c['id'] ?? 'N/A',
                    $c['name_en'] ?? 'N/A',
                    $c['name_fr'] ?? 'N/A',
                    $c['link'] ?? 'N/A',
                ])->toArray()
            );

            return self::SUCCESS;
        }

        $defaultCategories = [
            [
                'id' => 'tour',
                'display_order' => 0,
                'name_en' => 'Tours',
                'name_fr' => 'Excursions',
                'description_en' => 'Discover guided tours and adventures',
                'description_fr' => 'Découvrez des excursions et aventures guidées',
                'image' => null,
                'link' => '/listings?type=tour',
            ],
            [
                'id' => 'nautical',
                'display_order' => 1,
                'name_en' => 'Nautical Activities',
                'name_fr' => 'Activités Nautiques',
                'description_en' => 'Water sports and boat tours',
                'description_fr' => 'Sports nautiques et tours en bateau',
                'image' => null,
                'link' => '/listings?type=nautical',
            ],
            [
                'id' => 'accommodation',
                'display_order' => 2,
                'name_en' => 'Accommodations',
                'name_fr' => 'Hébergements',
                'description_en' => 'Hotels and guesthouses',
                'description_fr' => 'Hôtels et maisons d\'hôtes',
                'image' => null,
                'link' => '/listings?type=accommodation',
            ],
            [
                'id' => 'event',
                'display_order' => 3,
                'name_en' => 'Events',
                'name_fr' => 'Événements',
                'description_en' => 'Festivals, workshops and more',
                'description_fr' => 'Festivals, ateliers et plus',
                'image' => null,
                'link' => '/listings?type=event',
            ],
        ];

        // Use update() to ensure the data is persisted
        $settings->update(['experience_categories' => $defaultCategories]);

        // Also try direct DB update as fallback
        \Illuminate\Support\Facades\DB::table('platform_settings')
            ->where('id', $settings->id)
            ->update(['experience_categories' => json_encode($defaultCategories)]);

        $this->info('Experience categories seeded successfully!');
        $this->table(
            ['ID', 'Name (EN)', 'Name (FR)', 'Link'],
            collect($defaultCategories)->map(fn ($c) => [
                $c['id'],
                $c['name_en'],
                $c['name_fr'],
                $c['link'],
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
