<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::SUCCEEDED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'gray',
            self::PARTIALLY_REFUNDED => 'warning',
        };
    }
}
