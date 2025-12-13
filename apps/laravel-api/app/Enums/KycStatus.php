<?php

namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUBMITTED => 'Submitted',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
        };
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
