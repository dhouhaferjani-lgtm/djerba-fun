<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    use HasFactory, HasUuids;

    /**
     * Available consent types.
     */
    public const TYPE_TERMS = 'terms';
    public const TYPE_PRIVACY = 'privacy';
    public const TYPE_MARKETING = 'marketing';
    public const TYPE_COOKIES_ESSENTIAL = 'cookies_essential';
    public const TYPE_COOKIES_ANALYTICS = 'cookies_analytics';
    public const TYPE_COOKIES_MARKETING = 'cookies_marketing';

    /**
     * Available contexts.
     */
    public const CONTEXT_CHECKOUT = 'checkout';
    public const CONTEXT_REGISTRATION = 'registration';
    public const CONTEXT_COOKIE_BANNER = 'cookie_banner';
    public const CONTEXT_SETTINGS = 'settings';

    protected $fillable = [
        'user_id',
        'session_id',
        'email',
        'consent_type',
        'granted',
        'ip_address',
        'user_agent',
        'context',
        'granted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Get the user who gave this consent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get consents by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Scope to get granted consents.
     */
    public function scopeGranted($query)
    {
        return $query->where('granted', true)->whereNull('revoked_at');
    }

    /**
     * Scope to get consents by session.
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get consents by email.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Check if this consent is currently active (granted and not revoked).
     */
    public function isActive(): bool
    {
        return $this->granted && $this->revoked_at === null;
    }

    /**
     * Revoke this consent.
     */
    public function revoke(): void
    {
        $this->update([
            'revoked_at' => now(),
        ]);
    }

    /**
     * Get all available consent types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_TERMS => 'Terms of Service',
            self::TYPE_PRIVACY => 'Privacy Policy',
            self::TYPE_MARKETING => 'Marketing Communications',
            self::TYPE_COOKIES_ESSENTIAL => 'Essential Cookies',
            self::TYPE_COOKIES_ANALYTICS => 'Analytics Cookies',
            self::TYPE_COOKIES_MARKETING => 'Marketing Cookies',
        ];
    }
}
