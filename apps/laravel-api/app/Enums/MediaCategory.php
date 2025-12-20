<?php

namespace App\Enums;

enum MediaCategory: string
{
    case HERO = 'hero';
    case GALLERY = 'gallery';
    case FEATURED = 'featured';
    case ITINERARY_STOP = 'itinerary_stop';

    public function label(): string
    {
        return match ($this) {
            self::HERO => 'Hero Image',
            self::GALLERY => 'Gallery',
            self::FEATURED => 'Featured',
            self::ITINERARY_STOP => 'Itinerary Stop',
        };
    }
}
