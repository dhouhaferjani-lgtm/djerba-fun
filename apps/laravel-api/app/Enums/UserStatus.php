<?php

namespace App\Enums;

enum UserStatus: string
{
    case PENDING = 'pending';
    case PENDING_VERIFICATION = 'pending_verification';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Check if user can access the platform
     */
    public function canAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PENDING_VERIFICATION => 'warning',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
            self::DELETED => 'gray',
        };
    }
}
