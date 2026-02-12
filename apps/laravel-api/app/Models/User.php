<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use Notifiable;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'status',
        'display_name',
        'first_name',
        'last_name',
        'phone',
        'avatar_url',
        'preferred_locale',
        'email_verified_at',
        'magic_token_hash',
        'magic_token_expires_at',
        'magic_token_used_at',
        'prefers_passwordless',
        'last_magic_login_at',
        'verification_token_hash',
        'verification_token_expires_at',
        'oauth_provider',
        'oauth_provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'magic_token_hash',
        'verification_token_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'magic_token_expires_at' => 'datetime',
            'magic_token_used_at' => 'datetime',
            'last_magic_login_at' => 'datetime',
            'prefers_passwordless' => 'boolean',
            'verification_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the traveler profile for the user.
     */
    public function travelerProfile(): HasOne
    {
        return $this->hasOne(TravelerProfile::class);
    }

    /**
     * Get the vendor profile for the user.
     */
    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    /**
     * Get the reviews written by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the listings owned by the vendor.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'vendor_id');
    }

    /**
     * Get the payouts for the vendor.
     */
    public function vendorPayouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'vendor_id');
    }

    /**
     * Check if user is a vendor
     */
    public function isVendor(): bool
    {
        return $this->role === UserRole::VENDOR;
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is a traveler
     */
    public function isTraveler(): bool
    {
        return $this->role === UserRole::TRAVELER;
    }

    /**
     * Check if user is an agent
     */
    public function isAgent(): bool
    {
        return $this->role === UserRole::AGENT;
    }

    /**
     * Check if user registered via OAuth (Google, Facebook, etc.)
     */
    public function isOAuthUser(): bool
    {
        return $this->oauth_provider !== null;
    }

    /**
     * Check if user can access the platform
     */
    public function canAccess(): bool
    {
        return $this->status->canAccess();
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        if ($panel->getId() === 'vendor') {
            return $this->isVendor();
        }

        return false;
    }

    /**
     * Get the name attribute for Filament.
     */
    public function getNameAttribute(): string
    {
        return $this->display_name ?? $this->email;
    }

    /**
     * Get the user's preferred locale for notifications and emails.
     */
    public function preferredLocale(): ?string
    {
        return $this->preferred_locale;
    }
}
