<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case REFUND_REQUESTED = 'refund_requested';
    case REFUNDED = 'refunded';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'Pending Payment',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::REFUND_REQUESTED => 'Refund Requested',
            self::REFUNDED => 'Refunded',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::REFUND_REQUESTED => 'warning',
            self::REFUNDED => 'gray',
            self::COMPLETED => 'success',
            self::NO_SHOW => 'danger',
        };
    }
}
