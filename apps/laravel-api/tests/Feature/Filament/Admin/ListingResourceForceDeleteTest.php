<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\ListingResource\Pages\ListListings;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for ForceDelete in the Admin Listings panel.
 *
 * The admin Listings table exposes `ForceDeleteAction` + `ForceDeleteBulkAction`
 * (only visible on trashed records via the TrashedFilter). Before 2026-05-18
 * both were UNGUARDED — force-deleting a soft-deleted listing that still had
 * cart_items referencing it would hard-delete the listing row → `cart_items
 * .listing_id` FK RESTRICT → SQLSTATE 23503 → 500. Same cascade hole that
 * the LocationResource guard exists to prevent, but reachable directly via
 * the Listing trash UI without going through Location.
 *
 * Smart-allow policy mirror: block force-delete when cart_items reference
 * the listing; otherwise allow.
 *
 * Scenarios:
 *   BFD1. Force-delete a soft-deleted listing with NO cart_items → succeeds.
 *   BFD2. Force-delete a soft-deleted listing WITH cart_items → BLOCKED with
 *         danger notification (no FK 23503 surfaced as 500).
 *   BFD3. Bulk force-delete mixed (one safe, one cart-blocked) → only the safe
 *         one is purged; the cart-blocked one is preserved and named.
 *   BFD4. ForceDeleteAction is not visible on an active (non-trashed) listing
 *         (Filament default; pinned here so future refactors can't quietly
 *         enable it without re-considering the guard).
 */
class ListingResourceForceDeleteTest extends TestCase
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
     * Identical helper as LocationResourceDeleteTest: bypasses the shipped
     * CartItemFactory (which writes columns the cart_items schema lacks)
     * and inserts only the columns the migration actually has.
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
     * Helper — bring up the listings list with the "with trashed" filter so
     * ForceDeleteAction becomes visible on soft-deleted rows.
     */
    protected function listListingsWithTrashed(): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(ListListings::class, ['activeTab' => 'all'])
            ->set('tableFilters.trashed.value', 'with');
    }

    /**
     * Scenario BFD2 — Force-delete a soft-deleted listing that still has
     * cart_items must be blocked gracefully (not 500).
     *
     * Given a soft-deleted Listing AND a cart_item referencing it,
     * When the admin invokes ForceDeleteAction on that row,
     * Then the listing is NOT hard-deleted (it stays soft-deleted),
     *   AND a danger notification is rendered,
     *   AND the cart_item is preserved,
     *   AND no exception bubbles (no 23503 → 500).
     */
    public function test_bfd2_force_delete_blocked_when_cart_items_reference_the_listing(): void
    {
        $location = Location::factory()->create();
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();
        $cartItemId = $this->seedCartItemFor($listing);

        $this->listListingsWithTrashed()
            ->callTableAction(ForceDeleteAction::class, $listing)
            ->assertHasNoTableActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_force_delete_listing_title'));

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario BFD1 — Force-delete a soft-deleted listing with NO cart_items
     * succeeds (smart-allow happy path).
     */
    public function test_bfd1_force_delete_succeeds_when_no_cart_items_reference_the_listing(): void
    {
        $location = Location::factory()->create();
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        $this->listListingsWithTrashed()
            ->callTableAction(ForceDeleteAction::class, $listing)
            ->assertHasNoTableActionErrors();

        Notification::assertNotNotified(__('filament.notifications.cannot_force_delete_listing_title'));

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
    }

    /**
     * Scenario BFD3 — Bulk force-delete with mixed eligibility (one safe, one
     * cart-blocked). Only the safe one is purged; the cart-blocked one is
     * preserved and named in the danger notification.
     */
    public function test_bfd3_bulk_force_delete_purges_safe_listings_and_names_blocked_ones(): void
    {
        $location = Location::factory()->create();
        $safe = Listing::factory()->create(['location_id' => $location->id]);
        $blocked = Listing::factory()->create(['location_id' => $location->id]);
        $safe->delete();
        $blocked->delete();
        $blockedCartItemId = $this->seedCartItemFor($blocked);

        $this->listListingsWithTrashed()
            ->callTableBulkAction(ForceDeleteBulkAction::class, [
                $safe->getKey(),
                $blocked->getKey(),
            ])
            ->assertHasNoTableBulkActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_force_delete_listing_title'));

        $this->assertDatabaseMissing('listings', ['id' => $safe->id]);
        $this->assertSoftDeleted('listings', ['id' => $blocked->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $blockedCartItemId]);
    }

    /**
     * Scenario BFD4 — ForceDeleteAction is hidden on active (non-trashed)
     * listings (Filament default). Pinned so a future refactor cannot quietly
     * expose force-delete on active rows without re-considering the guard.
     */
    public function test_bfd4_force_delete_action_is_hidden_on_active_listings(): void
    {
        $location = Location::factory()->create();
        $listing = Listing::factory()->create(['location_id' => $location->id]);

        Livewire::test(ListListings::class, ['activeTab' => 'all'])
            ->assertTableActionHidden(ForceDeleteAction::class, $listing);
    }

    /**
     * Scenario BFD5 — Force-delete a soft-deleted listing whose only cart_item
     * is in an EXPIRED cart succeeds (the dead cart_item is pre-cleaned).
     *
     * Mirror of A13 on the ListingResource force-delete surface — closes the
     * same dead-data gap reachable from the Listings trash UI.
     */
    public function test_bfd5_force_delete_succeeds_when_only_dead_cart_items_reference_the_listing(): void
    {
        $location = Location::factory()->create();
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        $expiredCart = Cart::factory()->expired()->create();
        $cartItemId = $this->seedCartItemFor($listing, $expiredCart);

        $this->listListingsWithTrashed()
            ->callTableAction(ForceDeleteAction::class, $listing)
            ->assertHasNoTableActionErrors();

        Notification::assertNotNotified(__('filament.notifications.cannot_force_delete_listing_title'));

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    /**
     * Scenario BFD6 — Force-delete still blocks when a LIVE cart_item references
     * the listing (regression net for BFD2 under the refined contract).
     */
    public function test_bfd6_force_delete_still_blocks_when_a_live_cart_item_references_the_listing(): void
    {
        $location = Location::factory()->create();
        $listing = Listing::factory()->create(['location_id' => $location->id]);
        $listing->delete();

        // Default Cart::factory() = ACTIVE + 15min future → LIVE.
        $cartItemId = $this->seedCartItemFor($listing);

        $this->listListingsWithTrashed()
            ->callTableAction(ForceDeleteAction::class, $listing)
            ->assertHasNoTableActionErrors();

        Notification::assertNotified(__('filament.notifications.cannot_force_delete_listing_title'));

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }
}
