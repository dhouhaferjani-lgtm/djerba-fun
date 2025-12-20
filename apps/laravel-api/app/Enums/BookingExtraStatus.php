<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingExtraStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'warning',
        };
    }
}
