<?php

namespace App\Enums;

enum ServiceType: string
{
    case TOUR = 'tour';
    case EVENT = 'event';

    /**
     * Get human-readable label (translated)
     */
    public function label(): string
    {
        return __('enums.service_type.'.$this->value);
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
