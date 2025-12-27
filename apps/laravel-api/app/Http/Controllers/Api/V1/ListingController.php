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
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Listing::query()
            ->published()
            ->with(['vendor', 'location', 'media', 'faqs']);

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

        return ListingResource::collection(
            $query->paginate($limit)
        );
    }

    /**
     * Display the specified listing
     */
    public function show(Listing $listing): ListingResource
    {
        // Check if listing is published or user is the vendor/admin
        if (! $listing->isPublished()) {
            abort(404);
        }

        return new ListingResource(
            $listing->load(['vendor', 'location', 'media', 'faqs'])
        );
    }
}
