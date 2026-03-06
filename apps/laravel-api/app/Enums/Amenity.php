<?php

namespace App\Enums;

enum Amenity: string
{
    // Essentials
    case WIFI = 'wifi';
    case AIR_CONDITIONING = 'air_conditioning';
    case HEATING = 'heating';
    case KITCHEN = 'kitchen';
    case WASHER = 'washer';
    case DRYER = 'dryer';

    // Outdoor
    case POOL = 'pool';
    case HOT_TUB = 'hot_tub';
    case PARKING = 'parking';
    case GARDEN = 'garden';
    case TERRACE = 'terrace';
    case BBQ = 'bbq';

    // Entertainment
    case TV = 'tv';
    case WORKSPACE = 'workspace';
    case SAFE = 'safe';
    case IRON = 'iron';
    case HAIR_DRYER = 'hair_dryer';

    // Views & Location
    case BEACH_ACCESS = 'beach_access';
    case SEA_VIEW = 'sea_view';
    case MOUNTAIN_VIEW = 'mountain_view';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::WIFI => 'WiFi',
            self::AIR_CONDITIONING => 'Air Conditioning',
            self::HEATING => 'Heating',
            self::KITCHEN => 'Kitchen',
            self::WASHER => 'Washer',
            self::DRYER => 'Dryer',
            self::POOL => 'Pool',
            self::HOT_TUB => 'Hot Tub',
            self::PARKING => 'Parking',
            self::GARDEN => 'Garden',
            self::TERRACE => 'Terrace',
            self::BBQ => 'BBQ / Grill',
            self::TV => 'TV',
            self::WORKSPACE => 'Workspace',
            self::SAFE => 'Safe',
            self::IRON => 'Iron',
            self::HAIR_DRYER => 'Hair Dryer',
            self::BEACH_ACCESS => 'Beach Access',
            self::SEA_VIEW => 'Sea View',
            self::MOUNTAIN_VIEW => 'Mountain View',
        };
    }

    /**
     * Get the icon name (Heroicon).
     */
    public function icon(): string
    {
        return match ($this) {
            self::WIFI => 'heroicon-o-wifi',
            self::AIR_CONDITIONING => 'heroicon-o-sun',
            self::HEATING => 'heroicon-o-fire',
            self::KITCHEN => 'heroicon-o-cake',
            self::WASHER => 'heroicon-o-beaker',
            self::DRYER => 'heroicon-o-arrow-path',
            self::POOL => 'heroicon-o-sparkles',
            self::HOT_TUB => 'heroicon-o-fire',
            self::PARKING => 'heroicon-o-truck',
            self::GARDEN => 'heroicon-o-sun',
            self::TERRACE => 'heroicon-o-home-modern',
            self::BBQ => 'heroicon-o-fire',
            self::TV => 'heroicon-o-tv',
            self::WORKSPACE => 'heroicon-o-computer-desktop',
            self::SAFE => 'heroicon-o-lock-closed',
            self::IRON => 'heroicon-o-wrench',
            self::HAIR_DRYER => 'heroicon-o-bolt',
            self::BEACH_ACCESS => 'heroicon-o-sun',
            self::SEA_VIEW => 'heroicon-o-eye',
            self::MOUNTAIN_VIEW => 'heroicon-o-map',
        };
    }

    /**
     * Get category for grouping.
     */
    public function category(): string
    {
        return match ($this) {
            self::WIFI, self::AIR_CONDITIONING, self::HEATING, self::KITCHEN, self::WASHER, self::DRYER => 'essentials',
            self::POOL, self::HOT_TUB, self::PARKING, self::GARDEN, self::TERRACE, self::BBQ => 'outdoor',
            self::TV, self::WORKSPACE, self::SAFE, self::IRON, self::HAIR_DRYER => 'entertainment',
            self::BEACH_ACCESS, self::SEA_VIEW, self::MOUNTAIN_VIEW => 'views',
        };
    }

    /**
     * Get all amenities as options for Filament.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $amenity) => [$amenity->value => $amenity->label()])
            ->toArray();
    }

    /**
     * Get amenities grouped by category.
     */
    public static function grouped(): array
    {
        return collect(self::cases())
            ->groupBy(fn (self $amenity) => $amenity->category())
            ->map(fn ($group) => $group->mapWithKeys(fn (self $amenity) => [$amenity->value => $amenity->label()]))
            ->toArray();
    }
}
