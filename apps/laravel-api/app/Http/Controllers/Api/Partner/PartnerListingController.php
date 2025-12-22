<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Partner\PartnerListingResource;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class PartnerListingController extends Controller
{
    /**
     * Search listings (optimized for API partners).
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('listings:read')) {
            abort(403, 'Partner does not have permission to read listings');
        }

        $query = Listing::query()
            ->published()
            ->with(['vendor.vendorProfile', 'location', 'media']);

        // Filter by service type
        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Filter by location (coordinates)
        if ($request->has('lat') && $request->has('lng')) {
            $lat = $request->lat;
            $lng = $request->lng;
            $radius = $request->get('radius', 50); // km

            $query->whereHas('location', function ($q) use ($lat, $lng, $radius) {
                // Haversine formula for distance calculation
                $q->whereRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?',
                    [$lat, $lng, $lat, $radius]
                );
            });
        }

        // Filter by location name/city
        if ($request->has('location')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('slug', $request->location)
                    ->orWhere('city', 'like', "%{$request->location}%")
                    ->orWhere('name', 'like', "%{$request->location}%");
            });
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->whereRaw("(pricing->>'base')::integer >= ?", [$request->min_price]);
        }
        if ($request->has('max_price')) {
            $query->whereRaw("(pricing->>'base')::integer <= ?", [$request->max_price]);
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Search by keywords
        if ($request->has('q')) {
            $keywords = $request->q;
            $query->where(function ($q) use ($keywords) {
                $q->where('title', 'like', "%{$keywords}%")
                    ->orWhere('description', 'like', "%{$keywords}%")
                    ->orWhereJsonContains('tags', $keywords);
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'popularity');
        match ($sortBy) {
            'price_asc' => $query->orderByRaw("(pricing->>'base')::integer ASC"),
            'price_desc' => $query->orderByRaw("(pricing->>'base')::integer DESC"),
            'rating' => $query->orderBy('rating', 'desc'),
            'newest' => $query->orderBy('published_at', 'desc'),
            default => $query->orderBy('bookings_count', 'desc'),
        };

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);

        return PartnerListingResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Get a specific listing by ID.
     *
     * @param Request $request
     * @param Listing $listing
     * @return PartnerListingResource
     */
    public function show(Request $request, Listing $listing): PartnerListingResource
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('listings:read')) {
            abort(403, 'Partner does not have permission to read listings');
        }

        if (!$listing->isPublished()) {
            abort(404, 'Listing not found');
        }

        $listing->load(['vendor.vendorProfile', 'location', 'media', 'availabilityRules']);

        return new PartnerListingResource($listing);
    }

    /**
     * Get availability for a listing.
     *
     * @param Request $request
     * @param Listing $listing
     * @return JsonResponse
     */
    public function availability(Request $request, Listing $listing): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('listings:read')) {
            abort(403, 'Partner does not have permission to read listings');
        }

        if (!$listing->isPublished()) {
            abort(404, 'Listing not found');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
        ]);

        $slots = $listing->availabilitySlots()
            ->whereBetween('start_time', [
                $request->date_from,
                $request->date_to,
            ])
            ->where('available_quantity', '>', 0)
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'listing_id' => $listing->id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'slots' => $slots->map(fn ($slot) => [
                'id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'available_quantity' => $slot->available_quantity,
                'total_quantity' => $slot->total_quantity,
                'price' => $slot->price,
            ]),
        ]);
    }
}
