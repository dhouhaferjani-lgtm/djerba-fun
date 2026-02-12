<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteProxyController extends Controller
{
    /**
     * Proxy route requests to OSRM and cache results.
     *
     * GET /api/v1/route?waypoints=lat1,lng1;lat2,lng2;...
     */
    private const ALLOWED_PROFILES = ['driving', 'foot', 'cycling'];

    public function __invoke(Request $request): JsonResponse
    {
        $waypoints = $request->query('waypoints');

        if (! $waypoints || ! is_string($waypoints)) {
            return response()->json(['coordinates' => null], 400);
        }

        // Validate profile parameter (default to foot — most tours are walking/hiking)
        $profile = $request->query('profile', 'foot');
        if (! in_array($profile, self::ALLOWED_PROFILES, true)) {
            $profile = 'foot';
        }

        // Parse and validate waypoints
        $pairs = explode(';', $waypoints);
        if (count($pairs) < 2) {
            return response()->json(['coordinates' => null], 400);
        }

        $coords = [];
        foreach ($pairs as $pair) {
            $parts = explode(',', $pair);
            if (count($parts) !== 2) {
                return response()->json(['coordinates' => null], 400);
            }
            $lat = (float) $parts[0];
            $lng = (float) $parts[1];
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return response()->json(['coordinates' => null], 400);
            }
            // OSRM expects lng,lat
            $coords[] = "{$lng},{$lat}";
        }

        $cacheKey = "route:{$profile}:" . md5($waypoints);

        // Only cache successful results — never cache null (transient failures)
        $coordinates = Cache::get($cacheKey);

        if ($coordinates === null) {
            try {
                $osrmCoords = implode(';', $coords);
                $osrmProfile = $profile === 'cycling' ? 'bike' : $profile;
                $url = "https://router.project-osrm.org/route/v1/{$osrmProfile}/{$osrmCoords}?overview=full&geometries=geojson";

                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if (($data['code'] ?? '') === 'Ok' && ! empty($data['routes'][0]['geometry']['coordinates'])) {
                        // OSRM returns [lng, lat] — flip to [lat, lng]
                        $coordinates = array_map(
                            fn (array $coord) => [$coord[1], $coord[0]],
                            $data['routes'][0]['geometry']['coordinates']
                        );

                        Cache::put($cacheKey, $coordinates, 86400);
                    }
                }
            } catch (\Throwable) {
                // Don't cache failures — let next request retry
            }
        }

        return response()->json(['coordinates' => $coordinates]);
    }
}
