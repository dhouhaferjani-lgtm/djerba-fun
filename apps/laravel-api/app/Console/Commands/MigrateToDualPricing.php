<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\IncomePricingService;
use Illuminate\Console\Command;

class MigrateToDualPricing extends Command
{
    protected $signature = 'pricing:migrate-dual
                           {--dry-run : Preview changes without saving}
                           {--force : Skip confirmation prompt}';

    protected $description = 'Migrate existing listings from single currency to dual pricing (TND + EUR)';

    public function handle(IncomePricingService $pricingService): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('🔍 Scanning listings for dual pricing migration...');
        $this->newLine();

        // Get all listings
        $listings = Listing::all();
        $totalListings = $listings->count();

        if ($totalListings === 0) {
            $this->warn('No listings found.');

            return self::SUCCESS;
        }

        // Analyze listings
        $needsMigration = $listings->filter(function ($listing) {
            $pricing = $listing->pricing;

            return ! isset($pricing['tnd_price']) || ! isset($pricing['eur_price']);
        });

        $migrationCount = $needsMigration->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Listings', $totalListings],
                ['Already Migrated', $totalListings - $migrationCount],
                ['Needs Migration', $migrationCount],
            ]
        );

        if ($migrationCount === 0) {
            $this->info('✅ All listings already have dual pricing!');

            return self::SUCCESS;
        }

        // Confirm migration
        if (! $isDryRun && ! $isForced) {
            if (! $this->confirm("Migrate {$migrationCount} listing(s) to dual pricing?")) {
                $this->warn('Migration cancelled.');

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info($isDryRun ? '📋 DRY RUN - No changes will be saved' : '🚀 Starting migration...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar($migrationCount);
        $progressBar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($needsMigration as $listing) {
            try {
                $pricing = $listing->pricing;
                $newPricing = $pricing;

                // Determine existing currency
                $existingCurrency = $pricing['currency'] ?? null;
                $basePrice = $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? null;

                if (! $basePrice) {
                    $errors[] = "Listing {$listing->id}: No base price found";
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Migrate to dual pricing
                if ($existingCurrency === 'TND') {
                    $newPricing['tnd_price'] = $basePrice;
                    $newPricing['eur_price'] = $pricingService->calculateExpectedPrice($basePrice);
                } elseif ($existingCurrency === 'EUR') {
                    $newPricing['eur_price'] = $basePrice;
                    // Reverse calculation: EUR to TND
                    $ratio = $pricingService->getParityRatio() ?? 0.1286;
                    $newPricing['tnd_price'] = round($basePrice / $ratio, 2);
                } else {
                    // Unknown currency, default to EUR
                    $newPricing['eur_price'] = $basePrice;
                    $ratio = $pricingService->getParityRatio() ?? 0.1286;
                    $newPricing['tnd_price'] = round($basePrice / $ratio, 2);
                }

                // Migrate person types if they exist
                if (isset($pricing['personTypes']) || isset($pricing['person_types'])) {
                    $personTypes = $pricing['personTypes'] ?? $pricing['person_types'];
                    $newPersonTypes = [];

                    foreach ($personTypes as $type) {
                        $typePrice = $type['price'] ?? 0;
                        $newType = $type;

                        if ($existingCurrency === 'TND') {
                            $newType['tnd_price'] = $typePrice;
                            $newType['eur_price'] = $pricingService->calculateExpectedPrice($typePrice);
                        } elseif ($existingCurrency === 'EUR') {
                            $newType['eur_price'] = $typePrice;
                            $ratio = $pricingService->getParityRatio() ?? 0.1286;
                            $newType['tnd_price'] = round($typePrice / $ratio, 2);
                        } else {
                            $newType['eur_price'] = $typePrice;
                            $ratio = $pricingService->getParityRatio() ?? 0.1286;
                            $newType['tnd_price'] = round($typePrice / $ratio, 2);
                        }

                        $newPersonTypes[] = $newType;
                    }

                    $newPricing['personTypes'] = $newPersonTypes;
                }

                // Save changes
                if (! $isDryRun) {
                    $listing->update(['pricing' => $newPricing]);
                }

                $migrated++;
            } catch (\Exception $e) {
                $errors[] = "Listing {$listing->id}: {$e->getMessage()}";
                $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Migration complete!');
        $this->newLine();

        $this->table(
            ['Status', 'Count'],
            [
                ['Successfully Migrated', $migrated],
                ['Skipped/Errors', $skipped],
            ]
        );

        if (! empty($errors)) {
            $this->newLine();
            $this->error('⚠️  Errors encountered:');

            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes');
        }

        return self::SUCCESS;
    }
}
