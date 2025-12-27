<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Partner extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'api_key',
        'api_secret',
        'permissions',
        'rate_limit',
        'is_active',
        'last_used_at',
        'metadata',
        'user_id',
        'company_name',
        'company_type',
        'description',
        'website_url',
        'contact_email',
        'contact_phone',
        'ip_whitelist',
        'webhook_url',
        'webhook_secret',
        'kyc_status',
        'partner_tier',
        'sandbox_mode',
        'api_key_expires_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'metadata' => 'array',
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'sandbox_mode' => 'boolean',
        'last_used_at' => 'datetime',
        'api_key_expires_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $hidden = [
        'api_secret',
        'webhook_secret',
    ];

    /**
     * Get the user associated with this partner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved this partner.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the audit logs for the partner.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(PartnerAuditLog::class);
    }

    /**
     * Get all transactions for this partner.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PartnerTransaction::class);
    }

    /**
     * Get all API keys for this partner.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(PartnerApiKey::class);
    }

    /**
     * Get all webhooks for this partner.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(PartnerWebhook::class);
    }

    /**
     * Get all bookings created by this partner.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Generate a new API key and secret.
     */
    public static function generateCredentials(): array
    {
        $apiKey = 'pk_' . Str::random(32); // Changed prefix from 'ak_' to 'pk_' for Partner Key
        $apiSecret = 'ps_' . Str::random(48); // Changed prefix from 'as_' to 'ps_' for Partner Secret

        return [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'api_key_hashed' => hash('sha256', $apiKey),
            'api_secret_encrypted' => Crypt::encryptString($apiSecret),
        ];
    }

    /**
     * Verify the API secret.
     */
    public function verifySecret(string $secret): bool
    {
        try {
            $decrypted = Crypt::decryptString($this->api_secret);

            return hash_equals($decrypted, $secret);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the partner has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (empty($this->permissions)) {
            return false;
        }

        // Check for wildcard permission
        if (in_array('*', $this->permissions)) {
            return true;
        }

        // Check for exact permission
        if (in_array($permission, $this->permissions)) {
            return true;
        }

        // Check for wildcard pattern (e.g., 'listings:*' matches 'listings:read')
        foreach ($this->permissions as $perm) {
            if (str_ends_with($perm, ':*')) {
                $prefix = substr($perm, 0, -2);

                if (str_starts_with($permission, $prefix . ':')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the partner's IP is whitelisted.
     */
    public function isIpWhitelisted(string $ip): bool
    {
        // If no whitelist is configured, allow all IPs
        if (empty($this->ip_whitelist)) {
            return true;
        }

        return in_array($ip, $this->ip_whitelist);
    }

    /**
     * Check if the partner's API key has expired.
     */
    public function hasExpiredApiKey(): bool
    {
        if (! $this->api_key_expires_at) {
            return false; // No expiration set
        }

        return $this->api_key_expires_at->isPast();
    }

    /**
     * Check if the partner is approved.
     */
    public function isApproved(): bool
    {
        return $this->kyc_status === 'approved';
    }

    /**
     * Check if the partner is in sandbox mode.
     */
    public function isSandboxMode(): bool
    {
        return $this->sandbox_mode === true;
    }

    /**
     * Get the partner's current balance.
     */
    public function getCurrentBalance(): float
    {
        $latestTransaction = $this->transactions()
            ->orderBy('created_at', 'desc')
            ->first();

        return $latestTransaction ? (float) $latestTransaction->balance_after : 0.0;
    }

    /**
     * Update the last_used_at timestamp.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope a query to only include active partners.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include approved partners.
     */
    public function scopeApproved($query)
    {
        return $query->where('kyc_status', 'approved');
    }

    /**
     * Scope a query to only include partners in production mode.
     */
    public function scopeProduction($query)
    {
        return $query->where('sandbox_mode', false);
    }

    /**
     * Scope a query to filter by partner tier.
     */
    public function scopeByTier($query, string $tier)
    {
        return $query->where('partner_tier', $tier);
    }

    /**
     * Scope a query to filter by KYC status.
     */
    public function scopeByKycStatus($query, string $status)
    {
        return $query->where('kyc_status', $status);
    }
}
