<?php

declare(strict_types=1);

namespace App\Enums;

enum ExtraPricingType: string
{
    case PER_PERSON = 'per_person';
    case PER_BOOKING = 'per_booking';
    case PER_UNIT = 'per_unit';
    case PER_PERSON_TYPE = 'per_person_type';

    public function label(): string
    {
        return match ($this) {
            self::PER_PERSON => 'Per Person',
            self::PER_BOOKING => 'Per Booking (Flat)',
            self::PER_UNIT => 'Per Unit',
            self::PER_PERSON_TYPE => 'Per Person Type',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PER_PERSON => 'Price multiplied by total number of guests',
            self::PER_BOOKING => 'Single flat price for the entire booking',
            self::PER_UNIT => 'Customer selects quantity independently',
            self::PER_PERSON_TYPE => 'Different prices for adults, children, infants',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PER_PERSON => 'heroicon-o-users',
            self::PER_BOOKING => 'heroicon-o-ticket',
            self::PER_UNIT => 'heroicon-o-cube',
            self::PER_PERSON_TYPE => 'heroicon-o-user-group',
        };
    }
}
