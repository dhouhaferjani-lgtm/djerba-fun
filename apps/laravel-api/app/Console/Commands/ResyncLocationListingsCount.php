<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off command to heal stale `locations.listings_count` values.
 *
 * The counter is denormalized and historically drifted because no observer
 * kept it in sync. After shipping `ListingObserver`, run this once on dev and
 * once on prod to reset every row to its real count.
 *
 * Idempotent — running it again after data is correct is a no-op.
 */
class ResyncLocationListingsCount extends Command
{
    protected $signature = 'locations:resync-listings-count
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Recompute and update locations.listings_count to match the actual count of active listings';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE — no rows will be updated.');
        }

        $changed = 0;
        $unchanged = 0;

        Location::query()->chunkById(200, function ($locations) use (&$changed, &$unchanged, $dryRun) {
            foreach ($locations as $location) {
                // Soft-deleted Listings are excluded by default (no withTrashed).
                $actual = $location->listings()->count();

                if ($actual === (int) $location->listings_count) {
                    $unchanged++;

                    continue;
                }

                $this->line(sprintf(
                    '  location#%d (%s): %d → %d',
                    $location->id,
                    $location->slug,
                    $location->listings_count,
                    $actual,
                ));

                if (! $dryRun) {
                    // Update directly via the query builder so we don't bump
                    // `updated_at` for cosmetic counter corrections.
                    DB::table('locations')
                        ->where('id', $location->id)
                        ->update(['listings_count' => $actual]);
                }

                $changed++;
            }
        });

        $this->newLine();
        $this->info(sprintf(
            '%s %d row(s); %d already correct.',
            $dryRun ? 'Would update' : 'Updated',
            $changed,
            $unchanged,
        ));

        return self::SUCCESS;
    }
}
