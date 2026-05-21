<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Listing;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression tests for `locations:resync-listings-count`.
 *
 * One-off command that heals legacy stale counters by setting every
 * `locations.listings_count` to the actual `COUNT(*)` of its child listings.
 *
 * Run once on dev and once on prod after the fix is deployed, then again
 * any time a future migration or operation might desync the counter.
 *
 * Scenarios:
 *   C1. Stale non-zero counter is corrected down to the real count.
 *   C2. Zero counter when listings exist is corrected up.
 *   C3. Correctly-set counter is left alone (idempotent).
 *   C4. --dry-run reports what would change without writing.
 *   C5. Soft-deleted listings are NOT counted (matches the observer's view of
 *       "active listings").
 */
class ResyncLocationListingsCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario C1 — Stale non-zero counter with fewer actual listings is
     * corrected down.
     *
     * This is the exact case the production client hit: an old location's
     * counter says 99 but the listings have all been deleted/reassigned.
     */
    public function test_c1_stale_inflated_counter_is_corrected_down(): void
    {
        $location = Location::factory()->create(['listings_count' => 99]);
        // No listings created — actual count is 0.

        $this->artisan('locations:resync-listings-count')
            ->assertSuccessful();

        $this->assertSame(0, $location->fresh()->listings_count);
    }

    /**
     * Scenario C2 — Counter undercount is corrected up.
     *
     * We create listings normally (observer fires → count = 3) then manually
     * reset the counter via DB to simulate the legacy stale state (drift).
     * `Listing::withoutEvents()` globally suppresses ALL model events,
     * including the Location boot hook that generates UUIDs, which causes
     * NOT-NULL constraint failures — so we never use it.
     */
    public function test_c2_undercounted_counter_is_corrected_up(): void
    {
        $location = Location::factory()->create();
        Listing::factory()->count(3)->create(['location_id' => $location->id]);

        // Simulate the legacy drift: counter says 0, reality is 3.
        $this->forceListingsCount($location, 0);

        $this->artisan('locations:resync-listings-count')
            ->assertSuccessful();

        $this->assertSame(3, $location->fresh()->listings_count);
    }

    /**
     * Scenario C3 — A correctly-set counter is left alone (idempotent).
     */
    public function test_c3_already_correct_counter_is_left_alone(): void
    {
        $location = Location::factory()->create();
        Listing::factory()->count(2)->create(['location_id' => $location->id]);
        // After the observer fires, count is already 2.

        $this->artisan('locations:resync-listings-count')
            ->assertSuccessful();

        $this->assertSame(2, $location->fresh()->listings_count);
    }

    /**
     * Scenario C4 — `--dry-run` reports what would change without writing.
     */
    public function test_c4_dry_run_does_not_write(): void
    {
        $location = Location::factory()->create(['listings_count' => 99]);

        $this->artisan('locations:resync-listings-count', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(99, $location->fresh()->listings_count);
    }

    /**
     * Scenario C5 — Soft-deleted listings are excluded from the recomputed
     * count, matching how the delete guard and admin badge interpret the
     * column (active listings only).
     */
    public function test_c5_soft_deleted_listings_are_excluded(): void
    {
        $location = Location::factory()->create();
        Listing::factory()->count(2)->create(['location_id' => $location->id]);
        $stale = Listing::factory()->create(['location_id' => $location->id]);

        // Soft-delete one; observer decrements → count = 2 (live truth).
        $stale->delete();
        $this->assertSame(2, $location->fresh()->listings_count);

        // Simulate drift before running the command to prove it recomputes,
        // not just trusts the existing value.
        $this->forceListingsCount($location, 99);

        $this->artisan('locations:resync-listings-count')
            ->assertSuccessful();

        $this->assertSame(2, $location->fresh()->listings_count);
    }

    /**
     * Bypass the observer to simulate legacy drift in the denormalized
     * counter without disabling global model events.
     */
    private function forceListingsCount(Location $location, int $value): void
    {
        DB::table('locations')
            ->where('id', $location->id)
            ->update(['listings_count' => $value]);
    }
}
