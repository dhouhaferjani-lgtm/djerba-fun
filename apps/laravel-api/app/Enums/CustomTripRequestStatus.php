<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomTripRequestStatus: string implements HasLabel, HasColor
{
    case PENDING = 'pending';
    case CONTACTED = 'contacted';
    case PROPOSAL = 'proposal';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONTACTED => 'Contacted',
            self::PROPOSAL => 'Proposal Sent',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONTACTED => 'info',
            self::PROPOSAL => 'primary',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'success',
        };
    }
}
