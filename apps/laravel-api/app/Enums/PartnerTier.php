<?php

namespace App\Enums;

enum PartnerTier: string
{
    case STANDARD = 'standard';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    /**
     * Get default rate limit for tier
     */
    public function defaultRateLimit(): int
    {
        return match ($this) {
            self::STANDARD => 60,     // 60 requests per minute
            self::PREMIUM => 300,     // 300 requests per minute
            self::ENTERPRISE => 1000, // 1000 requests per minute
        };
    }

    /**
     * Get commission rate for tier (as decimal)
     */
    public function commissionRate(): float
    {
        return match ($this) {
            self::STANDARD => 0.15,   // 15%
            self::PREMIUM => 0.12,    // 12%
            self::ENTERPRISE => 0.10, // 10%
        };
    }

    /**
     * Check if tier has priority support
     */
    public function hasPrioritySupport(): bool
    {
        return $this === self::PREMIUM || $this === self::ENTERPRISE;
    }

    /**
     * Check if tier has dedicated account manager
     */
    public function hasDedicatedManager(): bool
    {
        return $this === self::ENTERPRISE;
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::STANDARD => 'gray',
            self::PREMIUM => 'warning',
            self::ENTERPRISE => 'success',
        };
    }

    /**
     * Get icon for UI
     */
    public function icon(): string
    {
        return match ($this) {
            self::STANDARD => 'heroicon-o-star',
            self::PREMIUM => 'heroicon-o-sparkles',
            self::ENTERPRISE => 'heroicon-o-trophy',
        };
    }

    /**
     * Get features included in tier
     */
    public function features(): array
    {
        return match ($this) {
            self::STANDARD => [
                'Basic API access',
                'Standard rate limits',
                'Email support',
                '15% commission',
            ],
            self::PREMIUM => [
                'Full API access',
                'Increased rate limits',
                'Priority support',
                'Webhook notifications',
                'Custom reporting',
                '12% commission',
            ],
            self::ENTERPRISE => [
                'Unlimited API access',
                'Highest rate limits',
                '24/7 priority support',
                'Dedicated account manager',
                'Custom integrations',
                'Advanced analytics',
                'White-label options',
                '10% commission',
            ],
        };
    }
}
