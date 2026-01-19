<?php

declare(strict_types=1);

namespace App\Enums;

enum ExtraCategory: string
{
    case EQUIPMENT = 'equipment';
    case MEAL = 'meal';
    case INSURANCE = 'insurance';
    case UPGRADE = 'upgrade';
    case MERCHANDISE = 'merchandise';
    case TRANSPORT = 'transport';
    case ACCESSIBILITY = 'accessibility';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'Equipment',
            self::MEAL => 'Meals & Refreshments',
            self::INSURANCE => 'Insurance & Protection',
            self::UPGRADE => 'Upgrades',
            self::MERCHANDISE => 'Merchandise',
            self::TRANSPORT => 'Transportation',
            self::ACCESSIBILITY => 'Accessibility',
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'heroicon-o-wrench-screwdriver',
            self::MEAL => 'heroicon-o-cake',
            self::INSURANCE => 'heroicon-o-shield-check',
            self::UPGRADE => 'heroicon-o-arrow-trending-up',
            self::MERCHANDISE => 'heroicon-o-shopping-bag',
            self::TRANSPORT => 'heroicon-o-truck',
            self::ACCESSIBILITY => 'heroicon-o-hand-raised',
            self::OTHER => 'heroicon-o-squares-plus',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'blue',
            self::MEAL => 'orange',
            self::INSURANCE => 'green',
            self::UPGRADE => 'purple',
            self::MERCHANDISE => 'pink',
            self::TRANSPORT => 'cyan',
            self::ACCESSIBILITY => 'yellow',
            self::OTHER => 'gray',
        };
    }
}
