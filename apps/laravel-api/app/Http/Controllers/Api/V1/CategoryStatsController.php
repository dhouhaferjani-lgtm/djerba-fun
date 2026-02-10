<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryStatsController extends Controller
{
    /**
     * Get category statistics for the homepage.
     * Returns counts and images for tours and events.
     */
    public function index(): JsonResponse
    {
        $stats = Cache::remember('category_stats', 300, function () {
            return [
                'tours' => $this->getStatsForType('tour'),
                'events' => $this->getStatsForType('event'),
                'sejours' => $this->getStatsForType('sejour'),
            ];
        });

        return response()->json(['data' => $stats]);
    }

    /**
     * Get stats for a specific service type.
     */
    private function getStatsForType(string $serviceType): array
    {
        $listings = Listing::query()
            ->where('status', 'published')
            ->where('service_type', $serviceType)
            ->with(['media' => function ($query) {
                $query->whereIn('category', ['hero', 'gallery', 'featured'])
                    ->orderByRaw("CASE WHEN category = 'hero' THEN 1 WHEN category = 'featured' THEN 2 ELSE 3 END");
            }])
            ->get();

        $images = [];
        foreach ($listings as $listing) {
            $media = $listing->media->first();
            if ($media && $media->url && ! in_array($media->url, $images)) {
                $images[] = $media->url;
                if (count($images) >= 5) {
                    break;
                }
            }
        }

        // Fallback images if not enough listings
        $fallbacks = [
            'https://images.unsplash.com/photo-1551632811-561732d1e306?w=600&q=80',
            'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=600&q=80',
        ];

        while (count($images) < 2) {
            $images[] = array_shift($fallbacks);
        }

        return [
            'count' => $listings->count(),
            'images' => $images,
        ];
    }
}
