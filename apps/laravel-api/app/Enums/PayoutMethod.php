<?php

declare(strict_types=1);

namespace App\Enums;

enum PayoutMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case PAYPAL = 'paypal';

    /**
     * Get the label for the payout method.
     */
    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::PAYPAL => 'PayPal',
        };
    }
}
