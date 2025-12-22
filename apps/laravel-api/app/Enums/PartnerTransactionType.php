<?php

namespace App\Enums;

enum PartnerTransactionType: string
{
    case CHARGE = 'charge';
    case PAYMENT = 'payment';
    case ADJUSTMENT = 'adjustment';
    case REFUND = 'refund';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::CHARGE => 'Charge',
            self::PAYMENT => 'Payment',
            self::ADJUSTMENT => 'Adjustment',
            self::REFUND => 'Refund',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::CHARGE => 'Booking created - partner owes platform',
            self::PAYMENT => 'Partner pays platform',
            self::ADJUSTMENT => 'Manual adjustment by admin',
            self::REFUND => 'Booking cancelled - refund to partner',
        };
    }

    /**
     * Check if transaction increases balance owed
     */
    public function increasesBalance(): bool
    {
        return $this === self::CHARGE;
    }

    /**
     * Check if transaction decreases balance owed
     */
    public function decreasesBalance(): bool
    {
        return in_array($this, [self::PAYMENT, self::REFUND]);
    }

    /**
     * Get sign multiplier for balance calculation
     * Positive for charges (increase balance owed)
     * Negative for payments/refunds (decrease balance owed)
     */
    public function signMultiplier(): int
    {
        return match ($this) {
            self::CHARGE => 1,
            self::PAYMENT, self::REFUND => -1,
            self::ADJUSTMENT => 0, // Adjustments can go either way
        };
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::CHARGE => 'danger',
            self::PAYMENT => 'success',
            self::ADJUSTMENT => 'info',
            self::REFUND => 'warning',
        };
    }

    /**
     * Get icon for UI
     */
    public function icon(): string
    {
        return match ($this) {
            self::CHARGE => 'heroicon-o-arrow-up-circle',
            self::PAYMENT => 'heroicon-o-arrow-down-circle',
            self::ADJUSTMENT => 'heroicon-o-adjustments-horizontal',
            self::REFUND => 'heroicon-o-arrow-path',
        };
    }

    /**
     * Check if transaction requires admin approval
     */
    public function requiresApproval(): bool
    {
        return $this === self::ADJUSTMENT;
    }
}
