<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ServiceType;
use App\Enums\TagType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TagController extends Controller
{
    /**
     * Display a listing of active tags.
     *
     * This endpoint returns all active tags, optionally filtered by type or service_type.
     * Results are cached for 30 minutes since tags rarely change.
     *
     * Query parameters:
     * - type: Filter by tag type (tour_type, boat_type, space_type, event_feature, amenity)
     * - service_type: Filter by applicable service type (tour, nautical, accommodation, event)
     * - grouped: If true, returns tags grouped by type (default: false)
     */
    public function index(Request $request): AnonymousResourceCollection|array
    {
        $type = $request->query('type');
        $serviceType = $request->query('service_type');
        $grouped = $request->boolean('grouped');

        // Build cache key based on filters
        $cacheKey = 'tags_list_' . ($type ?? 'all') . '_' . ($serviceType ?? 'all') . '_' . ($grouped ? 'grouped' : 'flat');
        $cacheTtl = 60 * 30; // 30 minutes

        try {
            $result = Cache::remember($cacheKey, $cacheTtl, function () use ($type, $serviceType, $grouped) {
                $query = Tag::query()->active()->ordered();

                // Filter by tag type
                if ($type) {
                    $tagType = TagType::tryFrom($type);
                    if ($tagType) {
                        $query->ofType($tagType);
                    }
                }

                // Filter by service type
                if ($serviceType) {
                    $query->forServiceType($serviceType);
                }

                $tags = $query->get();

                if ($grouped) {
                    return $tags->groupBy(fn (Tag $tag) => $tag->type?->value ?? 'other');
                }

                return $tags;
            });

            if ($grouped) {
                // Return grouped response
                $groupedData = [];
                foreach ($result as $groupType => $tags) {
                    $tagType = TagType::tryFrom($groupType);
                    $groupedData[] = [
                        'type' => $groupType,
                        'label' => $tagType?->label() ?? ucfirst(str_replace('_', ' ', $groupType)),
                        'tags' => TagResource::collection($tags),
                    ];
                }

                return $groupedData;
            }

            return TagResource::collection($result);
        } catch (\Exception $e) {
            // Return empty collection if table doesn't exist yet
            return TagResource::collection(collect([]));
        }
    }

    /**
     * Display the specified tag.
     */
    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    /**
     * Get tags for a specific service type, grouped by tag type.
     *
     * This is a convenience endpoint for the frontend filter UI.
     */
    public function forServiceType(string $serviceType): array
    {
        $cacheKey = "tags_for_service_{$serviceType}";
        $cacheTtl = 60 * 30; // 30 minutes

        try {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($serviceType) {
                // Validate service type
                $validServiceType = ServiceType::tryFrom($serviceType);
                if (! $validServiceType) {
                    return [];
                }

                // Get applicable tag types for this service
                $applicableTagTypes = TagType::forServiceType($validServiceType);

                $result = [];
                foreach ($applicableTagTypes as $tagType) {
                    $tags = Tag::query()
                        ->active()
                        ->ofType($tagType)
                        ->forServiceType($serviceType)
                        ->ordered()
                        ->get();

                    if ($tags->isNotEmpty()) {
                        $result[] = [
                            'type' => $tagType->value,
                            'label' => $tagType->label(),
                            'tags' => TagResource::collection($tags),
                        ];
                    }
                }

                return $result;
            });
        } catch (\Exception $e) {
            return [];
        }
    }
}
