<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryChangeType: string
{
    case RESERVED = 'reserved';
    case RELEASED = 'released';
    case ADJUSTMENT = 'adjustment';
    case RESTOCK = 'restock';

    public function label(): string
    {
        return match ($this) {
            self::RESERVED => 'Reserved',
            self::RELEASED => 'Released',
            self::ADJUSTMENT => 'Manual Adjustment',
            self::RESTOCK => 'Restocked',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RESERVED => 'heroicon-o-minus-circle',
            self::RELEASED => 'heroicon-o-plus-circle',
            self::ADJUSTMENT => 'heroicon-o-pencil-square',
            self::RESTOCK => 'heroicon-o-arrow-path',
        };
    }
}
