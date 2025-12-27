<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;

class MigrateListingPersonTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:listing-person-types
                            {--dry-run : Run the migration without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing listings from simple pricing to person type pricing structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no changes will be saved');
        }

        // Get all listings
        $listings = Listing::all();
        $this->info("Found {$listings->count()} listings to process");

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($listings as $listing) {
            try {
                $pricing = $listing->pricing ?? [];

                // Check if already has person_types
                if (! empty($pricing['person_types'])) {
                    $this->line("  [SKIP] {$listing->slug} - Already has person types");
                    $skipped++;
                    continue;
                }

                // Check if has old pricing structure
                if (empty($pricing['tnd_price']) && empty($pricing['eur_price'])) {
                    $this->line("  [SKIP] {$listing->slug} - No pricing data found");
                    $skipped++;
                    continue;
                }

                // Migrate to person_types structure
                $pricing['person_types'] = [
                    [
                        'key' => 'adult',
                        'label' => [
                            'en' => 'Adult',
                            'fr' => 'Adulte',
                        ],
                        'tnd_price' => $pricing['tnd_price'] ?? 0,
                        'eur_price' => $pricing['eur_price'] ?? 0,
                        'min_age' => 18,
                        'max_age' => null,
                        'min_quantity' => 1,
                        'max_quantity' => null,
                    ],
                ];

                // Keep old prices for backward compatibility
                // (in case any legacy code still references them)

                if (! $isDryRun) {
                    $listing->pricing = $pricing;
                    $listing->save();
                }

                $this->info("  [MIGRATED] {$listing->slug} - Added Adult person type (TND: {$pricing['tnd_price']}, EUR: {$pricing['eur_price']})");
                $migrated++;
            } catch (\Exception $e) {
                $this->error("  [ERROR] {$listing->slug} - {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info('Migration Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Migrated', $migrated],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total', $listings->count()],
            ]
        );

        if ($isDryRun) {
            $this->warn('This was a DRY RUN - no changes were saved');
            $this->info('Run without --dry-run to apply changes');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
