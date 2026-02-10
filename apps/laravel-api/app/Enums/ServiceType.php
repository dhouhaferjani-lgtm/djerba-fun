<?php

namespace App\Enums;

enum ServiceType: string
{
    case TOUR = 'tour';
    case EVENT = 'event';
    case SEJOUR = 'sejour';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TOUR => 'Tour',
            self::EVENT => 'Event',
            self::SEJOUR => 'Séjour',
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
            self::SEJOUR => 'heroicon-o-sun',
        };
    }
}
