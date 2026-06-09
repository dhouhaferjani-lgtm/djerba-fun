<?php

declare(strict_types=1);

namespace Tests\Feature\Observers;

use App\Models\ActivityType;
use App\Models\Listing;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for `ListingObserver` — keeps `locations.listings_count`
 * in sync with the real listings table.
 *
 * Why this observer exists: the Filament admin renders a badge/filter on
 * `listings_count`, and the previous delete guard tripped on it. The column
 * is denormalized; without an observer it drifts (it used to drift in
 * production — see the 2026-05-13 ticket). The observer wires every Listing
 * lifecycle event that can change which Location a Listing belongs to.
 *
 * Scenarios:
 *   B1. Listing created → owner's count +1.
 *   B2. Listing soft-deleted → owner's count -1 (not double-counted).
 *   B3. Listing restored → owner's count +1.
 *   B4. Listing forceDeleted from already soft-deleted → no double decrement.
 *   B5. Listing's location_id changes → old owner -1, new owner +1.
 *   B6. Listing forceDeleted while still active → owner's count -1 once.
 */
class ListingObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario B1 — Listing creation increments the owner's counter.
     */
    public function test_b1_creating_listing_increments_owner_counter(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);

        Listing::factory()->create(['location_id' => $location->id]);

        $this->assertSame(1, $location->fresh()->listings_count);
    }

    /**
     * Scenario B2 — Soft-deleting a Listing decrements the owner's counter.
     */
    public function test_b2_soft_deleting_listing_decrements_owner_counter(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);

        $this->assertSame(1, $location->fresh()->listings_count);

        $listing->delete();

        $this->assertSame(0, $location->fresh()->listings_count);
    }

    /**
     * Scenario B3 — Restoring a soft-deleted Listing increments the owner's
     * counter again.
     */
    public function test_b3_restoring_listing_increments_owner_counter(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        $this->assertSame(0, $location->fresh()->listings_count);

        $listing->restore();

        $this->assertSame(1, $location->fresh()->listings_count);
    }

    /**
     * Scenario B4 — forceDelete after soft-delete must not double-decrement.
     *
     * Without `isForceDeleting()` gating in the observer, a soft-delete
     * decrements once, then forceDelete decrements again → count goes negative.
     */
    public function test_b4_force_deleting_already_soft_deleted_does_not_double_decrement(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();
        $this->assertSame(0, $location->fresh()->listings_count);

        $listing->forceDelete();

        $this->assertSame(0, $location->fresh()->listings_count);
    }

    /**
     * Scenario B5 — Reassigning a Listing to a new Location moves the count.
     */
    public function test_b5_reassigning_listing_moves_count_between_locations(): void
    {
        $a = Location::factory()->create(['listings_count' => 0]);
        $b = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $a->id]);

        $this->assertSame(1, $a->fresh()->listings_count);
        $this->assertSame(0, $b->fresh()->listings_count);

        $listing->update(['location_id' => $b->id]);

        $this->assertSame(0, $a->fresh()->listings_count);
        $this->assertSame(1, $b->fresh()->listings_count);
    }

    /**
     * Scenario B6 — forceDelete on an active (not yet soft-deleted) Listing
     * decrements the counter exactly once.
     */
    public function test_b6_force_deleting_active_listing_decrements_once(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $this->assertSame(1, $location->fresh()->listings_count);

        $listing->forceDelete();

        $this->assertSame(0, $location->fresh()->listings_count);
    }

    // ------------------------------------------------------------------
    // E-scenarios — same observer behaviors but for ActivityType counter.
    // The Listing factory does NOT set activity_type_id by default, so we
    // attach it explicitly. The location_id branch must stay green in
    // parallel (extension is strictly additive — see B1–B6 above).
    // ------------------------------------------------------------------

    /** Scenario E1 — Listing creation increments the ActivityType counter. */
    public function test_e1_creating_listing_increments_activity_type_counter(): void
    {
        $type = ActivityType::factory()->create(['listings_count' => 0]);

        Listing::factory()->create(['activity_type_id' => $type->id]);

        $this->assertSame(1, $type->fresh()->listings_count);
    }

    /** Scenario E2 — Soft-delete decrements the ActivityType counter. */
    public function test_e2_soft_deleting_listing_decrements_activity_type_counter(): void
    {
        $type = ActivityType::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['activity_type_id' => $type->id]);
        $this->assertSame(1, $type->fresh()->listings_count);

        $listing->delete();

        $this->assertSame(0, $type->fresh()->listings_count);
    }

    /** Scenario E3 — Restore re-increments the ActivityType counter. */
    public function test_e3_restoring_listing_increments_activity_type_counter(): void
    {
        $type = ActivityType::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['activity_type_id' => $type->id]);
        $listing->delete();
        $this->assertSame(0, $type->fresh()->listings_count);

        $listing->restore();

        $this->assertSame(1, $type->fresh()->listings_count);
    }

    /** Scenario E4 — forceDelete after soft-delete must not double-decrement. */
    public function test_e4_force_deleting_already_soft_deleted_does_not_double_decrement_activity_type(): void
    {
        $type = ActivityType::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['activity_type_id' => $type->id]);
        $listing->delete();
        $this->assertSame(0, $type->fresh()->listings_count);

        $listing->forceDelete();

        $this->assertSame(0, $type->fresh()->listings_count);
    }

    /** Scenario E5 — activity_type_id reassignment moves the count between types. */
    public function test_e5_reassigning_listing_moves_count_between_activity_types(): void
    {
        $a = ActivityType::factory()->create(['listings_count' => 0]);
        $b = ActivityType::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['activity_type_id' => $a->id]);

        $this->assertSame(1, $a->fresh()->listings_count);
        $this->assertSame(0, $b->fresh()->listings_count);

        $listing->update(['activity_type_id' => $b->id]);

        $this->assertSame(0, $a->fresh()->listings_count);
        $this->assertSame(1, $b->fresh()->listings_count);
    }

    /** Scenario E6 — forceDelete of an active Listing decrements the ActivityType counter once. */
    public function test_e6_force_deleting_active_listing_decrements_activity_type_once(): void
    {
        $type = ActivityType::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['activity_type_id' => $type->id]);
        $this->assertSame(1, $type->fresh()->listings_count);

        $listing->forceDelete();

        $this->assertSame(0, $type->fresh()->listings_count);
    }
}
