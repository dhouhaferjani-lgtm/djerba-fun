<?php

namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label (translated)
     */
    public function label(): string
    {
        return __('enums.kyc_status.'.$this->value);
    }

    /**
     * Check if vendor is verified
     */
    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SUBMITTED => 'warning',
            self::VERIFIED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
