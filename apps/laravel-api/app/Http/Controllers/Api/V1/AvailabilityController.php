<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ServiceType;
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
     * Performance optimizations:
     * - Cache availability slots for 2 minutes (balance between freshness and performance)
     * - Select only needed columns from slots table
     */
    public function index(GetAvailabilityRequest $request, Listing $listing): AnonymousResourceCollection
    {
        $startDate = Carbon::parse($request->validated('start_date'));
        $endDate = Carbon::parse($request->validated('end_date'));

        // Build cache key from listing and date range
        $cacheKey = sprintf(
            'availability:listing:%s:%s:%s',
            $listing->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
        $cacheTtl = 120; // 2 minutes - short TTL for availability

        $slots = cache()->remember($cacheKey, $cacheTtl, function () use ($listing, $startDate, $endDate) {
            // Dispatch job to calculate availability if needed
            // For accommodations, run synchronously to ensure ALL slots exist before returning
            // (accommodations validate entire date range, not just single slot)
            if ($listing->service_type === ServiceType::ACCOMMODATION) {
                CalculateAvailabilityJob::dispatchSync($listing, $startDate, $endDate);
            } else {
                CalculateAvailabilityJob::dispatch($listing, $startDate, $endDate);
            }

            // Performance: Fetch available slots with only needed columns
            $query = $listing->availabilitySlots()
                ->selectApi() // Use model scope to prevent column mismatch issues
                ->betweenDates($startDate, $endDate);

            // Apply minimum advance booking time filter
            if ($listing->min_advance_booking_hours > 0) {
                $cutoffTime = Carbon::now()->addHours($listing->min_advance_booking_hours);

                // Filter out slots that start before the cutoff time
                $query->where(function ($q) use ($cutoffTime) {
                    $q->whereRaw("CONCAT(date, ' ', start_time) >= ?", [$cutoffTime->format('Y-m-d H:i:s')]);
                });
            }

            return $query->orderBy('date')
                ->orderBy('start_time')
                ->get();
        });

        return AvailabilitySlotResource::collection($slots);
    }

    /**
     * Refresh availability for a listing.
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
