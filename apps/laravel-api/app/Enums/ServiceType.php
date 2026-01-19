<?php

namespace App\Enums;

enum ServiceType: string
{
    case TOUR = 'tour';
    case EVENT = 'event';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TOUR => 'Tour',
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
            self::EVENT => 'heroicon-o-calendar',
        };
    }
}
