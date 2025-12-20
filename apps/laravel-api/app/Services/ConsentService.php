<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Consent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConsentService
{
    /**
     * Record a consent grant or update.
     */
    public function recordConsent(
        string $type,
        bool $granted,
        ?User $user = null,
        ?string $sessionId = null,
        ?string $email = null,
        ?string $context = null,
        ?Request $request = null
    ): Consent {
        // Find existing consent or create new
        $query = Consent::where('consent_type', $type);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } elseif ($email) {
            $query->where('email', $email);
        }

        $consent = $query->first();

        $data = [
            'consent_type' => $type,
            'granted' => $granted,
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'email' => $email ?? $user?->email,
            'context' => $context,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'granted_at' => $granted ? now() : null,
            'revoked_at' => $granted ? null : now(),
        ];

        if ($consent) {
            $consent->update($data);
        } else {
            $consent = Consent::create($data);
        }

        return $consent;
    }

    /**
     * Record multiple consents at once.
     */
    public function recordMultipleConsents(
        array $consents, // ['type' => bool]
        ?User $user = null,
        ?string $sessionId = null,
        ?string $email = null,
        ?string $context = null,
        ?Request $request = null
    ): Collection {
        $recorded = collect();

        foreach ($consents as $type => $granted) {
            $consent = $this->recordConsent(
                $type,
                $granted,
                $user,
                $sessionId,
                $email,
                $context,
                $request
            );
            $recorded->push($consent);
        }

        return $recorded;
    }

    /**
     * Check if a consent has been granted.
     */
    public function hasConsent(
        string $type,
        ?User $user = null,
        ?string $sessionId = null,
        ?string $email = null
    ): bool {
        $query = Consent::ofType($type)->granted();

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        } elseif ($email) {
            $query->forEmail($email);
        } else {
            return false;
        }

        return $query->exists();
    }

    /**
     * Revoke a consent.
     */
    public function revokeConsent(
        string $type,
        ?User $user = null,
        ?string $sessionId = null,
        ?string $email = null
    ): bool {
        $query = Consent::ofType($type);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        } elseif ($email) {
            $query->forEmail($email);
        } else {
            return false;
        }

        $consent = $query->first();

        if ($consent) {
            $consent->revoke();
            return true;
        }

        return false;
    }

    /**
     * Get consent history for a user or email.
     */
    public function getConsentHistory(
        ?User $user = null,
        ?string $email = null
    ): Collection {
        $query = Consent::query();

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($email) {
            $query->forEmail($email);
        } else {
            return collect();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get current consent status for all types.
     */
    public function getConsentStatus(
        ?User $user = null,
        ?string $sessionId = null,
        ?string $email = null
    ): array {
        $status = [];

        foreach (Consent::getTypes() as $type => $label) {
            $status[$type] = [
                'label' => $label,
                'granted' => $this->hasConsent($type, $user, $sessionId, $email),
            ];
        }

        return $status;
    }

    /**
     * Migrate session consents to a user after login/registration.
     */
    public function migrateSessionConsentsToUser(string $sessionId, User $user): int
    {
        return Consent::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update([
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
    }
}
