<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'api_key',
        'api_secret',
        'permissions',
        'rate_limit',
        'is_active',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'permissions' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'api_secret',
    ];

    /**
     * Get the audit logs for the agent.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AgentAuditLog::class);
    }

    /**
     * Generate a new API key and secret.
     */
    public static function generateCredentials(): array
    {
        $apiKey = 'ak_' . Str::random(32);
        $apiSecret = 'as_' . Str::random(48);

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
     * Check if the agent has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->is_active) {
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
     * Update the last_used_at timestamp.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope a query to only include active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
