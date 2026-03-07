<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Listing;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    /**
     * Get the authenticated user's wishlist.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $wishlists = $user->wishlists()
            ->with(['listing' => function ($query) {
                $query->with(['location', 'activityType'])
                    ->published();
            }])
            ->latest()
            ->paginate($request->input('per_page', 20));

        return WishlistResource::collection($wishlists);
    }

    /**
     * Add a listing to the wishlist.
     */
    public function store(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        // Check if listing is published
        if (! $listing->isPublished()) {
            return response()->json([
                'message' => 'This listing is not available.',
            ], 404);
        }

        // Check if already in wishlist
        if ($user->hasInWishlist($listing)) {
            return response()->json([
                'message' => 'Listing is already in your wishlist.',
                'data' => [
                    'listing_id' => $listing->id,
                    'in_wishlist' => true,
                ],
            ], 200);
        }

        // Add to wishlist
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        return response()->json([
            'message' => 'Listing added to wishlist.',
            'data' => new WishlistResource($wishlist->load('listing')),
        ], 201);
    }

    /**
     * Remove a listing from the wishlist.
     */
    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        $deleted = $user->wishlists()
            ->where('listing_id', $listing->id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'Listing was not in your wishlist.',
            ], 404);
        }

        return response()->json([
            'message' => 'Listing removed from wishlist.',
        ], 200);
    }

    /**
     * Toggle a listing in the wishlist.
     */
    public function toggle(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        // Check if listing is published
        if (! $listing->isPublished()) {
            return response()->json([
                'message' => 'This listing is not available.',
            ], 404);
        }

        $existingWishlist = $user->wishlists()
            ->where('listing_id', $listing->id)
            ->first();

        if ($existingWishlist) {
            $existingWishlist->delete();

            return response()->json([
                'message' => 'Listing removed from wishlist.',
                'data' => [
                    'listing_id' => $listing->id,
                    'in_wishlist' => false,
                ],
            ], 200);
        }

        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        return response()->json([
            'message' => 'Listing added to wishlist.',
            'data' => [
                'listing_id' => $listing->id,
                'in_wishlist' => true,
                'wishlist_id' => $wishlist->id,
            ],
        ], 200);
    }

    /**
     * Check if a listing is in the user's wishlist.
     */
    public function check(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        $inWishlist = $user->hasInWishlist($listing);

        return response()->json([
            'data' => [
                'listing_id' => $listing->id,
                'in_wishlist' => $inWishlist,
            ],
        ]);
    }

    /**
     * Get wishlist IDs only (for efficient client-side checking).
     * Returns listing UUIDs (not integer IDs) for frontend compatibility.
     */
    public function ids(Request $request): JsonResponse
    {
        $user = $request->user();

        // Return listing UUIDs, not integer IDs
        $listingUuids = $user->wishlists()
            ->join('listings', 'wishlists.listing_id', '=', 'listings.id')
            ->pluck('listings.uuid')
            ->toArray();

        return response()->json([
            'data' => [
                'listing_ids' => $listingUuids,
            ],
        ]);
    }
}
