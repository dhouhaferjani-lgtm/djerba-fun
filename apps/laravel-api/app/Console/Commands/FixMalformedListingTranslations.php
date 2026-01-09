<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;

class FixMalformedListingTranslations extends Command
{
    protected $signature = 'listings:fix-translations {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix double-nested translation arrays in listings (e.g., {"en":{"en":"value"}} -> {"en":"value"})';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('Scanning listings for malformed translations...');

        $translatableFields = ['title', 'summary', 'description'];
        $fixedCount = 0;
        $listings = Listing::withTrashed()->get();

        foreach ($listings as $listing) {
            $changes = [];

            foreach ($translatableFields as $field) {
                $value = $listing->getAttributes()[$field] ?? null;

                if ($value === null) {
                    continue;
                }

                // Decode JSON if needed
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }

                if (! is_array($value)) {
                    continue;
                }

                $fixed = $this->fixNestedTranslations($value);

                if ($fixed !== $value) {
                    $changes[$field] = [
                        'before' => $value,
                        'after' => $fixed,
                    ];
                }
            }

            if (! empty($changes)) {
                $this->line('');
                $this->warn("Listing #{$listing->id}: {$listing->slug}");

                foreach ($changes as $field => $change) {
                    $this->line("  Field: {$field}");
                    $this->line("    Before: " . json_encode($change['before']));
                    $this->line("    After:  " . json_encode($change['after']));

                    if (! $dryRun) {
                        $listing->setTranslations($field, $change['after']);
                    }
                }

                if (! $dryRun) {
                    $listing->saveQuietly();
                    $this->info("  -> Fixed!");
                }

                $fixedCount++;
            }
        }

        $this->line('');

        if ($fixedCount === 0) {
            $this->info('No malformed translations found.');
        } else {
            if ($dryRun) {
                $this->warn("Found {$fixedCount} listing(s) with malformed translations.");
                $this->info('Run without --dry-run to fix them.');
            } else {
                $this->info("Fixed {$fixedCount} listing(s).");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Fix double-nested translation arrays.
     */
    private function fixNestedTranslations(array $value): array
    {
        $locales = ['en', 'fr'];
        $hasLocaleKeys = ! empty(array_intersect(array_keys($value), $locales));

        if (! $hasLocaleKeys) {
            return $value;
        }

        $fixed = [];

        foreach ($locales as $locale) {
            if (! isset($value[$locale])) {
                continue;
            }

            $localeValue = $value[$locale];

            // Unwrap nested arrays
            while (is_array($localeValue)) {
                $extracted = $localeValue[$locale] ?? $localeValue['en'] ?? reset($localeValue);
                if ($extracted === false || $extracted === $localeValue) {
                    // Can't extract further, convert to string if possible
                    $localeValue = '';
                    break;
                }
                $localeValue = $extracted;
            }

            if (is_string($localeValue) && $localeValue !== '') {
                $fixed[$locale] = $localeValue;
            }
        }

        return $fixed;
    }
}
