<?php

namespace App\Enums;

enum ServiceType: string
{
    case TOUR = 'tour';
    case NAUTICAL = 'nautical';
    case ACCOMMODATION = 'accommodation';
    case EVENT = 'event';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TOUR => 'Tour',
            self::NAUTICAL => 'Nautical',
            self::ACCOMMODATION => 'Accommodation',
            self::EVENT => 'Event',
        };
    }

    /**
     * Get icon for UI
     */
    public function icon(): string
    {
        return match ($this) {
            self::TOUR => 'heroicon-o-map',
            self::NAUTICAL => 'heroicon-o-lifebuoy',
            self::ACCOMMODATION => 'heroicon-o-home',
            self::EVENT => 'heroicon-o-calendar',
        };
    }

    /**
     * Check if this service type is "tour-like" (uses duration, itinerary, difficulty fields).
     * Events have different fields (venue, agenda, etc.)
     */
    public function isTourLike(): bool
    {
        return match ($this) {
            self::TOUR, self::NAUTICAL, self::ACCOMMODATION => true,
            self::EVENT => false,
        };
    }
}
