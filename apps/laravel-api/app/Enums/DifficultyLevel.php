<?php

namespace App\Enums;

enum DifficultyLevel: string
{
    case EASY = 'easy';
    case MODERATE = 'moderate';
    case CHALLENGING = 'challenging';
    case EXPERT = 'expert';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::EASY => 'Easy',
            self::MODERATE => 'Moderate',
            self::CHALLENGING => 'Challenging',
            self::EXPERT => 'Expert',
        };
    }

    /**
     * Get difficulty level as numeric value (1-4)
     */
    public function level(): int
    {
        return match ($this) {
            self::EASY => 1,
            self::MODERATE => 2,
            self::CHALLENGING => 3,
            self::EXPERT => 4,
        };
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::EASY => 'success',
            self::MODERATE => 'info',
            self::CHALLENGING => 'warning',
            self::EXPERT => 'danger',
        };
    }
}
