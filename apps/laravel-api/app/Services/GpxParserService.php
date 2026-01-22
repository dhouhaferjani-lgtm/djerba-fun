<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for parsing GPX (GPS Exchange Format) files.
 * Used to extract elevation profiles, waypoints, and track data
 * for tour listings.
 */
class GpxParserService
{
    /**
     * Parse a GPX file and extract track points and waypoints.
     *
     * @param  string  $filePath  Full path to the GPX file
     * @return array{trackPoints: array, waypoints: array, metadata: array}
     *
     * @throws \Exception If file cannot be parsed
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \Exception("GPX file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \Exception("Could not read GPX file: {$filePath}");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMessage = ! empty($errors) ? $errors[0]->message : 'Unknown XML error';
            throw new \Exception("Invalid GPX file: {$errorMessage}");
        }

        // Register namespaces commonly used in GPX files
        $namespaces = $xml->getNamespaces(true);
        $gpxNamespace = $namespaces[''] ?? null;

        $trackPoints = [];
        $waypoints = [];
        $metadata = [];

        // Parse metadata if present
        if (isset($xml->metadata)) {
            $metadata = [
                'name' => (string) ($xml->metadata->name ?? ''),
                'description' => (string) ($xml->metadata->desc ?? ''),
                'author' => (string) ($xml->metadata->author->name ?? ''),
            ];
        }

        // Parse waypoints
        foreach ($xml->wpt as $wpt) {
            $waypoints[] = [
                'lat' => (float) $wpt['lat'],
                'lng' => (float) $wpt['lon'],
                'elevation' => isset($wpt->ele) ? (float) $wpt->ele : null,
                'name' => (string) ($wpt->name ?? ''),
                'description' => (string) ($wpt->desc ?? ''),
                'time' => isset($wpt->time) ? (string) $wpt->time : null,
            ];
        }

        // Parse tracks
        foreach ($xml->trk as $track) {
            foreach ($track->trkseg as $segment) {
                foreach ($segment->trkpt as $point) {
                    $trackPoints[] = [
                        'lat' => (float) $point['lat'],
                        'lng' => (float) $point['lon'],
                        'elevation' => isset($point->ele) ? (float) $point->ele : null,
                        'time' => isset($point->time) ? (string) $point->time : null,
                    ];
                }
            }
        }

        // Parse routes as fallback if no tracks
        if (empty($trackPoints)) {
            foreach ($xml->rte as $route) {
                foreach ($route->rtept as $point) {
                    $trackPoints[] = [
                        'lat' => (float) $point['lat'],
                        'lng' => (float) $point['lon'],
                        'elevation' => isset($point->ele) ? (float) $point->ele : null,
                        'name' => (string) ($point->name ?? ''),
                    ];
                }
            }
        }

        return [
            'trackPoints' => $trackPoints,
            'waypoints' => $waypoints,
            'metadata' => $metadata,
        ];
    }

    /**
     * Generate an elevation profile from track points.
     *
     * @param  array  $trackPoints  Array of track points with elevation data
     * @return array Elevation profile data
     */
    public function generateElevationProfile(array $trackPoints): array
    {
        if (empty($trackPoints)) {
            return [];
        }

        $profile = [];
        $totalDistance = 0;
        $previousPoint = null;
        $minElevation = PHP_FLOAT_MAX;
        $maxElevation = PHP_FLOAT_MIN;
        $totalAscent = 0;
        $totalDescent = 0;

        foreach ($trackPoints as $index => $point) {
            if (! isset($point['elevation']) || $point['elevation'] === null) {
                continue;
            }

            $elevation = (float) $point['elevation'];
            $minElevation = min($minElevation, $elevation);
            $maxElevation = max($maxElevation, $elevation);

            if ($previousPoint !== null) {
                // Calculate distance using Haversine formula
                $distance = $this->calculateDistance(
                    $previousPoint['lat'],
                    $previousPoint['lng'],
                    $point['lat'],
                    $point['lng']
                );
                $totalDistance += $distance;

                // Calculate elevation change
                $elevationChange = $elevation - ($previousPoint['elevation'] ?? $elevation);
                if ($elevationChange > 0) {
                    $totalAscent += $elevationChange;
                } else {
                    $totalDescent += abs($elevationChange);
                }
            }

            $profile[] = [
                'distance' => round($totalDistance, 2),
                'elevation' => round($elevation, 1),
                'lat' => $point['lat'],
                'lng' => $point['lng'],
            ];

            $previousPoint = $point;
        }

        if (empty($profile)) {
            return [];
        }

        return [
            'points' => $profile,
            'stats' => [
                'minElevation' => round($minElevation, 1),
                'maxElevation' => round($maxElevation, 1),
                'totalAscent' => round($totalAscent, 1),
                'totalDescent' => round($totalDescent, 1),
                'totalDistance' => round($totalDistance, 2),
            ],
        ];
    }

    /**
     * Convert GPX waypoints to itinerary format.
     *
     * @param  array  $waypoints  Array of waypoints
     * @return array Itinerary data
     */
    public function waypointsToItinerary(array $waypoints): array
    {
        $itinerary = [];
        $totalWaypoints = count($waypoints);

        foreach ($waypoints as $index => $waypoint) {
            // Determine pin type based on position
            $pinType = 'waypoint';
            if ($index === 0) {
                $pinType = 'start';
            } elseif ($index === $totalWaypoints - 1) {
                $pinType = 'end';
            }

            $itinerary[] = [
                'id' => (string) Str::uuid(),
                'order' => $index,  // 0-based indexing
                'title' => [
                    'en' => $waypoint['name'] ?: 'Stop ' . ($index + 1),
                    'fr' => $waypoint['name'] ?: 'Arrêt ' . ($index + 1),
                ],
                'description' => [
                    'en' => $waypoint['description'] ?? '',
                    'fr' => '',
                ],
                'lat' => $waypoint['lat'],
                'lng' => $waypoint['lng'],
                'elevationMeters' => $waypoint['elevation'],
                'durationMinutes' => 15, // Default stop duration
                'pinType' => $pinType,
            ];
        }

        return $itinerary;
    }

    /**
     * Create stops from track points by sampling at regular intervals.
     *
     * @param  array  $trackPoints  Array of track points
     * @param  int  $numberOfStops  Number of stops to create
     * @return array Itinerary data
     */
    public function createStopsFromTrack(array $trackPoints, int $numberOfStops = 5): array
    {
        if (empty($trackPoints) || $numberOfStops < 2) {
            return [];
        }

        $totalPoints = count($trackPoints);
        $interval = max(1, (int) floor($totalPoints / ($numberOfStops - 1)));
        $itinerary = [];
        $stopNumber = 1;

        for ($i = 0; $i < $totalPoints && $stopNumber <= $numberOfStops; $i += $interval) {
            $point = $trackPoints[$i];

            // Always include the last point
            if ($stopNumber === $numberOfStops && $i < $totalPoints - 1) {
                $point = $trackPoints[$totalPoints - 1];
            }

            // Determine pin type based on position
            $pinType = 'waypoint';
            if ($stopNumber === 1) {
                $pinType = 'start';
            } elseif ($stopNumber === $numberOfStops) {
                $pinType = 'end';
            }

            $itinerary[] = [
                'id' => (string) Str::uuid(),
                'order' => $stopNumber - 1,  // 0-based indexing
                'title' => [
                    'en' => $stopNumber === 1 ? 'Start Point' : ($stopNumber === $numberOfStops ? 'End Point' : 'Checkpoint ' . $stopNumber),
                    'fr' => $stopNumber === 1 ? 'Point de départ' : ($stopNumber === $numberOfStops ? 'Point d\'arrivée' : 'Point de contrôle ' . $stopNumber),
                ],
                'description' => [
                    'en' => '',
                    'fr' => '',
                ],
                'lat' => $point['lat'],
                'lng' => $point['lng'],
                'elevationMeters' => $point['elevation'] ?? null,
                'durationMinutes' => $stopNumber === 1 || $stopNumber === $numberOfStops ? 0 : 10,
                'pinType' => $pinType,
            ];

            $stopNumber++;
        }

        return $itinerary;
    }

    /**
     * Calculate distance between two points using Haversine formula.
     *
     * @param  float  $lat1  Latitude of point 1
     * @param  float  $lng1  Longitude of point 1
     * @param  float  $lat2  Latitude of point 2
     * @param  float  $lng2  Longitude of point 2
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
