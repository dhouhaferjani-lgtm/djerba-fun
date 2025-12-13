<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Public can view published listings
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Listing $listing): bool
    {
        // If published, anyone can view
        if ($listing->isPublished()) {
            return true;
        }

        // Otherwise, only vendor or admin can view
        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $listing->vendor_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isVendor() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Listing $listing): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Vendors can only update their own listings and if status allows editing
        return $listing->vendor_id === $user->id && $listing->status->canEdit();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Listing $listing): bool
    {
        return $user->isAdmin() || $listing->vendor_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Listing $listing): bool
    {
        return $user->isAdmin() || $listing->vendor_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Listing $listing): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can publish the listing.
     */
    public function publish(User $user, Listing $listing): bool
    {
        return $user->isAdmin();
    }
}
