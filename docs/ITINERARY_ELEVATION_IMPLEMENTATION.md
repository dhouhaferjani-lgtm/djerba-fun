# Itinerary & Elevation Profile Implementation Plan

> **Feature**: GPX-based itinerary and elevation profile system for tours
> **Priority**: High
> **Estimated Effort**: 3 phases

---

## Table of Contents

1. [Overview](#overview)
2. [User Stories](#user-stories)
3. [Phase 1: Database & Backend](#phase-1-database--backend)
4. [Phase 2: Filament Vendor UI](#phase-2-filament-vendor-ui)
5. [Phase 3: Frontend Integration](#phase-3-frontend-integration)
6. [Technical Specifications](#technical-specifications)
7. [Pin Types & Icons](#pin-types--icons)
8. [Testing Strategy](#testing-strategy)

---

## Overview

### Feature Summary

Vendors can enhance their tour listings with:

- **Interactive Map** showing the tour route with custom pin markers
- **Elevation Profile** chart showing altitude changes along the route
- **Itinerary Timeline** with detailed stop information, photos, and descriptions

### Data Flow

```
GPX File Upload
      │
      ▼
┌─────────────────┐
│  GPX Parser     │──► Extract track points (lat, lng, elevation)
│  Service        │──► Extract waypoints as itinerary stops
└─────────────────┘
      │
      ▼
┌─────────────────┐
│  Listing Model  │──► itinerary (JSON array of stops)
│                 │──► elevation_profile (JSON with points & stats)
└─────────────────┘
      │
      ▼
┌─────────────────┐
│  Filament UI    │──► Repeater for manual editing
│  (Vendor)       │──► Pin type selector
│                 │──► Photo upload per stop
└─────────────────┘
      │
      ▼
┌─────────────────┐
│  Frontend       │──► ListingMap component
│  (Traveler)     │──► ElevationProfile chart
│                 │──► ItineraryTimeline
└─────────────────┘
```

### Toggle System

| Toggle                   | Controls                                     |
| ------------------------ | -------------------------------------------- |
| `show_itinerary`         | Displays map with route + itinerary timeline |
| `show_elevation_profile` | Displays elevation chart                     |

Both can be enabled/disabled independently. GPX upload populates both but vendor can toggle display.

---

## User Stories

### Vendor Stories

1. **As a vendor**, I want to upload a GPX file so that my tour route and stops are automatically created
2. **As a vendor**, I want to manually add/edit itinerary stops if I don't have a GPX file
3. **As a vendor**, I want to add descriptions and photos to each stop
4. **As a vendor**, I want to choose a pin icon that represents each stop type (monument, forest, viewpoint, etc.)
5. **As a vendor**, I want to toggle map/elevation visibility independently
6. **As a vendor**, I want to reorder stops by drag-and-drop
7. **As a vendor**, I want to delete individual stops or clear all and re-upload

### Traveler Stories

1. **As a traveler**, I want to see the tour route on a map before booking
2. **As a traveler**, I want to see elevation changes to understand difficulty
3. **As a traveler**, I want to read about each stop and see photos
4. **As a traveler**, I want to click markers on the map to see stop details

---

## Phase 1: Database & Backend

### 1.1 Database Migration

Create migration to add elevation profile storage:

```php
// database/migrations/xxxx_add_elevation_profile_to_listings_table.php

Schema::table('listings', function (Blueprint $table) {
    // Elevation profile data (generated from GPX)
    $table->json('elevation_profile')->nullable()->after('has_elevation_profile');

    // Toggle flags
    $table->boolean('show_itinerary')->default(false)->after('elevation_profile');
    $table->boolean('show_elevation_profile')->default(false)->after('show_itinerary');

    // Original GPX file reference (for re-processing)
    $table->string('gpx_file_path')->nullable()->after('show_elevation_profile');
});
```

**Elevation Profile JSON Structure:**

```json
{
  "points": [
    { "distance": 0, "elevation": 245.5 },
    { "distance": 100, "elevation": 248.2 },
    { "distance": 200, "elevation": 252.1 }
  ],
  "totalDistance": 12500,
  "totalAscent": 485,
  "totalDescent": 320,
  "maxElevation": 892,
  "minElevation": 145
}
```

**Itinerary Stop JSON Structure (update existing):**

```json
{
  "id": "uuid",
  "order": 0,
  "title": { "en": "Start Point", "fr": "Point de départ" },
  "description": { "en": "Meet at...", "fr": "Rendez-vous à..." },
  "lat": 36.8065,
  "lng": 10.1815,
  "elevationMeters": 245,
  "durationMinutes": 15,
  "pinType": "start",
  "photos": [{ "id": "uuid", "url": "https://...", "alt": "Description" }]
}
```

### 1.2 GPX Parser Service

Create `app/Services/GpxParserService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class GpxParserService
{
    /**
     * Parse a GPX file and extract track points and waypoints.
     */
    public function parse(UploadedFile|string $gpxFile): array
    {
        $content = $gpxFile instanceof UploadedFile
            ? $gpxFile->get()
            : file_get_contents($gpxFile);

        $xml = simplexml_load_string($content);

        if (!$xml) {
            throw new \InvalidArgumentException('Invalid GPX file');
        }

        // Register namespaces
        $xml->registerXPathNamespace('gpx', 'http://www.topografix.com/GPX/1/1');

        return [
            'trackPoints' => $this->extractTrackPoints($xml),
            'waypoints' => $this->extractWaypoints($xml),
            'metadata' => $this->extractMetadata($xml),
        ];
    }

    /**
     * Extract track points for elevation profile.
     */
    private function extractTrackPoints(\SimpleXMLElement $xml): array
    {
        $points = [];
        $totalDistance = 0;
        $prevPoint = null;

        // Try both GPX 1.1 and 1.0 formats
        $trackPoints = $xml->xpath('//gpx:trkpt') ?: $xml->xpath('//trkpt');

        foreach ($trackPoints as $point) {
            $lat = (float) $point['lat'];
            $lng = (float) $point['lon'];
            $elevation = isset($point->ele) ? (float) $point->ele : null;

            if ($prevPoint) {
                $totalDistance += $this->calculateDistance(
                    $prevPoint['lat'], $prevPoint['lng'],
                    $lat, $lng
                );
            }

            $points[] = [
                'lat' => $lat,
                'lng' => $lng,
                'elevation' => $elevation,
                'distance' => $totalDistance,
            ];

            $prevPoint = ['lat' => $lat, 'lng' => $lng];
        }

        return $points;
    }

    /**
     * Extract waypoints as itinerary stops.
     */
    private function extractWaypoints(\SimpleXMLElement $xml): array
    {
        $waypoints = [];
        $order = 0;

        $wpts = $xml->xpath('//gpx:wpt') ?: $xml->xpath('//wpt');

        foreach ($wpts as $wpt) {
            $waypoints[] = [
                'id' => (string) Str::uuid(),
                'order' => $order++,
                'lat' => (float) $wpt['lat'],
                'lng' => (float) $wpt['lon'],
                'elevation' => isset($wpt->ele) ? (float) $wpt->ele : null,
                'name' => (string) ($wpt->name ?? 'Waypoint ' . $order),
                'description' => (string) ($wpt->desc ?? ''),
                'type' => $this->inferPinType((string) ($wpt->type ?? '')),
            ];
        }

        return $waypoints;
    }

    /**
     * Extract metadata from GPX.
     */
    private function extractMetadata(\SimpleXMLElement $xml): array
    {
        return [
            'name' => (string) ($xml->metadata->name ?? $xml->trk->name ?? ''),
            'description' => (string) ($xml->metadata->desc ?? $xml->trk->desc ?? ''),
            'author' => (string) ($xml->metadata->author->name ?? ''),
            'time' => (string) ($xml->metadata->time ?? ''),
        ];
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Infer pin type from GPX waypoint type or symbol.
     */
    private function inferPinType(string $type): string
    {
        $typeMap = [
            'summit' => 'viewpoint',
            'peak' => 'viewpoint',
            'parking' => 'parking',
            'restaurant' => 'restaurant',
            'water' => 'water',
            'campground' => 'camping',
            'lodge' => 'accommodation',
            'hotel' => 'accommodation',
            'museum' => 'monument',
            'historic' => 'monument',
            'forest' => 'forest',
            'beach' => 'beach',
            'photo' => 'photo_spot',
        ];

        $typeLower = strtolower($type);

        foreach ($typeMap as $key => $pinType) {
            if (str_contains($typeLower, $key)) {
                return $pinType;
            }
        }

        return 'waypoint';
    }

    /**
     * Generate elevation profile from track points.
     */
    public function generateElevationProfile(array $trackPoints): array
    {
        if (empty($trackPoints)) {
            return [];
        }

        $points = [];
        $totalAscent = 0;
        $totalDescent = 0;
        $elevations = [];
        $prevElevation = null;

        foreach ($trackPoints as $point) {
            if ($point['elevation'] === null) {
                continue;
            }

            $elevations[] = $point['elevation'];

            $points[] = [
                'distance' => round($point['distance'], 1),
                'elevation' => round($point['elevation'], 1),
            ];

            if ($prevElevation !== null) {
                $diff = $point['elevation'] - $prevElevation;
                if ($diff > 0) {
                    $totalAscent += $diff;
                } else {
                    $totalDescent += abs($diff);
                }
            }

            $prevElevation = $point['elevation'];
        }

        if (empty($elevations)) {
            return [];
        }

        return [
            'points' => $points,
            'totalDistance' => round(end($trackPoints)['distance'], 1),
            'totalAscent' => round($totalAscent, 1),
            'totalDescent' => round($totalDescent, 1),
            'maxElevation' => round(max($elevations), 1),
            'minElevation' => round(min($elevations), 1),
        ];
    }

    /**
     * Convert waypoints to itinerary stops format.
     */
    public function waypointsToItinerary(array $waypoints): array
    {
        return array_map(function ($wpt) {
            return [
                'id' => $wpt['id'],
                'order' => $wpt['order'],
                'title' => [
                    'en' => $wpt['name'],
                    'fr' => $wpt['name'], // Vendor will translate
                ],
                'description' => [
                    'en' => $wpt['description'],
                    'fr' => $wpt['description'],
                ],
                'lat' => $wpt['lat'],
                'lng' => $wpt['lng'],
                'elevationMeters' => $wpt['elevation'],
                'durationMinutes' => null,
                'pinType' => $wpt['type'],
                'photos' => [],
            ];
        }, $waypoints);
    }

    /**
     * If no waypoints, create stops from track points at intervals.
     */
    public function createStopsFromTrack(
        array $trackPoints,
        int $numberOfStops = 5
    ): array {
        if (count($trackPoints) < $numberOfStops) {
            return [];
        }

        $totalDistance = end($trackPoints)['distance'];
        $interval = $totalDistance / ($numberOfStops - 1);
        $stops = [];

        for ($i = 0; $i < $numberOfStops; $i++) {
            $targetDistance = $interval * $i;
            $closestPoint = $this->findClosestPoint($trackPoints, $targetDistance);

            $stops[] = [
                'id' => (string) Str::uuid(),
                'order' => $i,
                'title' => [
                    'en' => $i === 0 ? 'Start' : ($i === $numberOfStops - 1 ? 'End' : 'Stop ' . $i),
                    'fr' => $i === 0 ? 'Départ' : ($i === $numberOfStops - 1 ? 'Arrivée' : 'Arrêt ' . $i),
                ],
                'description' => ['en' => '', 'fr' => ''],
                'lat' => $closestPoint['lat'],
                'lng' => $closestPoint['lng'],
                'elevationMeters' => $closestPoint['elevation'],
                'durationMinutes' => null,
                'pinType' => $i === 0 ? 'start' : ($i === $numberOfStops - 1 ? 'end' : 'waypoint'),
                'photos' => [],
            ];
        }

        return $stops;
    }

    private function findClosestPoint(array $points, float $targetDistance): array
    {
        $closest = $points[0];
        $minDiff = PHP_FLOAT_MAX;

        foreach ($points as $point) {
            $diff = abs($point['distance'] - $targetDistance);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $point;
            }
        }

        return $closest;
    }
}
```

### 1.3 Update Listing Model

```php
// app/Models/Listing.php

// Add to $fillable
'elevation_profile',
'show_itinerary',
'show_elevation_profile',
'gpx_file_path',

// Add to casts()
'elevation_profile' => 'array',
'show_itinerary' => 'boolean',
'show_elevation_profile' => 'boolean',

// Add helper methods
public function hasItinerary(): bool
{
    return $this->show_itinerary && !empty($this->itinerary);
}

public function hasElevationProfile(): bool
{
    return $this->show_elevation_profile && !empty($this->elevation_profile);
}
```

### 1.4 GPX Upload Controller Action

```php
// app/Http/Controllers/Api/V1/ListingGpxController.php

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\GpxParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListingGpxController extends Controller
{
    public function __construct(
        private GpxParserService $gpxParser
    ) {}

    public function upload(Request $request, Listing $listing)
    {
        $this->authorize('update', $listing);

        $request->validate([
            'gpx_file' => 'required|file|mimes:gpx,xml|max:10240', // 10MB max
            'create_stops_from_track' => 'boolean',
            'number_of_stops' => 'integer|min:2|max:20',
        ]);

        $file = $request->file('gpx_file');

        // Parse GPX
        $parsed = $this->gpxParser->parse($file);

        // Generate elevation profile
        $elevationProfile = $this->gpxParser->generateElevationProfile(
            $parsed['trackPoints']
        );

        // Generate itinerary from waypoints or track
        $itinerary = !empty($parsed['waypoints'])
            ? $this->gpxParser->waypointsToItinerary($parsed['waypoints'])
            : ($request->boolean('create_stops_from_track')
                ? $this->gpxParser->createStopsFromTrack(
                    $parsed['trackPoints'],
                    $request->integer('number_of_stops', 5)
                )
                : []);

        // Store GPX file
        $path = $file->store("listings/{$listing->id}/gpx", 'private');

        // Update listing
        $listing->update([
            'gpx_file_path' => $path,
            'elevation_profile' => $elevationProfile,
            'itinerary' => $itinerary,
            'show_itinerary' => !empty($itinerary),
            'show_elevation_profile' => !empty($elevationProfile),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'itinerary_count' => count($itinerary),
                'elevation_points' => count($elevationProfile['points'] ?? []),
                'total_distance' => $elevationProfile['totalDistance'] ?? 0,
                'total_ascent' => $elevationProfile['totalAscent'] ?? 0,
            ],
        ]);
    }

    public function clearGpxData(Listing $listing)
    {
        $this->authorize('update', $listing);

        // Delete stored GPX file
        if ($listing->gpx_file_path) {
            Storage::disk('private')->delete($listing->gpx_file_path);
        }

        $listing->update([
            'gpx_file_path' => null,
            'elevation_profile' => null,
            'itinerary' => null,
            'show_itinerary' => false,
            'show_elevation_profile' => false,
        ]);

        return response()->json(['success' => true]);
    }
}
```

### 1.5 Update Listing API Resource

```php
// app/Http/Resources/ListingResource.php

// Add to toArray()
'showItinerary' => $this->show_itinerary,
'showElevationProfile' => $this->show_elevation_profile,
'itinerary' => $this->when($this->hasItinerary(), $this->itinerary),
'elevationProfile' => $this->when($this->hasElevationProfile(), $this->elevation_profile),
```

---

## Phase 2: Filament Vendor UI

### 2.1 Itinerary Section in ListingResource

Add to `app/Filament/Vendor/Resources/ListingResource.php`:

```php
// In the form() method, add new section after Tour Details

Forms\Components\Section::make('Route & Itinerary')
    ->description('Add route map, itinerary stops, and elevation profile')
    ->schema([
        // Toggle controls
        Forms\Components\Grid::make(2)
            ->schema([
                Forms\Components\Toggle::make('show_itinerary')
                    ->label('Show Route Map & Itinerary')
                    ->helperText('Display interactive map with stops')
                    ->live(),

                Forms\Components\Toggle::make('show_elevation_profile')
                    ->label('Show Elevation Profile')
                    ->helperText('Display elevation chart'),
            ]),

        // GPX Upload Section
        Forms\Components\Section::make('GPX Import')
            ->description('Upload a GPX file to automatically generate route and stops')
            ->schema([
                Forms\Components\FileUpload::make('gpx_file')
                    ->label('GPX File')
                    ->acceptedFileTypes(['.gpx', 'application/gpx+xml', 'text/xml'])
                    ->maxSize(10240) // 10MB
                    ->directory('gpx-uploads')
                    ->visibility('private')
                    ->helperText('Upload a GPX file from your GPS device or mapping app')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                        if ($state) {
                            // Trigger GPX parsing action
                            $livewire->dispatch('parse-gpx-file');
                        }
                    }),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('parseGpx')
                        ->label('Parse GPX & Generate Stops')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->action(function (Get $get, Set $set) {
                            $this->parseGpxFile($get, $set);
                        })
                        ->visible(fn (Get $get) => $get('gpx_file')),

                    Forms\Components\Actions\Action::make('clearGpxData')
                        ->label('Clear All Route Data')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Set $set) {
                            $set('itinerary', []);
                            $set('elevation_profile', null);
                            $set('gpx_file', null);
                        }),
                ]),
            ])
            ->collapsible()
            ->collapsed(fn (Get $get) => !empty($get('itinerary'))),

        // Itinerary Repeater
        Forms\Components\Repeater::make('itinerary')
            ->label('Itinerary Stops')
            ->schema([
                Forms\Components\Hidden::make('id')
                    ->default(fn () => (string) \Illuminate\Support\Str::uuid()),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('title.en')
                            ->label('Title (English)')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('title.fr')
                            ->label('Title (French)')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('pinType')
                            ->label('Pin Type')
                            ->options([
                                'start' => 'Start Point',
                                'end' => 'End Point',
                                'waypoint' => 'Waypoint',
                                'viewpoint' => 'Viewpoint',
                                'monument' => 'Monument/Historic',
                                'forest' => 'Forest/Nature',
                                'beach' => 'Beach',
                                'restaurant' => 'Restaurant',
                                'accommodation' => 'Accommodation',
                                'parking' => 'Parking',
                                'water' => 'Water Source',
                                'photo_spot' => 'Photo Spot',
                                'camping' => 'Camping',
                                'museum' => 'Museum',
                                'mosque' => 'Mosque',
                                'market' => 'Market/Souk',
                                'cafe' => 'Café',
                                'ruins' => 'Ruins',
                                'cave' => 'Cave',
                                'oasis' => 'Oasis',
                            ])
                            ->default('waypoint')
                            ->required(),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\Textarea::make('description.fr')
                            ->label('Description (French)')
                            ->rows(2)
                            ->maxLength(500),
                    ]),

                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.000001)
                            ->required(),

                        Forms\Components\TextInput::make('lng')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.000001)
                            ->required(),

                        Forms\Components\TextInput::make('elevationMeters')
                            ->label('Elevation (m)')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('durationMinutes')
                            ->label('Duration (min)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Time spent at this stop'),
                    ]),

                Forms\Components\FileUpload::make('photos')
                    ->label('Photos')
                    ->image()
                    ->multiple()
                    ->maxFiles(5)
                    ->maxSize(5120) // 5MB per image
                    ->directory('listing-stops')
                    ->reorderable()
                    ->imageEditor()
                    ->columnSpanFull(),
            ])
            ->orderColumn('order')
            ->reorderable()
            ->reorderableWithButtons()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string =>
                ($state['title']['en'] ?? null)
                    ? "#{$state['order']} - {$state['title']['en']}"
                    : null
            )
            ->addActionLabel('Add Stop')
            ->defaultItems(0)
            ->visible(fn (Get $get) => $get('show_itinerary'))
            ->columnSpanFull(),

        // Elevation Profile Preview (read-only display)
        Forms\Components\Placeholder::make('elevation_preview')
            ->label('Elevation Profile Data')
            ->content(function (Get $get) {
                $profile = $get('elevation_profile');
                if (empty($profile)) {
                    return 'No elevation data. Upload a GPX file to generate.';
                }

                return new \Illuminate\Support\HtmlString(sprintf(
                    '<div class="grid grid-cols-4 gap-4 text-sm">
                        <div><strong>Distance:</strong> %.1f km</div>
                        <div><strong>Ascent:</strong> %.0f m</div>
                        <div><strong>Descent:</strong> %.0f m</div>
                        <div><strong>Max/Min:</strong> %.0f / %.0f m</div>
                    </div>',
                    ($profile['totalDistance'] ?? 0) / 1000,
                    $profile['totalAscent'] ?? 0,
                    $profile['totalDescent'] ?? 0,
                    $profile['maxElevation'] ?? 0,
                    $profile['minElevation'] ?? 0
                ));
            })
            ->visible(fn (Get $get) => $get('show_elevation_profile')),
    ])
    ->visible(fn (Get $get): bool => $get('service_type') === ServiceType::TOUR->value)
    ->columns(1),
```

### 2.2 GPX Parsing Action Handler

Add to ListingResource or create a Livewire action:

```php
// In ListingResource class
protected function parseGpxFile(Get $get, Set $set): void
{
    $gpxPath = $get('gpx_file');

    if (!$gpxPath) {
        Notification::make()
            ->title('No GPX file uploaded')
            ->danger()
            ->send();
        return;
    }

    try {
        $parser = app(GpxParserService::class);
        $fullPath = Storage::disk('public')->path($gpxPath);

        $parsed = $parser->parse($fullPath);

        // Generate elevation profile
        $elevationProfile = $parser->generateElevationProfile($parsed['trackPoints']);

        // Generate itinerary
        $itinerary = !empty($parsed['waypoints'])
            ? $parser->waypointsToItinerary($parsed['waypoints'])
            : $parser->createStopsFromTrack($parsed['trackPoints'], 5);

        // Set values
        $set('elevation_profile', $elevationProfile);
        $set('itinerary', $itinerary);
        $set('show_itinerary', true);
        $set('show_elevation_profile', !empty($elevationProfile));

        Notification::make()
            ->title('GPX Parsed Successfully')
            ->body(sprintf(
                'Created %d stops, %d elevation points',
                count($itinerary),
                count($elevationProfile['points'] ?? [])
            ))
            ->success()
            ->send();

    } catch (\Exception $e) {
        Notification::make()
            ->title('GPX Parsing Failed')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}
```

### 2.3 Custom Map Picker Component (Optional Enhancement)

For better UX, create a custom Filament field for picking coordinates on a map:

```php
// app/Filament/Forms/Components/MapPicker.php

<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class MapPicker extends Field
{
    protected string $view = 'filament.forms.components.map-picker';

    protected float $defaultLat = 36.8065; // Tunisia center
    protected float $defaultLng = 10.1815;
    protected int $defaultZoom = 8;

    public function defaultLocation(float $lat, float $lng): static
    {
        $this->defaultLat = $lat;
        $this->defaultLng = $lng;
        return $this;
    }

    public function defaultZoom(int $zoom): static
    {
        $this->defaultZoom = $zoom;
        return $this;
    }

    // ... implement view with Leaflet map
}
```

---

## Phase 3: Frontend Integration

### 3.1 Update Listing Detail Page

Modify `apps/web/src/app/[locale]/listings/[slug]/page.tsx`:

```tsx
// Add imports
import dynamic from 'next/dynamic';
import ItineraryTimeline from '@/components/itinerary/ItineraryTimeline';

// Dynamic imports for map components (client-side only)
const ListingMap = dynamic(() => import('@/components/maps/ListingMap'), {
  ssr: false,
  loading: () => <MapSkeleton />,
});

const ElevationProfile = dynamic(() => import('@/components/itinerary/ElevationProfile'), {
  ssr: false,
});

// In the component, after description section:
{
  /* Route Map */
}
{
  listing.showItinerary && listing.itinerary && listing.itinerary.length > 0 && (
    <section className="space-y-4">
      <h2 className="text-2xl font-semibold text-neutral-900">{t('route_map')}</h2>
      <ListingMap
        center={[
          listing.meetingPoint?.coordinates?.lat ?? listing.itinerary[0].lat,
          listing.meetingPoint?.coordinates?.lng ?? listing.itinerary[0].lng,
        ]}
        title={title}
        imageUrl={mainImage?.url}
        itinerary={listing.itinerary}
        className="h-96 rounded-lg"
      />
    </section>
  );
}

{
  /* Elevation Profile */
}
{
  listing.showElevationProfile && listing.elevationProfile && (
    <section>
      <ElevationProfile profile={listing.elevationProfile} className="mt-8" />
    </section>
  );
}

{
  /* Itinerary Timeline */
}
{
  listing.showItinerary && listing.itinerary && listing.itinerary.length > 0 && (
    <section className="mt-8">
      <ItineraryTimeline stops={listing.itinerary} locale={locale} />
    </section>
  );
}
```

### 3.2 Update ListingMap Component for Pin Types

```tsx
// apps/web/src/components/maps/ListingMap.tsx

import { useMemo } from 'react';

// Pin icon mapping
const PIN_ICONS: Record<string, string> = {
  start: '/icons/pins/start.svg',
  end: '/icons/pins/end.svg',
  waypoint: '/icons/pins/waypoint.svg',
  viewpoint: '/icons/pins/viewpoint.svg',
  monument: '/icons/pins/monument.svg',
  forest: '/icons/pins/forest.svg',
  beach: '/icons/pins/beach.svg',
  restaurant: '/icons/pins/restaurant.svg',
  accommodation: '/icons/pins/accommodation.svg',
  parking: '/icons/pins/parking.svg',
  water: '/icons/pins/water.svg',
  photo_spot: '/icons/pins/photo.svg',
  camping: '/icons/pins/camping.svg',
  museum: '/icons/pins/museum.svg',
  mosque: '/icons/pins/mosque.svg',
  market: '/icons/pins/market.svg',
  cafe: '/icons/pins/cafe.svg',
  ruins: '/icons/pins/ruins.svg',
  cave: '/icons/pins/cave.svg',
  oasis: '/icons/pins/oasis.svg',
};

// Create custom icon
const createPinIcon = (pinType: string) => {
  const iconUrl = PIN_ICONS[pinType] || PIN_ICONS.waypoint;

  return L.icon({
    iconUrl,
    iconSize: [32, 40],
    iconAnchor: [16, 40],
    popupAnchor: [0, -40],
  });
};

// In component, update marker creation:
{
  itinerary.map((stop, index) => (
    <Marker key={stop.id} position={[stop.lat, stop.lng]} icon={createPinIcon(stop.pinType)}>
      <Popup>
        <div className="max-w-xs">
          <h4 className="font-semibold">{tr(stop.title)}</h4>
          {stop.description && <p className="text-sm text-gray-600 mt-1">{tr(stop.description)}</p>}
          {stop.photos?.[0] && (
            <img
              src={stop.photos[0].url}
              alt={stop.photos[0].alt}
              className="w-full h-24 object-cover rounded mt-2"
            />
          )}
        </div>
      </Popup>
    </Marker>
  ));
}
```

### 3.3 Create Pin SVG Icons

Create SVG pin icons in `apps/web/public/icons/pins/`:

```
pins/
├── start.svg        # Green flag
├── end.svg          # Checkered flag
├── waypoint.svg     # Standard pin
├── viewpoint.svg    # Eye/binoculars
├── monument.svg     # Column/building
├── forest.svg       # Tree
├── beach.svg        # Wave/umbrella
├── restaurant.svg   # Fork & knife
├── accommodation.svg # Bed
├── parking.svg      # P symbol
├── water.svg        # Water drop
├── photo.svg        # Camera
├── camping.svg      # Tent
├── museum.svg       # Classical building
├── mosque.svg       # Mosque dome
├── market.svg       # Shopping bag
├── cafe.svg         # Coffee cup
├── ruins.svg        # Broken column
├── cave.svg         # Cave entrance
└── oasis.svg        # Palm tree
```

**Standard Pin SVG Template:**

```svg
<svg width="32" height="40" viewBox="0 0 32 40" fill="none" xmlns="http://www.w3.org/2000/svg">
  <!-- Pin shape -->
  <path d="M16 0C7.16 0 0 7.16 0 16c0 12 16 24 16 24s16-12 16-24C32 7.16 24.84 0 16 0z" fill="#0D642E"/>
  <!-- Inner circle -->
  <circle cx="16" cy="14" r="10" fill="white"/>
  <!-- Icon placeholder - replace per type -->
  <g transform="translate(8, 6)">
    <!-- Icon SVG path here -->
  </g>
</svg>
```

### 3.4 Add i18n Translations

```json
// apps/web/messages/en.json
{
  "itinerary": {
    "title": "Itinerary",
    "elevation_profile": "Elevation Profile",
    "max_elevation": "Max Elevation",
    "min_elevation": "Min Elevation",
    "total_ascent": "Total Ascent",
    "total_descent": "Total Descent",
    "distance": "Distance",
    "from_previous": "from previous",
    "elevation": "Elevation",
    "stop_types": {
      "start": "Start Point",
      "end": "End Point",
      "waypoint": "Waypoint",
      "viewpoint": "Viewpoint",
      "monument": "Monument",
      "forest": "Forest",
      "beach": "Beach",
      "restaurant": "Restaurant",
      "accommodation": "Accommodation"
    }
  },
  "listing": {
    "route_map": "Route Map"
  }
}

// apps/web/messages/fr.json
{
  "itinerary": {
    "title": "Itinéraire",
    "elevation_profile": "Profil d'élévation",
    "max_elevation": "Altitude max",
    "min_elevation": "Altitude min",
    "total_ascent": "Dénivelé positif",
    "total_descent": "Dénivelé négatif",
    "distance": "Distance",
    "from_previous": "du précédent",
    "elevation": "Altitude",
    "stop_types": {
      "start": "Point de départ",
      "end": "Point d'arrivée",
      "waypoint": "Étape",
      "viewpoint": "Point de vue",
      "monument": "Monument",
      "forest": "Forêt",
      "beach": "Plage",
      "restaurant": "Restaurant",
      "accommodation": "Hébergement"
    }
  },
  "listing": {
    "route_map": "Carte du parcours"
  }
}
```

---

## Technical Specifications

### GPX File Format Support

| Element    | Supported | Notes                       |
| ---------- | --------- | --------------------------- |
| `<trk>`    | Yes       | Track with points           |
| `<trkseg>` | Yes       | Track segments              |
| `<trkpt>`  | Yes       | Track points with lat/lon   |
| `<ele>`    | Yes       | Elevation data              |
| `<wpt>`    | Yes       | Waypoints → Itinerary stops |
| `<rte>`    | Partial   | Routes (converted to track) |
| `<time>`   | Ignored   | Timestamps not used         |

### Performance Considerations

1. **Large GPX Files**:
   - Simplify track points (Douglas-Peucker algorithm) if > 1000 points
   - Store simplified version for elevation profile display

2. **Map Rendering**:
   - Lazy load map component
   - Use vector tiles for better performance
   - Cluster markers if > 20 stops

3. **Image Optimization**:
   - Resize stop photos to max 1200x800
   - Generate thumbnails (400x300) for timeline
   - Use WebP format

### Data Validation

```php
// Itinerary stop validation rules
[
    'itinerary' => 'nullable|array|max:50',
    'itinerary.*.id' => 'required|uuid',
    'itinerary.*.order' => 'required|integer|min:0',
    'itinerary.*.title.en' => 'required|string|max:100',
    'itinerary.*.title.fr' => 'required|string|max:100',
    'itinerary.*.lat' => 'required|numeric|between:-90,90',
    'itinerary.*.lng' => 'required|numeric|between:-180,180',
    'itinerary.*.pinType' => 'required|string|in:start,end,waypoint,...',
    'itinerary.*.photos' => 'nullable|array|max:5',
]
```

---

## Pin Types & Icons

### Standard Pin Set (Phase 1)

| Type       | Icon           | Color | Use Case       |
| ---------- | -------------- | ----- | -------------- |
| `start`    | Flag           | Green | Starting point |
| `end`      | Checkered flag | Red   | Ending point   |
| `waypoint` | Circle         | Blue  | Generic stop   |

### Extended Pin Set (Phase 2)

| Type            | Icon          | Category |
| --------------- | ------------- | -------- |
| `viewpoint`     | Binoculars    | Nature   |
| `monument`      | Column        | Culture  |
| `forest`        | Tree          | Nature   |
| `beach`         | Wave          | Nature   |
| `restaurant`    | Fork/Knife    | Services |
| `accommodation` | Bed           | Services |
| `parking`       | P             | Services |
| `water`         | Drop          | Amenity  |
| `photo_spot`    | Camera        | Activity |
| `camping`       | Tent          | Services |
| `museum`        | Building      | Culture  |
| `mosque`        | Dome          | Culture  |
| `market`        | Bag           | Culture  |
| `cafe`          | Cup           | Services |
| `ruins`         | Broken column | Culture  |
| `cave`          | Cave          | Nature   |
| `oasis`         | Palm          | Nature   |

---

## Testing Strategy

### Unit Tests

```php
// tests/Unit/Services/GpxParserServiceTest.php

public function test_parses_gpx_track_points(): void
{
    $parser = new GpxParserService();
    $result = $parser->parse(base_path('tests/fixtures/sample.gpx'));

    $this->assertArrayHasKey('trackPoints', $result);
    $this->assertNotEmpty($result['trackPoints']);
    $this->assertArrayHasKey('lat', $result['trackPoints'][0]);
    $this->assertArrayHasKey('elevation', $result['trackPoints'][0]);
}

public function test_generates_elevation_profile(): void
{
    $parser = new GpxParserService();
    $parsed = $parser->parse(base_path('tests/fixtures/sample.gpx'));
    $profile = $parser->generateElevationProfile($parsed['trackPoints']);

    $this->assertArrayHasKey('totalAscent', $profile);
    $this->assertArrayHasKey('maxElevation', $profile);
    $this->assertGreaterThan(0, $profile['totalDistance']);
}

public function test_extracts_waypoints_as_stops(): void
{
    $parser = new GpxParserService();
    $parsed = $parser->parse(base_path('tests/fixtures/with-waypoints.gpx'));
    $itinerary = $parser->waypointsToItinerary($parsed['waypoints']);

    $this->assertNotEmpty($itinerary);
    $this->assertArrayHasKey('title', $itinerary[0]);
    $this->assertArrayHasKey('pinType', $itinerary[0]);
}
```

### Feature Tests

```php
// tests/Feature/ListingItineraryTest.php

public function test_vendor_can_upload_gpx_file(): void
{
    $vendor = User::factory()->vendor()->create();
    $listing = Listing::factory()->tour()->for($vendor)->create();

    $response = $this->actingAs($vendor)
        ->postJson("/api/v1/listings/{$listing->id}/gpx", [
            'gpx_file' => UploadedFile::fake()->create('route.gpx', 100),
        ]);

    $response->assertOk();
    $this->assertNotNull($listing->fresh()->elevation_profile);
}

public function test_listing_api_returns_itinerary_when_enabled(): void
{
    $listing = Listing::factory()
        ->tour()
        ->withItinerary()
        ->create(['show_itinerary' => true]);

    $response = $this->getJson("/api/v1/listings/{$listing->slug}");

    $response->assertOk()
        ->assertJsonPath('data.showItinerary', true)
        ->assertJsonStructure(['data' => ['itinerary']]);
}
```

### E2E Tests

```typescript
// tests/e2e/listing-itinerary.spec.ts

test('displays itinerary map on listing page', async ({ page }) => {
  await page.goto('/en/listings/sahara-trek');

  // Wait for map to load
  await expect(page.locator('.leaflet-container')).toBeVisible();

  // Check markers are rendered
  await expect(page.locator('.leaflet-marker-icon')).toHaveCount(5);

  // Check elevation profile
  await expect(page.locator('[data-testid="elevation-profile"]')).toBeVisible();
});
```

---

## Implementation Checklist

### Phase 1: Backend

- [ ] Create migration for new fields
- [ ] Implement GpxParserService
- [ ] Add GPX upload endpoint
- [ ] Update Listing model
- [ ] Update ListingResource API output
- [ ] Write unit tests
- [ ] Add sample GPX fixtures

### Phase 2: Filament UI

- [ ] Add route/itinerary section to form
- [ ] Implement GPX upload field
- [ ] Create itinerary repeater
- [ ] Add pin type selector
- [ ] Add photo upload per stop
- [ ] Implement GPX parsing action
- [ ] Test vendor workflow

### Phase 3: Frontend

- [ ] Integrate ListingMap in detail page
- [ ] Integrate ElevationProfile component
- [ ] Integrate ItineraryTimeline
- [ ] Create pin SVG icons
- [ ] Update map markers with pin types
- [ ] Add i18n translations
- [ ] Test responsive design
- [ ] E2E tests

---

_Document created: 2024-12-14_
_Last updated: 2024-12-14_
