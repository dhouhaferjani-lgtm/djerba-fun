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
     */
    public function index(Request $request)
    {
        $locations = Location::query()
            ->where('listings_count', '>', 0)
            ->orderByDesc('listings_count')
            ->limit(20)
            ->get();

        return LocationResource::collection($locations);
    }

    /**
     * Display the specified destination with its listings
     */
    public function show(Request $request, string $slug)
    {
        $location = Location::where('slug', $slug)->firstOrFail();

        // Get listings for this location
        $listings = Listing::query()
            ->where('location_id', $location->id)
            ->where('status', 'published')
            ->with(['location', 'media', 'vendor'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'location' => new LocationResource($location),
            'listings' => ListingResource::collection($listings),
        ]);
    }
}
