<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeedGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __construct(
        protected FeedGeneratorService $feedGenerator
    ) {}

    /**
     * Get listings feed in JSON format.
     */
    public function listingsJson(): JsonResponse
    {
        $feed = $this->feedGenerator->generateListingsJsonFeed();

        return response()->json($feed);
    }

    /**
     * Get listings feed in CSV format.
     */
    public function listingsCsv(): Response
    {
        $csv = $this->feedGenerator->generateListingsCsvFeed();

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="listings-' . now()->format('Y-m-d') . '.csv"');
    }

    /**
     * Get availability feed in JSON format.
     */
    public function availabilityJson(): JsonResponse
    {
        $feed = $this->feedGenerator->generateAvailabilityJsonFeed();

        return response()->json($feed);
    }
}
