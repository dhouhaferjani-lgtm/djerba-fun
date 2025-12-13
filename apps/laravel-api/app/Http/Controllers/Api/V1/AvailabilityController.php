<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetAvailabilityRequest;
use App\Http\Resources\AvailabilitySlotResource;
use App\Jobs\CalculateAvailabilityJob;
use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityController extends Controller
{
    /**
     * Get availability for a listing.
     *
     * @param  GetAvailabilityRequest  $request
     * @param  Listing  $listing
     * @return AnonymousResourceCollection
     */
    public function index(GetAvailabilityRequest $request, Listing $listing): AnonymousResourceCollection
    {
        $startDate = Carbon::parse($request->validated('start_date'));
        $endDate = Carbon::parse($request->validated('end_date'));

        // Dispatch job to calculate availability if needed
        // This ensures slots are generated for the requested date range
        CalculateAvailabilityJob::dispatch($listing, $startDate, $endDate);

        // Fetch available slots for the date range
        $slots = $listing->availabilitySlots()
            ->betweenDates($startDate, $endDate)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return AvailabilitySlotResource::collection($slots);
    }

    /**
     * Refresh availability for a listing.
     *
     * @param  GetAvailabilityRequest  $request
     * @param  Listing  $listing
     * @return JsonResponse
     */
    public function refresh(GetAvailabilityRequest $request, Listing $listing): JsonResponse
    {
        $startDate = Carbon::parse($request->validated('start_date'));
        $endDate = Carbon::parse($request->validated('end_date'));

        // Dispatch job to recalculate availability
        CalculateAvailabilityJob::dispatch($listing, $startDate, $endDate);

        return response()->json([
            'message' => 'Availability calculation started',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }
}
