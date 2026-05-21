<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ActivityType;
use App\Models\Listing;
use App\Models\Location;

/**
 * Keep denormalized listing-count columns in sync with the listings table.
 *
 * Two columns are kept current:
 *   - `locations.listings_count`       (FK: listings.location_id, cascadeOnDelete)
 *   - `activity_types.listings_count`  (FK: listings.activity_type_id, nullOnDelete)
 *
 * Both columns are read by the admin Filament badge / filter. Without this
 * observer they drift over time (the increment/decrement helpers on the
 * models were never wired to a hook). All increments/decrements run as raw
 * DB queries on `whereKey(...)` to dodge relationship cache surprises and
 * avoid mass-assignment issues.
 *
 * Listing uses SoftDeletes, so:
 *   - `deleted`      fires on soft-delete (decrement, unless purging an
 *                    already-soft-deleted record)
 *   - `forceDeleted` fires after `deleted` on hard delete (no-op — done)
 *   - `restored`     fires when un-soft-deleting (re-increment)
 *
 * The `updated` hook does the +/− pair when `location_id` OR `activity_type_id`
 * actually changed; each branch is independent, so routine updates to other
 * Listing fields are zero-cost.
 */
class ListingObserver
{
    public function created(Listing $listing): void
    {
        if ($listing->location_id !== null) {
            Location::whereKey($listing->location_id)->increment('listings_count');
        }

        if ($listing->activity_type_id !== null) {
            ActivityType::whereKey($listing->activity_type_id)->increment('listings_count');
        }
    }

    public function deleted(Listing $listing): void
    {
        // `deleted` fires on every delete path:
        //   1. soft-delete from active:   isForceDeleting=false, decrement.
        //   2. forceDelete from active:   isForceDeleting=true, decrement.
        //   3. forceDelete from already-soft-deleted: skip — we already
        //      decremented at step 1 for that model.
        //
        // Distinguishing 2 from 3: in case 2, the model's `deleted_at` was
        // null when loaded so original.deleted_at is null. In case 3, the
        // model was loaded with `deleted_at` populated, so original is
        // non-null and forceDelete bypasses save() — original stays put.
        // (For case 1, save() during runSoftDelete() syncs original, but
        // isForceDeleting=false short-circuits this check before that
        // matters.)
        $isPurgingAlreadySoftDeleted = $listing->isForceDeleting()
            && $listing->getOriginal('deleted_at') !== null;

        if ($isPurgingAlreadySoftDeleted) {
            return;
        }

        if ($listing->location_id !== null) {
            Location::whereKey($listing->location_id)->decrement('listings_count');
        }

        if ($listing->activity_type_id !== null) {
            ActivityType::whereKey($listing->activity_type_id)->decrement('listings_count');
        }
    }

    public function forceDeleted(Listing $listing): void
    {
        // No-op: `deleted` handles the decrement. Laravel fires `deleted`
        // before `forceDeleted` during a hard delete; doing it again here
        // would double-decrement on the active → forceDeleted path.
    }

    public function restored(Listing $listing): void
    {
        if ($listing->location_id !== null) {
            Location::whereKey($listing->location_id)->increment('listings_count');
        }

        if ($listing->activity_type_id !== null) {
            ActivityType::whereKey($listing->activity_type_id)->increment('listings_count');
        }
    }

    public function updated(Listing $listing): void
    {
        if (! $listing->wasChanged('location_id') && ! $listing->wasChanged('activity_type_id')) {
            return;
        }

        if ($listing->wasChanged('location_id')) {
            $previousLocationId = $listing->getOriginal('location_id');
            $newLocationId = $listing->location_id;

            if ($previousLocationId !== null) {
                Location::whereKey($previousLocationId)->decrement('listings_count');
            }

            if ($newLocationId !== null) {
                Location::whereKey($newLocationId)->increment('listings_count');
            }
        }

        if ($listing->wasChanged('activity_type_id')) {
            $previousActivityTypeId = $listing->getOriginal('activity_type_id');
            $newActivityTypeId = $listing->activity_type_id;

            if ($previousActivityTypeId !== null) {
                ActivityType::whereKey($previousActivityTypeId)->decrement('listings_count');
            }

            if ($newActivityTypeId !== null) {
                ActivityType::whereKey($newActivityTypeId)->increment('listings_count');
            }
        }
    }
}
