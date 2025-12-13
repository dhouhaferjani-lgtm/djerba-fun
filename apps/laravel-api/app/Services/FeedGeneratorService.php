<?php

namespace App\Services;

use App\Models\Listing;
use Illuminate\Support\Facades\Cache;

class FeedGeneratorService
{
    /**
     * Generate listings feed in JSON format.
     */
    public function generateListingsJsonFeed(): array
    {
        return Cache::remember('feed:listings:json', 300, function () {
            $listings = Listing::query()
                ->published()
                ->with(['location', 'media', 'vendor.vendorProfile'])
                ->orderBy('rating', 'desc')
                ->get();

            return [
                'generated_at' => now()->toIso8601String(),
                'count' => $listings->count(),
                'listings' => $listings->map(function ($listing) {
                    return [
                        'id' => $listing->id,
                        'slug' => $listing->slug,
                        'title' => $listing->title,
                        'description' => $listing->description,
                        'type' => $listing->service_type,
                        'location' => [
                            'name' => $listing->location?->name,
                            'city' => $listing->location?->city,
                            'region' => $listing->location?->region,
                            'country' => $listing->location?->country,
                            'lat' => $listing->location?->latitude,
                            'lng' => $listing->location?->longitude,
                        ],
                        'pricing' => [
                            'from' => $listing->pricing['base'] ?? null,
                            'currency' => $listing->pricing['currency'] ?? 'EUR',
                            'per_person' => $listing->pricing['per_person'] ?? false,
                        ],
                        'rating' => $listing->rating,
                        'reviews_count' => $listing->reviews_count ?? 0,
                        'difficulty' => $listing->difficulty,
                        'duration' => $listing->duration,
                        'languages' => $listing->languages ?? [],
                        'tags' => $listing->tags ?? [],
                        'images' => $listing->media->map(fn ($media) => [
                            'url' => $media->url,
                            'alt_text' => $media->alt_text,
                        ])->toArray(),
                        'vendor' => [
                            'name' => $listing->vendor?->vendorProfile?->business_name,
                            'rating' => $listing->vendor?->vendorProfile?->rating,
                        ],
                        'url' => config('app.frontend_url') . '/listings/' . $listing->slug,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Generate listings feed in CSV format.
     */
    public function generateListingsCsvFeed(): string
    {
        return Cache::remember('feed:listings:csv', 300, function () {
            $listings = Listing::query()
                ->published()
                ->with(['location', 'vendor.vendorProfile'])
                ->orderBy('rating', 'desc')
                ->get();

            $csv = "ID,Slug,Title,Type,City,Country,Price,Currency,Rating,Reviews,Difficulty,Duration,URL\n";

            foreach ($listings as $listing) {
                $row = [
                    $listing->id,
                    $listing->slug,
                    '"' . str_replace('"', '""', $listing->title) . '"',
                    $listing->service_type,
                    $listing->location?->city ?? '',
                    $listing->location?->country ?? '',
                    $listing->pricing['base'] ?? '',
                    $listing->pricing['currency'] ?? 'EUR',
                    $listing->rating ?? '',
                    $listing->reviews_count ?? 0,
                    $listing->difficulty ?? '',
                    $listing->duration ?? '',
                    config('app.frontend_url') . '/listings/' . $listing->slug,
                ];

                $csv .= implode(',', $row) . "\n";
            }

            return $csv;
        });
    }

    /**
     * Generate availability feed in JSON format.
     */
    public function generateAvailabilityJsonFeed(): array
    {
        return Cache::remember('feed:availability:json', 300, function () {
            $listings = Listing::query()
                ->published()
                ->with(['availabilitySlots' => function ($query) {
                    $query->where('start_time', '>=', now())
                        ->where('available_quantity', '>', 0)
                        ->orderBy('start_time')
                        ->limit(10);
                }])
                ->has('availabilitySlots')
                ->get();

            return [
                'generated_at' => now()->toIso8601String(),
                'count' => $listings->count(),
                'listings' => $listings->map(function ($listing) {
                    return [
                        'id' => $listing->id,
                        'slug' => $listing->slug,
                        'title' => $listing->title,
                        'type' => $listing->service_type,
                        'upcoming_slots' => $listing->availabilitySlots->map(function ($slot) {
                            return [
                                'id' => $slot->id,
                                'start_time' => $slot->start_time->toIso8601String(),
                                'end_time' => $slot->end_time->toIso8601String(),
                                'available' => $slot->available_quantity,
                                'total' => $slot->total_quantity,
                                'price' => $slot->price,
                            ];
                        })->toArray(),
                        'url' => config('app.frontend_url') . '/listings/' . $listing->slug,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Clear all feed caches.
     */
    public function clearFeedCaches(): void
    {
        Cache::forget('feed:listings:json');
        Cache::forget('feed:listings:csv');
        Cache::forget('feed:availability:json');
    }
}
