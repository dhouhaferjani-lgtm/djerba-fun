<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PartnerApiKey extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'partner_id',
        'name',
        'key_hash',
        'key_encrypted',
        'status',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
        'key_encrypted',
    ];

    /**
     * Get the partner that owns this API key.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Generate a new API key for a partner.
     */
    public static function generateForPartner(Partner $partner, ?string $name = null, ?int $expiresInDays = null): array
    {
        $plainKey = 'pak_' . Str::random(40); // Partner API Key
        $keyHash = hash('sha256', $plainKey);
        $keyEncrypted = Crypt::encryptString($plainKey);

        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;

        $apiKey = static::create([
            'partner_id' => $partner->id,
            'name' => $name,
            'key_hash' => $keyHash,
            'key_encrypted' => $keyEncrypted,
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Get the last 8 characters of the key for display.
     */
    public function getMaskedKeyAttribute(): string
    {
        try {
            $decrypted = Crypt::decryptString($this->key_encrypted);

            return '****' . substr($decrypted, -8);
        } catch (\Exception $e) {
            return '****';
        }
    }

    /**
     * Check if the API key is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the API key has expired.
     */
    public function hasExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the API key is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Check if the API key is usable (active and not expired).
     */
    public function isUsable(): bool
    {
        return $this->isActive() && ! $this->hasExpired() && ! $this->isRevoked();
    }

    /**
     * Revoke this API key.
     */
    public function revoke(): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }

    /**
     * Record usage of this API key.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope a query to only include active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include revoked keys.
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    /**
     * Scope a query to only include expired keys.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->whereNotNull('expires_at');
    }

    /**
     * Scope a query to only include usable keys (active and not expired).
     */
    public function scopeUsable($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to filter by partner.
     */
    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }
}
