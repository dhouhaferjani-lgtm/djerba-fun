<?php

namespace App\Enums;

enum SlotStatus: string
{
    case AVAILABLE = 'available';
    case LIMITED = 'limited';
    case SOLD_OUT = 'sold_out';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::LIMITED => 'Limited Availability',
            self::SOLD_OUT => 'Sold Out',
            self::BLOCKED => 'Blocked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::LIMITED => 'warning',
            self::SOLD_OUT => 'danger',
            self::BLOCKED => 'gray',
        };
    }

    public function isBookable(): bool
    {
        return in_array($this, [self::AVAILABLE, self::LIMITED]);
    }
}
