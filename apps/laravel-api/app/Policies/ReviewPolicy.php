<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine if the user can view any reviews.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the review.
     */
    public function view(User $user, Review $review): bool
    {
        return $review->is_published
            || $user->isAdmin()
            || $review->user_id === $user->id
            || ($user->isVendor() && $review->listing?->vendor_id === $user->id);
    }

    /**
     * Determine if the user can create reviews.
     */
    public function create(User $user): bool
    {
        return $user->isTraveler();
    }

    /**
     * Determine if the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can publish (approve) the review.
     */
    public function publish(User $user, Review $review): bool
    {
        return $user->isAdmin() || ($user->isVendor() && $review->listing?->vendor_id === $user->id);
    }

    /**
     * Determine if the user can reject the review.
     */
    public function reject(User $user, Review $review): bool
    {
        return $user->isAdmin() || ($user->isVendor() && $review->listing?->vendor_id === $user->id);
    }

    /**
     * Determine if the user can reply to the review.
     */
    public function reply(User $user, Review $review): bool
    {
        // Vendor can reply to reviews of their own listings
        return $user->isVendor() && $review->listing?->vendor_id === $user->id;
    }
}
