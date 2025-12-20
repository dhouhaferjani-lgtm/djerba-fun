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
        $query = $listing->availabilitySlots()
            ->betweenDates($startDate, $endDate);

        // Apply minimum advance booking time filter
        if ($listing->min_advance_booking_hours > 0) {
            $cutoffTime = Carbon::now()->addHours($listing->min_advance_booking_hours);

            // Filter out slots that start before the cutoff time
            $query->where(function ($q) use ($cutoffTime) {
                $q->whereRaw("CONCAT(date, ' ', start_time) >= ?", [$cutoffTime->format('Y-m-d H:i:s')]);
            });
        }

        $slots = $query->orderBy('date')
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
