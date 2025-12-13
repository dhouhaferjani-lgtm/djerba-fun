<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payout;
use App\Models\User;

class PayoutPolicy
{
    /**
     * Determine if the user can view any payouts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isVendor() || $user->isAdmin();
    }

    /**
     * Determine if the user can view the payout.
     */
    public function view(User $user, Payout $payout): bool
    {
        return $user->isAdmin() || $payout->vendor_id === $user->id;
    }

    /**
     * Determine if the user can create payouts.
     */
    public function create(User $user): bool
    {
        return $user->isVendor();
    }

    /**
     * Determine if the user can update the payout.
     */
    public function update(User $user, Payout $payout): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete the payout.
     */
    public function delete(User $user, Payout $payout): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can process the payout.
     */
    public function process(User $user, Payout $payout): bool
    {
        return $user->isAdmin() && $payout->status->canBeProcessed();
    }
}
