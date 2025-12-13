<?php

namespace App\Enums;

enum AvailabilityRuleType: string
{
    case WEEKLY = 'weekly';
    case DAILY = 'daily';
    case SPECIFIC_DATES = 'specific_dates';
    case BLOCKED_DATES = 'blocked_dates';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'Weekly Recurring',
            self::DAILY => 'Daily',
            self::SPECIFIC_DATES => 'Specific Dates',
            self::BLOCKED_DATES => 'Blocked Dates',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WEEKLY => 'Repeats on specific days of the week',
            self::DAILY => 'Available every day within date range',
            self::SPECIFIC_DATES => 'Available only on specified dates',
            self::BLOCKED_DATES => 'Blocked dates - not available for booking',
        };
    }

    public function isRecurring(): bool
    {
        return in_array($this, [self::WEEKLY, self::DAILY]);
    }
}
