<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HoldStatus;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\ListingExtra;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected PriceCalculationService $priceService
    ) {}

    /**
     * Get or create an active cart for the user or session.
     */
    public function getOrCreateCart(?User $user, ?string $sessionId): Cart
    {
        $cart = $this->getActiveCart($user, $sessionId);

        if (! $cart) {
            $cart = Cart::create([
                'user_id' => $user?->id,
                'session_id' => $sessionId,
                'status' => Cart::STATUS_ACTIVE,
                'expires_at' => now()->addMinutes(Cart::DEFAULT_EXPIRATION_MINUTES),
            ]);
        }

        return $cart;
    }

    /**
     * Get an active cart for the user or session.
     */
    public function getActiveCart(?User $user, ?string $sessionId): ?Cart
    {
        return Cart::active()
            ->forOwner($user?->id, $sessionId)
            ->with(['items.hold', 'items.listing'])
            ->first();
    }

    /**
     * Add an item to the cart from a booking hold.
     *
     * @param  Cart  $cart  The cart to add to
     * @param  BookingHold  $hold  The booking hold to add
     * @param  Listing  $listing  The listing being booked
     * @param  string|null  $currency  The currency to use (detected from middleware)
     * @return CartItem The created cart item
     */
    public function addItem(Cart $cart, BookingHold $hold, Listing $listing, ?string $currency = null): CartItem
    {
        return DB::transaction(function () use ($cart, $hold, $listing, $currency) {
            // Link the hold to this cart
            $hold->update(['cart_id' => $cart->id]);

            // Determine currency (use provided, or get from request, or default to EUR)
            if (! $currency) {
                $currency = request()->attributes->get('user_currency', 'EUR');
            }

            // Calculate unit price from listing based on detected currency
            $pricing = $listing->pricing;
            $basePrice = $this->getPriceForCurrency($pricing, $currency);

            // Enrich extras from hold with price and name info
            $enrichedExtras = null;
            if (! empty($hold->extras)) {
                $enrichedExtras = [];
                foreach ($hold->extras as $extra) {
                    $listingExtra = ListingExtra::with('extra')->find($extra['id']);
                    if ($listingExtra && $listingExtra->extra) {
                        $enrichedExtras[] = [
                            'id' => $extra['id'],
                            'name' => $listingExtra->extra->getTranslation('name', 'en'),
                            'price' => $listingExtra->getEffectivePrice($currency),
                            'quantity' => $extra['quantity'] ?? 1,
                        ];
                    }
                }
            }

            // Create cart item with cached data
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'hold_id' => $hold->id,
                'listing_id' => $listing->id,
                'listing_title' => $this->extractListingTitle($listing),
                'slot_start' => $hold->slot->start_time,
                'slot_end' => $hold->slot->end_time,
                'quantity' => $hold->quantity,
                'person_type_breakdown' => $hold->person_type_breakdown,
                'unit_price' => $basePrice,
                'currency' => $currency,
                'extras' => $enrichedExtras,
            ]);

            // Extend cart expiration
            $cart->extendExpiration();

            return $item;
        });
    }

    /**
     * Remove an item from the cart.
     *
     * @param  CartItem  $item  The item to remove
     * @param  bool  $releaseHold  Whether to release the hold capacity
     */
    public function removeItem(CartItem $item, bool $releaseHold = true): void
    {
        DB::transaction(function () use ($item, $releaseHold) {
            $hold = $item->hold;

            // Remove the item
            $item->delete();

            // Release the hold if requested and it's still active
            if ($releaseHold && $hold && $hold->status === HoldStatus::ACTIVE) {
                $hold->expire();
            }
        });
    }

    /**
     * Clear all items from a cart.
     */
    public function clearCart(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            foreach ($cart->items as $item) {
                $this->removeItem($item);
            }
        });
    }

    /**
     * Update the primary contact for a cart item.
     *
     * @param  CartItem  $item  The cart item
     * @param  array  $contact  Contact info: [firstName, lastName, email, phone]
     */
    public function updatePrimaryContact(CartItem $item, array $contact): void
    {
        $item->setPrimaryContact($contact);
    }

    /**
     * Update guest names for a cart item (only if listing requires it).
     *
     * @param  CartItem  $item  The cart item
     * @param  array  $names  Array of guest names
     */
    public function updateGuestNames(CartItem $item, array $names): void
    {
        if (! $item->requiresTravelerNames()) {
            return;
        }

        $item->setGuestNames($names);
    }

    /**
     * Update extras for a cart item.
     *
     * @param  CartItem  $item  The cart item
     * @param  array  $extras  Array of extras: [{id, name, price, quantity}]
     */
    public function updateExtras(CartItem $item, array $extras): void
    {
        $item->update(['extras' => $extras]);
    }

    /**
     * Calculate cart totals using the price calculation service.
     *
     * @param  Cart  $cart  The cart to calculate
     * @return array{items: array, subtotal: float, total: float, currency: string}
     */
    public function calculateTotals(Cart $cart): array
    {
        $cart->load(['items.listing', 'items.hold']);

        $items = [];
        $subtotal = 0;
        $currency = 'EUR';

        foreach ($cart->items as $item) {
            $listing = $item->listing;

            if (! $listing) {
                continue;
            }

            // Calculate item price using person type breakdown if available
            if (! empty($item->person_type_breakdown)) {
                $result = $this->priceService->calculateTotal($listing, $item->person_type_breakdown, $item->currency);
                $itemSubtotal = $result['total'];
                $currency = $result['currency'];
                $breakdown = $result['breakdown'];
            } else {
                $result = $this->priceService->calculateSimpleTotal($listing, $item->quantity, $item->currency);
                $itemSubtotal = $result['total'];
                $currency = $result['currency'];
                $breakdown = null;
            }

            // Add extras
            $extrasTotal = $item->getExtrasTotal();

            $items[] = [
                'id' => $item->id,
                'listing_id' => $item->listing_id,
                'title' => $item->getTitle(),
                'slot_start' => $item->slot_start,
                'slot_end' => $item->slot_end,
                'quantity' => $item->quantity,
                'person_type_breakdown' => $item->person_type_breakdown,
                'breakdown' => $breakdown,
                'subtotal' => $itemSubtotal,
                'extras_total' => $extrasTotal,
                'total' => $itemSubtotal + $extrasTotal,
            ];

            $subtotal += $itemSubtotal + $extrasTotal;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $subtotal, // Can add service fees, taxes here later
            'currency' => $currency,
            'item_count' => count($items),
        ];
    }

    /**
     * Validate cart before checkout.
     *
     * @param  Cart  $cart  The cart to validate
     * @return array{valid: bool, errors: array}
     */
    public function validateForCheckout(Cart $cart): array
    {
        $errors = [];

        // Check cart is active
        if (! $cart->isActive()) {
            $errors[] = [
                'code' => 'cart_expired',
                'message' => 'Cart has expired',
            ];
        }

        // Check cart has items
        if ($cart->isEmpty()) {
            $errors[] = [
                'code' => 'cart_empty',
                'message' => 'Cart is empty',
            ];
        }

        // Validate each item
        foreach ($cart->items as $item) {
            $itemErrors = $this->validateItem($item);

            if (! empty($itemErrors)) {
                $errors = array_merge($errors, $itemErrors);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate a single cart item.
     *
     * @param  CartItem  $item  The item to validate
     * @return array Array of errors (empty if valid)
     */
    protected function validateItem(CartItem $item): array
    {
        $errors = [];

        // Check hold is still valid
        if (! $item->isHoldValid()) {
            $errors[] = [
                'code' => 'hold_expired',
                'message' => 'Hold has expired for ' . $item->getTitle(),
                'item_id' => $item->id,
            ];
        }

        // Check primary contact is set
        if (empty($item->primary_contact)) {
            $errors[] = [
                'code' => 'missing_contact',
                'message' => 'Primary contact required for ' . $item->getTitle(),
                'item_id' => $item->id,
            ];
        }

        // NOTE: Guest names validation removed - participant names are collected post-payment
        // This aligns cart checkout with BookingService which also does post-payment collection

        return $errors;
    }

    /**
     * Merge a guest cart into a user's cart after login.
     *
     * @param  string  $sessionId  The guest session ID
     * @param  User  $user  The logged-in user
     * @return Cart|null The merged cart
     */
    public function mergeGuestCart(string $sessionId, User $user): ?Cart
    {
        // Find guest cart
        $guestCart = Cart::active()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->first();

        if (! $guestCart) {
            return null;
        }

        // Find or create user cart
        $userCart = $this->getActiveCart($user, null);

        if (! $userCart) {
            // Just assign the guest cart to the user
            $guestCart->update(['user_id' => $user->id]);

            return $guestCart->fresh();
        }

        // Merge items from guest cart to user cart
        return DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $item) {
                // Move item to user's cart
                $item->update(['cart_id' => $userCart->id]);

                // Update hold's cart_id
                if ($item->hold) {
                    $item->hold->update(['cart_id' => $userCart->id]);
                }
            }

            // Move holds to user cart
            foreach ($guestCart->holds as $hold) {
                $hold->update(['cart_id' => $userCart->id]);
            }

            // Abandon the guest cart
            $guestCart->abandon();

            // Extend user cart expiration
            $userCart->extendExpiration();

            return $userCart->fresh(['items', 'holds']);
        });
    }

    /**
     * Extend all holds in the cart.
     * Called when user is actively engaging with checkout.
     *
     * @return array{extended: int, failed: int, unavailable: array}
     */
    public function extendHolds(Cart $cart): array
    {
        $newExpiration = now()->addMinutes(BookingHold::HOLD_DURATION_MINUTES);
        $extended = 0;
        $failed = 0;
        $unavailable = [];

        foreach ($cart->items as $item) {
            if (! $item->hold) {
                $failed++;
                continue;
            }

            $hold = $item->hold;

            // Case 1: Hold is still active (not expired yet) - just extend
            if ($hold->isActive()) {
                $hold->update(['expires_at' => $newExpiration]);
                $extended++;
                continue;
            }

            // Case 2: Hold status is ACTIVE but expires_at is past (just expired, capacity still reserved)
            if ($hold->status === HoldStatus::ACTIVE && $hold->expires_at->isPast()) {
                // Capacity is still held, just extend the time
                $hold->update(['expires_at' => $newExpiration]);
                $extended++;
                continue;
            }

            // Case 3: Hold was fully expired (status=EXPIRED, capacity released)
            // Need to check if we can re-reserve capacity
            if ($hold->status === HoldStatus::EXPIRED) {
                $slot = $hold->slot;

                // Check if slot still has capacity (uses computed accessor)
                if ($slot && $slot->remainingCapacity >= $hold->quantity) {
                    // Reactivate hold (capacity tracked automatically via accessor)
                    $hold->update([
                        'status' => HoldStatus::ACTIVE,
                        'expires_at' => $newExpiration,
                    ]);
                    $extended++;
                    continue;
                }

                // Slot no longer available
                $unavailable[] = [
                    'item_id' => $item->id,
                    'title' => $item->getTitle(),
                    'reason' => 'slot_unavailable',
                ];
                $failed++;
                continue;
            }

            // Hold is in some other status (converted, etc.) - can't extend
            $failed++;
        }

        // Only extend cart if at least one hold was extended
        if ($extended > 0) {
            $cart->extendExpiration();
        }

        return [
            'extended' => $extended,
            'failed' => $failed,
            'unavailable' => $unavailable,
        ];
    }

    /**
     * Mark cart as checking out.
     */
    public function startCheckout(Cart $cart): void
    {
        $cart->startCheckout();
    }

    /**
     * Get cart summary for display.
     */
    public function getSummary(Cart $cart): array
    {
        $totals = $this->calculateTotals($cart);

        return [
            'id' => $cart->id,
            'status' => $cart->status,
            'expires_at' => $cart->expires_at,
            'item_count' => $totals['item_count'],
            'items' => $totals['items'],
            'subtotal' => $totals['subtotal'],
            'total' => $totals['total'],
            'currency' => $totals['currency'],
        ];
    }

    /**
     * Get the price for the specified currency from the pricing structure.
     *
     * Supports both old single-currency format and new dual-currency format.
     */
    protected function getPriceForCurrency(array $pricing, string $currency): float
    {
        // Handle person_types pricing structure (new format)
        if (isset($pricing['person_types']) && ! empty($pricing['person_types'])) {
            $firstType = $pricing['person_types'][0] ?? [];
            if ($currency === 'TND' && isset($firstType['tnd_price'])) {
                return (float) $firstType['tnd_price'];
            }
            if ($currency === 'EUR' && isset($firstType['eur_price'])) {
                return (float) $firstType['eur_price'];
            }
        }

        // Direct dual-pricing format
        if ($currency === 'TND' && isset($pricing['tnd_price'])) {
            return (float) $pricing['tnd_price'];
        }

        if ($currency === 'EUR' && isset($pricing['eur_price'])) {
            return (float) $pricing['eur_price'];
        }

        // Fallback to old single-currency format
        return (float) ($pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? 0);
    }

    /**
     * Extract listing title, handling corrupted data.
     */
    protected function extractListingTitle(Listing $listing): array
    {
        $translations = $listing->getTranslations('title');
        $result = [];

        foreach (['en', 'fr'] as $locale) {
            $value = $translations[$locale] ?? null;

            // Handle corrupted nested data
            if (is_array($value)) {
                // Try to find a string value in the nested array
                $value = $this->findFirstString($value) ?? $listing->slug;
            }

            $result[$locale] = is_string($value) && ! empty($value) ? $value : $listing->slug;
        }

        return $result;
    }

    /**
     * Recursively find first string in nested array.
     */
    protected function findFirstString(array $arr): ?string
    {
        foreach ($arr as $value) {
            if (is_string($value) && ! empty($value)) {
                return $value;
            }
            if (is_array($value)) {
                $found = $this->findFirstString($value);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }
}
