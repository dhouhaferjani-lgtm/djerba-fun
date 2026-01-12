<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Http\Resources\LocationResource;
use App\Models\Listing;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of popular destinations
     *
     * Performance optimizations:
     * - Select only needed columns
     * - Cache results for 30 minutes (locations change infrequently)
     */
    public function index(Request $request)
    {
        $cacheKey = 'locations:all:v3';
        $cacheTtl = 1800; // 30 minutes

        $locations = cache()->remember($cacheKey, $cacheTtl, function () {
            return Location::query()
                ->select([
                    'id', 'uuid', 'name', 'slug', 'city', 'region', 'country',
                    'latitude', 'longitude', 'description', 'image_url',
                    'created_at', 'updated_at'
                ])
                ->whereHas('listings', fn ($q) => $q->where('status', 'published'))
                ->withCount(['listings as listings_count' => fn ($q) => $q->where('status', 'published')])
                ->orderByDesc('listings_count')
                ->limit(50)
                ->get();
        });

        return LocationResource::collection($locations);
    }

    /**
     * Display the specified destination with its listings
     *
     * Performance optimizations:
     * - Eager load listings with specific columns
     * - Cache location with listings for 15 minutes
     */
    public function show(Request $request, string $slug)
    {
        $cacheKey = 'location:show:' . $slug;
        $cacheTtl = 900; // 15 minutes

        $data = cache()->remember($cacheKey, $cacheTtl, function () use ($slug) {
            $location = Location::where('slug', $slug)
                ->withCount(['listings' => fn ($q) => $q->where('status', 'published')])
                ->firstOrFail();

            // Performance: Get listings with eager loading and specific columns
            $listings = Listing::query()
                ->where('location_id', $location->id)
                ->where('status', 'published')
                // Performance: Select only needed columns
                ->select([
                    'id', 'uuid', 'vendor_id', 'location_id', 'service_type', 'status',
                    'title', 'slug', 'summary', 'description', 'pricing',
                    'min_group_size', 'max_group_size', 'rating', 'reviews_count',
                    'bookings_count', 'created_at', 'updated_at', 'published_at',
                    'duration', 'difficulty', 'service_type'
                ])
                // Performance: Eager load with specific columns
                ->with([
                    'location:id,uuid,name,slug,city,latitude,longitude',
                    'media:id,uuid,url,thumbnail_url,alt,type,order,category',
                    'vendor:id,uuid'
                ])
                ->orderByDesc('created_at')
                ->get();

            return [
                'location' => $location,
                'listings' => $listings,
            ];
        });

        return response()->json([
            'location' => new LocationResource($data['location']),
            'listings' => ListingResource::collection($data['listings']),
        ]);
    }
}
