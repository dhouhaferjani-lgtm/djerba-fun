<?php

namespace App\Models;

use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'company_type',
        'tax_id',
        'kyc_status',
        'commission_tier',
        'payout_account_id',
        'description',
        'website_url',
        'phone',
        'address',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address' => 'array',
            'verified_at' => 'datetime',
            'kyc_status' => KycStatus::class,
        ];
    }

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if vendor is verified
     */
    public function isVerified(): bool
    {
        return $this->kyc_status->isVerified();
    }

    /**
     * Mark vendor as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'kyc_status' => KycStatus::VERIFIED,
            'verified_at' => now(),
        ]);
    }
}
