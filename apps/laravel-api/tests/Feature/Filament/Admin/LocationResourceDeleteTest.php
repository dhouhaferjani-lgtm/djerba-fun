<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\LocationResource;
use App\Filament\Admin\Resources\LocationResource\Pages\EditLocation;
use App\Filament\Admin\Resources\LocationResource\Pages\ListLocations;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Filament\Actions\DeleteAction as HeaderDeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for deletion in the Admin Locations panel.
 *
 * Original client ticket (2026-05-13): admins see a raw `500 Server Error` on
 * /admin/locations when clicking Delete on an OLD location. Newly-created
 * locations delete fine.
 *
 * Root cause: `LocationResource` and `EditLocation` had a delete guard that
 * called `throw new \Exception(...)` when `$record->listings_count > 0`. In
 * Filament 3 a vanilla exception bubbles to Laravel's exception handler and
 * renders the 500 page. The correct halt is `Notification::send()` + halt().
 *
 * Compounding: the guard read the denormalized `listings_count` column, but
 * that column was never wired to any listener — so it drifts. Old records
 * carry stale non-zero counts; new records stay at 0 (default). Therefore:
 *
 *   OLD record  → stale count > 0  → `throw \Exception` → 500
 *   NEW record  → count = 0        → guard passes      → delete OK
 *
 * Scenarios pinned here:
 *   A1. Old record with stale counter but no real listings → delete succeeds.
 *   A2. Real listings exist → delete is blocked with a `danger` notification,
 *       NOT a 500.
 *   A3. Fresh empty record → delete succeeds (regression net for the existing
 *       "happy path").
 *   A4. Bulk delete with mixed eligibility → deletes only the eligible rows
 *       and notifies for the rest.
 *   A5. Same guard fires from the EditLocation page header (the bug is in 3
 *       call sites; tests must cover every surface a client can reach).
 */
class LocationResourceDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * Helper — seed a cart_items row pointing at the given Listing.
     *
     * The shipped CartItemFactory writes columns the cart_items schema does not
     * have (`availability_slot_id`, `total_price`) and leaves `hold_id` null
     * even though the FK is NOT NULL — both pre-existing issues outside this
     * ticket's scope. We bypass it with a raw insert using only the columns
     * the migration (`2025_12_17_124539_create_carts_table.php`) actually has.
     *
     * Pass an explicit $cart to vary status/expiry (used by the dead-cart
     * refinement tests A13–A16). The default cart is ACTIVE+notExpired (LIVE)
     * — matches the pre-existing A6/A7/A10 contracts.
     *
     * Returns the cart_item UUID so callers can assertDatabaseHas('cart_items', ['id' => $id]).
     */
    protected function seedCartItemFor(Listing $listing, ?Cart $cart = null): string
    {
        $slot = AvailabilitySlot::factory()->create(['listing_id' => $listing->id]);
        $hold = BookingHold::factory()->create([
            'listing_id' => $listing->id,
            'slot_id' => $slot->id,
        ]);
        $cart ??= Cart::factory()->create();
        $id = (string) Str::uuid();

        DB::table('cart_items')->insert([
            'id' => $id,
            'cart_id' => $cart->id,
            'hold_id' => $hold->id,
            'listing_id' => $listing->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'currency' => 'EUR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Scenario A1 — Delete an OLD location with a stale counter but no real
     * listings.
     *
     * Given a Location whose `listings_count` column is 5
     *   AND zero rows exist in `listings` with that location_id,
     * When the admin invokes the row Delete action,
     * Then the location row is hard-deleted,
     *   AND no exception bubbles out (no 500 page),
     *   AND a success notification is sent.
     *
     * Reproduces the production bug: with the buggy code, the guard reads the
     * stale counter, calls `throw new \Exception`, and the action throws —
     * which `assertHasNoTableActionErrors` catches.
     */
    public function test_a1_delete_old_location_with_stale_counter_but_no_listings_succeeds(): void
    {
        $location = Location::factory()->create([
            'listings_count' => 5,
        ]);
        $this->assertSame(0, $location->listings()->count());

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    /**
     * Scenario A2 — Delete attempt while real listings still reference the
     * location.
     *
     * Given a Location with 2 active (non-soft-deleted) Listings,
     * When the admin invokes the row Delete action,
     * Then the location is NOT deleted,
     *   AND a `danger` notification is rendered,
     *   AND no exception bubbles out (no 500).
     *
     * This is the "graceful halt" path. We use the LIVE listings query, not
     * the denormalized counter, so the guard never produces a false positive
     * or false negative.
     */
    public function test_a2_delete_blocked_with_danger_notification_when_real_listings_exist(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        Listing::factory()->count(2)->create(['location_id' => $location->id]);

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    /**
     * Scenario A3 — Fresh empty location deletes cleanly.
     *
     * Baseline regression net: the "new location" path (count=0, no listings)
     * has always worked. It must keep working.
     */
    public function test_a3_fresh_empty_location_deletes_cleanly(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    /**
     * Scenario A4 — Bulk delete with mixed eligibility.
     *
     * Given three Locations selected: empty, has real listings, stale counter
     *   but no real listings,
     * When the admin invokes Delete Bulk Action,
     * Then the empty and stale-counter rows are deleted,
     *   AND the row with real listings is preserved,
     *   AND a `danger` notification names the preserved row,
     *   AND no exception bubbles out.
     */
    public function test_a4_bulk_delete_skips_only_locations_with_real_listings(): void
    {
        $empty = Location::factory()->create(['listings_count' => 0]);
        $blocked = Location::factory()->create(['listings_count' => 0]);
        $staleCounter = Location::factory()->create(['listings_count' => 7]);

        Listing::factory()->count(2)->create(['location_id' => $blocked->id]);

        Livewire::test(ListLocations::class)
            ->callTableBulkAction(DeleteBulkAction::class, [
                $empty->getKey(),
                $blocked->getKey(),
                $staleCounter->getKey(),
            ])
            ->assertHasNoTableBulkActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseMissing('locations', ['id' => $empty->id]);
        $this->assertDatabaseMissing('locations', ['id' => $staleCounter->id]);
        $this->assertDatabaseHas('locations', ['id' => $blocked->id]);
    }

    /**
     * Scenario A5 — Delete from the EditLocation page header action.
     *
     * The same buggy `throw new \Exception` pattern lives in three places
     * (LocationResource row action, bulk action, EditLocation header). A fix
     * that only patches the table action would leave this surface broken —
     * a client clicking Delete from the edit page would still 500.
     */
    public function test_a5_edit_page_header_delete_uses_graceful_halt_not_500(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        Listing::factory()->count(2)->create(['location_id' => $location->id]);

        Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
            ->callAction(HeaderDeleteAction::class)
            ->assertHasNoActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    /**
     * Scenario A6 — Soft-deleted listings referenced by cart_items must block
     * Location deletion (the actual 23503 root cause).
     *
     * Production repro (2026-05-14, dev location id=4 Sidi Bou Said):
     * cascadeOnDelete on listings.location_id triggers a hard-delete of
     * the soft-deleted Listing row, which cart_items.listing_id (FK RESTRICT)
     * blocks with 23503 → 500.
     *
     * Original fix (commit 93c3c6c) blocked on ANY listing under the location
     * including soft-deleted ones — over-conservative; admins could not delete
     * a Location they had genuinely emptied. Updated policy (2026-05-18 ticket):
     * smart-allow. Block only when the data would actually trip the cascade —
     * i.e. when cart_items still reference the trashed listings.
     *
     * Given a Location with one soft-deleted Listing AND a cart_item referencing it,
     * When the admin clicks Delete,
     * Then the location is NOT deleted (the cart_items branch of the guard fires),
     *   AND a danger notification is rendered with the cart_items message,
     *   AND no exception bubbles (no raw 23503/500),
     *   AND the cart_item is preserved.
     */
    public function test_a6_soft_deleted_listings_with_cart_items_block_delete_to_prevent_fk_violation(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete(); // soft-delete
        $cartItemId = $this->seedCartItemFor($listing);

        $this->assertSame(0, $location->listings()->count(), 'precondition: 0 active listings');
        $this->assertSame(1, $location->listings()->withTrashed()->count(), 'precondition: 1 trashed listing');

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario A7 — Bulk smart-allow with mixed eligibility (refactored from
     * "soft-only preserved" under the over-conservative pre-2026-05-18 policy).
     *
     * Under the new policy: a Location with soft-deleted listings + cart_items
     * is blocked (cart_items branch). A Location with soft-deleted listings but
     * NO cart_items is allowed (cascade purges the trashed listing).
     */
    public function test_a7_bulk_smart_allow_preserves_only_locations_with_cart_items_referencing_trashed_listings(): void
    {
        $empty = Location::factory()->create(['listings_count' => 0]);
        $active = Location::factory()->create(['listings_count' => 0]);
        $softWithCart = Location::factory()->create(['listings_count' => 0]);
        $softNoCart = Location::factory()->create(['listings_count' => 0]);
        $staleCounter = Location::factory()->create(['listings_count' => 7]);

        Listing::factory()->count(2)->create(['location_id' => $active->id]);

        $softWithCartListing = Listing::factory()->create(['location_id' => $softWithCart->id]);
        $softWithCartListing->delete();
        $this->seedCartItemFor($softWithCartListing);

        $softNoCartListing = Listing::factory()->create(['location_id' => $softNoCart->id]);
        $softNoCartListing->delete();

        Livewire::test(ListLocations::class)
            ->callTableBulkAction(DeleteBulkAction::class, [
                $empty->getKey(),
                $active->getKey(),
                $softWithCart->getKey(),
                $softNoCart->getKey(),
                $staleCounter->getKey(),
            ])
            ->assertHasNoTableBulkActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        // Allowed:
        $this->assertDatabaseMissing('locations', ['id' => $empty->id]);
        $this->assertDatabaseMissing('locations', ['id' => $staleCounter->id]);
        $this->assertDatabaseMissing('locations', ['id' => $softNoCart->id]);
        $this->assertDatabaseMissing('listings', ['id' => $softNoCartListing->id]);
        // Blocked:
        $this->assertDatabaseHas('locations', ['id' => $active->id]);
        $this->assertDatabaseHas('locations', ['id' => $softWithCart->id]);
    }

    /**
     * Scenario A8 — Smart-allow row delete when only soft-deleted listings
     * remain AND no cart_items reference them.
     *
     * Client ticket (2026-05-18): admin "deleted" the old listings (soft-delete)
     * and then could not delete the parent Location because the prior over-conservative
     * guard blocked on any listing (active or trashed). New policy:
     *
     *   block when:  active listings exist (A2 contract)
     *              OR cart_items still reference the trashed listings (A6/A9 contract)
     *   allow when:  only trashed listings remain AND no cart_items
     *
     * On allow, the DB-level cascadeOnDelete on listings.location_id will
     * hard-delete the trashed listings + cascade through bookings/reviews/etc.
     * (accepted product trade-off — bookings + review history for those
     * archived listings is wiped).
     *
     * Given a Location with one soft-deleted Listing and zero cart_items,
     * When the admin invokes the row Delete action,
     * Then the Location is hard-deleted,
     *   AND the soft-deleted Listing is hard-deleted by cascade,
     *   AND no danger notification is sent,
     *   AND no exception bubbles out.
     */
    public function test_a8_smart_allow_deletes_location_when_only_soft_deleted_listings_and_no_cart_items(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete(); // soft-delete

        $this->assertSame(0, $location->listings()->count(), 'precondition: 0 active listings');
        $this->assertSame(1, $location->listings()->withTrashed()->count(), 'precondition: 1 trashed listing');

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
    }

    /**
     * Scenario A9 — Smart-allow on the EditLocation page header (third surface).
     *
     * Mirror of A8 on the third Filament delete surface. The header path lives
     * in a separate file (`EditLocation.php`) — a fix that only touches the
     * table action would leave the header broken. This pins the header.
     */
    public function test_a9_smart_allow_deletes_location_from_edit_page_header_when_only_soft_deleted_listings_and_no_cart_items(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
            ->callAction(HeaderDeleteAction::class)
            ->assertHasNoActionErrors();

        Notification::assertNotNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
    }

    /**
     * Scenario A10 — EditLocation header blocks when cart_items reference the
     * soft-deleted listings (third surface for the cart_items branch).
     *
     * Mirror of A6 on the EditLocation header.
     */
    public function test_a10_edit_page_header_blocks_when_soft_deleted_listings_have_cart_items(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();
        $cartItemId = $this->seedCartItemFor($listing);

        Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
            ->callAction(HeaderDeleteAction::class)
            ->assertHasNoActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario A11 — Row DeleteAction modal description renders the cascade
     * warning when trashed listings exist under the location.
     *
     * The confirmation modal must surface what's about to be destroyed before
     * the admin clicks Confirm — the smart-allow path will hard-delete the
     * soft-deleted listings AND cascade through their bookings + reviews.
     */
    public function test_a11_row_delete_modal_description_shows_cascade_warning_when_trashed_listings_exist(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        // After mountTableAction, the confirmation modal is rendered and its
        // description (closure on the DeleteAction) is part of the response.
        // The cascade warning includes "1 archived listing" verbatim.
        Livewire::test(ListLocations::class)
            ->mountTableAction(DeleteAction::class, $location)
            ->assertSee(__('filament.notifications.delete_location_cascade_warning', ['count' => 1]));
    }

    /**
     * Scenario A12 — Row DeleteAction modal description omits the cascade
     * warning when there are zero trashed listings (default Filament confirm only).
     *
     * Pins the negative case: we don't show a misleading "0 archived listings…"
     * message when the location is genuinely empty.
     */
    public function test_a12_row_delete_modal_description_omits_cascade_warning_when_no_trashed_listings(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);

        // The cascade warning copy must NOT appear when there are no trashed
        // listings — the modal description closure returns null in this case.
        Livewire::test(ListLocations::class)
            ->mountTableAction(DeleteAction::class, $location)
            ->assertDontSee('archived listing');
    }

    /**
     * Scenario A13 — Smart-allow extends to dead carts: cart_items in an
     * EXPIRED cart must NOT block the location delete.
     *
     * Client ticket follow-up (2026-05-19, dev): after commit 6f1300f shipped,
     * an admin tried to delete a location and hit the cart_items block —
     * correctly diagnosed, but no UI affordance to clear the stale cart_item.
     * Looking at Cart semantics: only `status=ACTIVE AND expires_at > now()`
     * (or `status=CHECKING_OUT`) is truly "live". Anything else is dead data
     * that shouldn't block a legitimate cleanup.
     *
     * Refined contract: live cart_items block (A6/A15); dead cart_items get
     * pre-deleted as part of the action, then the cascade fires.
     *
     * Given a Location with one soft-deleted Listing AND a cart_item whose
     *   parent cart is `status=ACTIVE` but `expires_at` is in the past,
     * When the admin invokes the row Delete action,
     * Then the location is hard-deleted (cascade succeeds),
     *   AND the trashed Listing is hard-deleted (cascade),
     *   AND the dead cart_item is pre-cleaned (no FK violation),
     *   AND no danger notification is sent.
     */
    public function test_a13_smart_allow_when_only_expired_cart_items_reference_trashed_listings(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        $expiredCart = Cart::factory()->expired()->create();
        $cartItemId = $this->seedCartItemFor($listing, $expiredCart);

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario A14 — Same as A13 but the parent cart is ABANDONED instead of
     * expired-but-active. ABANDONED carts are dead by definition and must not
     * block the location delete.
     */
    public function test_a14_smart_allow_when_only_abandoned_cart_items_reference_trashed_listings(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        $abandonedCart = Cart::factory()->abandoned()->create();
        $cartItemId = $this->seedCartItemFor($listing, $abandonedCart);

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario A15 — Live cart_items still block (regression net for A6 under
     * the refined contract). A live cart is `status=ACTIVE AND expires_at>now()`
     * — the default factory shape — and must continue to block the delete.
     */
    public function test_a15_live_cart_items_still_block_delete(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        // Default Cart::factory() creates status=ACTIVE + expires_at 15min in
        // the future → LIVE under Cart::scopeLive.
        $cartItemId = $this->seedCartItemFor($listing);

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario A16 — CHECKING_OUT carts are mid-payment; we treat them as LIVE
     * even if their `expires_at` would otherwise mark them dead. Destroying a
     * cart_item mid-payment is a real risk we explicitly avoid.
     */
    public function test_a16_checking_out_cart_items_still_block_delete(): void
    {
        $location = Location::factory()->create(['listings_count' => 0]);
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        $checkingOutCart = Cart::factory()->checkingOut()->create();
        $cartItemId = $this->seedCartItemFor($listing, $checkingOutCart);

        Livewire::test(ListLocations::class)
            ->callTableAction(DeleteAction::class, $location)
            ->assertHasNoTableActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_delete_location_title'));

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }
}
