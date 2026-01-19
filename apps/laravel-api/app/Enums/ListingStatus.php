<?php

namespace App\Enums;

enum ListingStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_REVIEW => 'Pending Review',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Check if listing is visible to public
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if listing can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'warning',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'info',
            self::REJECTED => 'danger',
        };
    }
}
