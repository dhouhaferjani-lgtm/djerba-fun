<?php

namespace App\Enums;

enum HoldStatus: string
{
    case ACTIVE = 'active';
    case CONVERTED = 'converted';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CONVERTED => 'Converted to Booking',
            self::EXPIRED => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'warning',
            self::CONVERTED => 'success',
            self::EXPIRED => 'gray',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
