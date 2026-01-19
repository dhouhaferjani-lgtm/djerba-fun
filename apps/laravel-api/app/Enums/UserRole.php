<?php

namespace App\Enums;

enum UserRole: string
{
    case TRAVELER = 'traveler';
    case VENDOR = 'vendor';
    case ADMIN = 'admin';
    case AGENT = 'agent';

    /**
     * Get human-readable label (translated)
     */
    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }

    /**
     * Check if role is vendor
     */
    public function isVendor(): bool
    {
        return $this === self::VENDOR;
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role is traveler
     */
    public function isTraveler(): bool
    {
        return $this === self::TRAVELER;
    }

    /**
     * Check if role is agent
     */
    public function isAgent(): bool
    {
        return $this === self::AGENT;
    }
}
