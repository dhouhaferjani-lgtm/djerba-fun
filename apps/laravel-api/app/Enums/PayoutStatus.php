<?php

declare(strict_types=1);

namespace App\Enums;

enum PayoutStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Get the label for the status (translated).
     */
    public function label(): string
    {
        return __('enums.payout_status.'.$this->value);
    }

    /**
     * Get the color for the status (Filament).
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    /**
     * Check if the payout can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if the payout is final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED], true);
    }
}
