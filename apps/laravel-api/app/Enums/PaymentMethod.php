<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH_ON_ARRIVAL = 'cash_on_arrival';
    case FREE = 'free';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Credit/Debit Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH_ON_ARRIVAL => 'Cash on Arrival',
            self::FREE => 'Free',
        };
    }

    public function requiresOnlineProcessing(): bool
    {
        return match ($this) {
            self::CARD => true,
            self::BANK_TRANSFER, self::CASH_ON_ARRIVAL, self::FREE => false,
        };
    }
}
