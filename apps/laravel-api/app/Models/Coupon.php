<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'minimum_order',
        'maximum_discount',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_count',
        'is_active',
        'listing_ids',
        'user_ids',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'minimum_order' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'is_active' => 'boolean',
            'listing_ids' => 'array',
            'user_ids' => 'array',
        ];
    }

    /**
     * Check if the coupon is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($now->lt($this->valid_from) || $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon is valid for a specific listing.
     */
    public function isValidForListing(string $listingId): bool
    {
        if ($this->listing_ids === null) {
            return true; // Valid for all listings
        }

        return in_array($listingId, $this->listing_ids, true);
    }

    /**
     * Check if the coupon is valid for a specific user.
     */
    public function isValidForUser(string $userId): bool
    {
        if ($this->user_ids === null) {
            return true; // Valid for all users
        }

        return in_array($userId, $this->user_ids, true);
    }

    /**
     * Check if minimum order amount is met.
     */
    public function meetsMinimumOrder(float $amount): bool
    {
        if ($this->minimum_order === null) {
            return true;
        }

        return $amount >= $this->minimum_order;
    }

    /**
     * Calculate the discount amount for a given order amount.
     */
    public function calculateDiscount(float $amount): float
    {
        return $this->discount_type->calculateDiscount(
            $this->discount_value,
            $amount,
            $this->maximum_discount
        );
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope to filter active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    /**
     * Scope to find by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }
}
