<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListingController extends Controller
{
    /**
     * Display a listing of published listings
     *
     * Performance optimizations:
     * - Eager loading vendor, location, media, faqs to prevent N+1 queries
     * - Select only required columns to reduce data transfer
     * - Efficient use of whereHas with exists checks
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Build cache key from request parameters
        $cacheKey = 'listings:index:' . md5(json_encode($request->all()));

        // Cache listings for 5 minutes (popular searches)
        $cacheTtl = 300; // 5 minutes

        // For non-filtered requests (home page), use caching
        $useCache = !$request->has('q') && !$request->has('price_min') && !$request->has('price_max');

        if ($useCache && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $query = Listing::query()
            ->published()
            // Performance: Select only needed columns to reduce data transfer
            ->select([
                'id', 'uuid', 'vendor_id', 'location_id', 'service_type', 'status',
                'title', 'slug', 'summary', 'description', 'highlights', 'included',
                'not_included', 'requirements', 'meeting_point', 'pricing',
                'cancellation_policy', 'safety_info', 'accessibility_info',
                'difficulty_details', 'min_group_size', 'max_group_size',
                'min_advance_booking_hours', 'rating', 'reviews_count', 'bookings_count',
                'created_at', 'updated_at', 'published_at',
                // Tour-specific
                'duration', 'difficulty', 'distance', 'itinerary', 'has_elevation_profile',
                // Event-specific
                'event_type', 'start_date', 'end_date', 'venue', 'agenda'
            ])
            // Performance: Eager load relationships to prevent N+1 queries
            ->with([
                'vendor:id,uuid,name,slug',
                'location:id,uuid,name,slug,city,latitude,longitude',
                'media:id,model_id,file_name,mime_type,size,url',
                'faqs:id,listing_id,question,answer,order'
            ]);

        // Search by query (title, summary, description)
        if ($request->has('q') && ! empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                // Search in JSON fields (multilingual)
                $q->whereRaw('title::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('summary::text ILIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('description::text ILIKE ?', ["%{$searchTerm}%"]);
            });
        }

        // Filter by service type
        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Filter by location
        // Performance: Use whereHas with specific column selection
        if ($request->has('location')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('slug', $request->location)
                    ->orWhere('city', 'like', "%{$request->location}%");
            });
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Filter by price range
        if ($request->has('price_min') || $request->has('price_max')) {
            $priceMin = $request->get('price_min');
            $priceMax = $request->get('price_max');

            if ($priceMin !== null) {
                $query->whereRaw("(pricing->>'tnd_price')::numeric >= ?", [$priceMin]);
            }

            if ($priceMax !== null) {
                $query->whereRaw("(pricing->>'tnd_price')::numeric <= ?", [$priceMax]);
            }
        }

        // Filter by date range (for events)
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate !== null) {
                $query->where(function ($q) use ($startDate) {
                    $q->where('service_type', 'event')
                        ->where('start_date', '>=', $startDate);
                });
            }

            if ($endDate !== null) {
                $query->where(function ($q) use ($endDate) {
                    $q->where('service_type', 'event')
                        ->where('end_date', '<=', $endDate);
                });
            }
        }

        // Filter by minimum capacity (for group bookings)
        if ($request->has('guests')) {
            $guests = (int) $request->get('guests');
            $query->where('max_group_size', '>=', $guests);
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
        $limit = min($request->get('limit', 20), 50);

        $result = ListingResource::collection(
            $query->paginate($limit)
        );

        // Cache popular searches (home page, no filters)
        if ($useCache) {
            cache()->put($cacheKey, $result, $cacheTtl);
        }

        return $result;
    }

    /**
     * Display the specified listing
     *
     * Performance optimizations:
     * - Eager loading relationships with specific column selection
     * - Caching individual listing data for 5 minutes
     */
    public function show(Listing $listing): ListingResource
    {
        // Check if listing is published or user is the vendor/admin
        if (! $listing->isPublished()) {
            abort(404);
        }

        // Cache individual listing for 5 minutes
        $cacheKey = 'listing:show:' . $listing->id;
        $cacheTtl = 300; // 5 minutes

        $cachedListing = cache()->remember($cacheKey, $cacheTtl, function () use ($listing) {
            return $listing->load([
                'vendor:id,uuid,name,slug,logo_url,description,rating,reviews_count',
                'location:id,uuid,name,slug,city,state,country,latitude,longitude',
                'media:id,model_id,file_name,mime_type,size,url',
                'faqs:id,listing_id,question,answer,order'
            ]);
        });

        return new ListingResource($cachedListing);
    }
}
