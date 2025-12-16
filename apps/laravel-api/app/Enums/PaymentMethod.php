<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH_ON_ARRIVAL = 'cash_on_arrival';
    case FREE = 'free';
    case MOCK = 'mock';  // For development/testing
    case OFFLINE = 'offline';  // Cash or bank transfer without online processing
    case CLICK_TO_PAY = 'click_to_pay';  // Visa Click to Pay

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Credit/Debit Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH_ON_ARRIVAL => 'Cash on Arrival',
            self::FREE => 'Free',
            self::MOCK => 'Mock Payment (Testing)',
            self::OFFLINE => 'Offline Payment',
            self::CLICK_TO_PAY => 'Click to Pay',
        };
    }

    public function requiresOnlineProcessing(): bool
    {
        return match ($this) {
            self::CARD, self::CLICK_TO_PAY => true,
            self::BANK_TRANSFER, self::CASH_ON_ARRIVAL, self::FREE, self::MOCK, self::OFFLINE => false,
        };
    }
}
