<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityTypeResource;
use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ActivityTypeController extends Controller
{
    /**
     * Display a listing of active activity types.
     *
     * This endpoint returns all active activity types, ordered by display_order.
     * Results are cached for 30 minutes since activity types rarely change.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $cacheKey = 'activity_types_list';
        $cacheTtl = 60 * 30; // 30 minutes

        try {
            $activityTypes = Cache::remember($cacheKey, $cacheTtl, function () {
                return ActivityType::query()
                    ->active()
                    ->ordered()
                    ->get();
            });
        } catch (\Exception $e) {
            // Return empty collection if table doesn't exist yet
            $activityTypes = collect([]);
        }

        return ActivityTypeResource::collection($activityTypes);
    }

    /**
     * Display the specified activity type.
     */
    public function show(ActivityType $activityType): ActivityTypeResource
    {
        return new ActivityTypeResource($activityType);
    }
}
