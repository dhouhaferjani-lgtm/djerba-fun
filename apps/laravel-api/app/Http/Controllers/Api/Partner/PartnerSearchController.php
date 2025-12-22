<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Partner\PartnerListingResource;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PartnerSearchController extends Controller
{
    /**
     * Natural language search endpoint for API partners.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('listings:search')) {
            abort(403, 'Partner does not have permission to search listings');
        }

        $validated = $request->validate([
            'query' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'guests' => 'nullable|integer|min:1|max:100',
            'budget' => 'nullable|integer|min:0',
            'service_type' => 'nullable|in:tour,event',
            'difficulty' => 'nullable|in:easy,moderate,challenging,difficult',
            'min_rating' => 'nullable|numeric|min:0|max:5',
        ]);

        $query = Listing::query()
            ->published()
            ->with(['vendor.vendorProfile', 'location', 'media']);

        // Natural language query processing
        if (!empty($validated['query'])) {
            $keywords = $validated['query'];
            $query->where(function ($q) use ($keywords) {
                $q->where('title', 'like', "%{$keywords}%")
                    ->orWhere('description', 'like', "%{$keywords}%")
                    ->orWhereJsonContains('tags', $keywords);
            });
        }

        // Location filter
        if (!empty($validated['location'])) {
            $location = $validated['location'];
            $query->whereHas('location', function ($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                    ->orWhere('name', 'like', "%{$location}%")
                    ->orWhere('region', 'like', "%{$location}%");
            });
        }

        // Service type
        if (!empty($validated['service_type'])) {
            $query->where('service_type', $validated['service_type']);
        }

        // Difficulty
        if (!empty($validated['difficulty'])) {
            $query->where('difficulty', $validated['difficulty']);
        }

        // Budget filter
        if (isset($validated['budget'])) {
            $query->whereRaw("(pricing->>'base')::integer <= ?", [$validated['budget']]);
        }

        // Rating filter
        if (isset($validated['min_rating'])) {
            $query->where('rating', '>=', $validated['min_rating']);
        }

        // Date availability filter
        if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
            $query->whereHas('availabilitySlots', function ($q) use ($validated) {
                $q->whereBetween('start_time', [
                    $validated['date_from'],
                    $validated['date_to'],
                ])
                ->where('available_quantity', '>', 0);
            });
        }

        // Guest capacity filter
        if (!empty($validated['guests'])) {
            $query->whereRaw("(capacity->>'max')::integer >= ?", [$validated['guests']]);
        }

        // Order by relevance (rating and popularity)
        $query->orderByDesc('rating')
            ->orderByDesc('bookings_count');

        // Limit results for partners
        $results = $query->limit(20)->get();

        // Generate recommendations based on search
        $recommendations = $this->generateRecommendations($validated, $results);

        return response()->json([
            'query' => $validated,
            'results_count' => $results->count(),
            'results' => PartnerListingResource::collection($results),
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Generate recommendations based on search parameters.
     *
     * @param array $params
     * @param \Illuminate\Support\Collection $results
     * @return array
     */
    protected function generateRecommendations(array $params, $results): array
    {
        $recommendations = [];

        // Budget recommendation
        if (isset($params['budget']) && $results->isEmpty()) {
            $recommendations[] = [
                'type' => 'budget',
                'message' => 'No listings found within your budget. Consider increasing your budget or looking at different dates.',
            ];
        }

        // Location recommendation
        if (!empty($params['location']) && $results->isEmpty()) {
            $recommendations[] = [
                'type' => 'location',
                'message' => 'No listings found in this location. Consider nearby areas or different service types.',
            ];
        }

        // Date recommendation
        if (!empty($params['date_from']) && $results->isEmpty()) {
            $recommendations[] = [
                'type' => 'dates',
                'message' => 'No availability for selected dates. Try flexible dates or check availability calendar.',
            ];
        }

        // Popular alternatives
        if ($results->count() < 5) {
            $recommendations[] = [
                'type' => 'popular',
                'message' => 'Consider our top-rated activities in other locations.',
            ];
        }

        return $recommendations;
    }
}
