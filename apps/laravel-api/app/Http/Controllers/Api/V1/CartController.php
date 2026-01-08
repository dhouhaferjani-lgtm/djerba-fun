<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Get the current cart for the user or session.
     *
     * Performance: Eager load relationships with specific columns
     */
    public function show(Request $request): CartResource|JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        $cart = $this->cartService->getActiveCart($user, $sessionId);

        if (! $cart) {
            return response()->json([
                'message' => 'No active cart found',
                'cart' => null,
            ], 200);
        }

        // Eager load relationships to prevent N+1 queries
        $cart->load([
            'items.hold.slot',
            'items.listing.vendor',
            'items.listing.location'
        ]);

        return new CartResource($cart);
    }

    /**
     * Add an item to the cart from a booking hold.
     *
     * Performance: Eager load hold relationships with specific columns
     */
    public function addItem(AddToCartRequest $request): CartResource|JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $sessionId = $request->getSessionId();

        // Performance: Find the hold with specific column selection
        $hold = BookingHold::with([
            'slot:id,listing_id,date,start_time,end_time,capacity,remaining_capacity',
            'listing:id,uuid,location_id,title,slug,pricing,service_type,status'
        ])->findOrFail($validated['hold_id']);

        // Verify hold ownership
        $isOwner = ($user && $hold->user_id === $user->id) ||
                   ($sessionId && $hold->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This hold does not belong to you',
            ], 403);
        }

        // Check hold is still valid
        if (! $hold->isActive()) {
            return response()->json([
                'message' => 'This hold has expired',
            ], 410);
        }

        // Check if hold is already in a cart
        if ($hold->cart_id) {
            return response()->json([
                'message' => 'This item is already in a cart',
            ], 422);
        }

        // Get or create cart
        $cart = $this->cartService->getOrCreateCart($user, $sessionId);

        // Add item to cart
        $this->cartService->addItem($cart, $hold, $hold->listing);

        // Load cart relationships to prevent N+1 queries
        $cart->load([
            'items.hold.slot',
            'items.listing.vendor',
            'items.listing.location'
        ]);

        return new CartResource($cart);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id') ?? $request->input('session_id');
        $cart = $item->cart;

        // Verify cart ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This cart does not belong to you',
            ], 403);
        }

        // Remove item (and release hold)
        $this->cartService->removeItem($item);

        return response()->json([
            'message' => 'Item removed from cart',
        ]);
    }

    /**
     * Update a cart item (primary contact, guest names, extras).
     */
    public function updateItem(UpdateCartItemRequest $request, CartItem $item): CartItemResource|JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->validated('session_id');
        $cart = $item->cart;

        // Verify cart ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This cart does not belong to you',
            ], 403);
        }

        // Update primary contact if provided
        $primaryContact = $request->getPrimaryContact();

        if ($primaryContact) {
            $this->cartService->updatePrimaryContact($item, $primaryContact);
        }

        // Update guest names if provided
        $guestNames = $request->getGuestNames();

        if ($guestNames !== null) {
            $this->cartService->updateGuestNames($item, $guestNames);
        }

        // Update extras if provided
        $extras = $request->getExtras();

        if ($extras !== null) {
            $this->cartService->updateExtras($item, $extras);
        }

        // Load relationships to prevent N+1 queries
        $item->load([
            'hold.slot',
            'listing.vendor',
            'listing.location'
        ]);

        return new CartItemResource($item->fresh());
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id') ?? $request->input('session_id');

        $cart = $this->cartService->getActiveCart($user, $sessionId);

        if (! $cart) {
            return response()->json([
                'message' => 'No active cart found',
            ], 404);
        }

        // Verify cart ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This cart does not belong to you',
            ], 403);
        }

        $this->cartService->clearCart($cart);

        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }

    /**
     * Get cart totals and validation status.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        $cart = $this->cartService->getActiveCart($user, $sessionId);

        if (! $cart) {
            return response()->json([
                'message' => 'No active cart found',
                'cart' => null,
            ], 200);
        }

        $summary = $this->cartService->getSummary($cart);
        $validation = $this->cartService->validateForCheckout($cart);

        return response()->json([
            'cart' => $summary,
            'validation' => $validation,
        ]);
    }

    /**
     * Extend holds in the cart (keep-alive during checkout).
     * Allows extending recently expired carts (within 5 minutes of expiration).
     */
    public function extendHolds(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id') ?? $request->input('session_id');

        // Performance: Find cart by owner with eager loading
        $cart = Cart::where('status', Cart::STATUS_ACTIVE)
            ->where('expires_at', '>', now()->subMinutes(5)) // Allow up to 5 min past expiration
            ->forOwner($user?->id, $sessionId)
            // Eager load relationships
            ->with([
                'items.hold.slot',
                'items.listing.vendor',
                'items.listing.location'
            ])
            ->first();

        if (! $cart) {
            return response()->json([
                'message' => 'No cart found to extend',
            ], 404);
        }

        // Verify cart ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This cart does not belong to you',
            ], 403);
        }

        $result = $this->cartService->extendHolds($cart);

        // Return updated cart data for frontend
        $cart->refresh();

        // Build response based on what happened
        $response = [
            'message' => $result['extended'] > 0 ? 'Holds extended' : 'No holds could be extended',
            'extended' => $result['extended'],
            'failed' => $result['failed'],
            'expires_at' => $cart->expires_at->toIso8601String(),
            'expiresInSeconds' => max(0, $cart->expires_at->getTimestamp() - now()->getTimestamp()),
        ];

        // Include unavailable items if any
        if (! empty($result['unavailable'])) {
            $response['unavailable'] = $result['unavailable'];
        }

        // Return appropriate status code
        if ($result['extended'] === 0 && $result['failed'] > 0) {
            return response()->json($response, 409); // Conflict - items no longer available
        }

        return response()->json($response);
    }

    /**
     * Merge guest cart into user cart after login.
     */
    public function merge(Request $request): CartResource|JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Authentication required',
            ], 401);
        }

        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'message' => 'Session ID required',
            ], 422);
        }

        $mergedCart = $this->cartService->mergeGuestCart($sessionId, $user);

        if (! $mergedCart) {
            // No guest cart to merge, return user's cart
            $cart = $this->cartService->getActiveCart($user, null);

            if (! $cart) {
                return response()->json([
                    'message' => 'No cart to merge',
                    'cart' => null,
                ], 200);
            }

            // Load relationships
            $cart->load([
                'items.hold.slot',
                'items.listing.vendor',
                'items.listing.location'
            ]);

            return new CartResource($cart);
        }

        // Load relationships
        $mergedCart->load([
            'items.hold.slot',
            'items.listing.vendor',
            'items.listing.location'
        ]);

        return new CartResource($mergedCart);
    }
}
