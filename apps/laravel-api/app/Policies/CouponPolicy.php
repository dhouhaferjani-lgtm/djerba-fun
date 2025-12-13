<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    /**
     * Determine if the user can view any coupons.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the coupon.
     */
    public function view(User $user, Coupon $coupon): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can create coupons.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the coupon.
     */
    public function update(User $user, Coupon $coupon): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete the coupon.
     */
    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->isAdmin();
    }
}
