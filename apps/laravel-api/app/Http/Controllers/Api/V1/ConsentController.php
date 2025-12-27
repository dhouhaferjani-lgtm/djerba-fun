<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function __construct(
        private readonly ConsentService $consentService
    ) {}

    /**
     * Record consent(s).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consents' => 'required|array',
            'consents.*' => 'required|boolean',
            'context' => 'nullable|string|max:50',
            'session_id' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
        ]);

        $user = $request->user();
        $sessionId = $validated['session_id'] ?? null;
        $email = $validated['email'] ?? null;
        $context = $validated['context'] ?? null;

        // Validate consent types
        $validTypes = array_keys(Consent::getTypes());
        $invalidTypes = array_diff(array_keys($validated['consents']), $validTypes);

        if (! empty($invalidTypes)) {
            return response()->json([
                'message' => 'Invalid consent types: ' . implode(', ', $invalidTypes),
            ], 422);
        }

        $recorded = $this->consentService->recordMultipleConsents(
            $validated['consents'],
            $user,
            $sessionId,
            $email,
            $context,
            $request
        );

        return response()->json([
            'message' => 'Consents recorded successfully.',
            'data' => $recorded->map(fn ($c) => [
                'type' => $c->consent_type,
                'granted' => $c->granted,
                'grantedAt' => $c->granted_at?->toISOString(),
            ]),
        ]);
    }

    /**
     * Get current consent status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');
        $email = $request->query('email');

        $status = $this->consentService->getConsentStatus($user, $sessionId, $email);

        return response()->json([
            'data' => $status,
        ]);
    }

    /**
     * Revoke a specific consent.
     */
    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'session_id' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
        ]);

        $validTypes = array_keys(Consent::getTypes());

        if (! in_array($validated['type'], $validTypes)) {
            return response()->json([
                'message' => 'Invalid consent type.',
            ], 422);
        }

        $user = $request->user();
        $sessionId = $validated['session_id'] ?? null;
        $email = $validated['email'] ?? null;

        $revoked = $this->consentService->revokeConsent(
            $validated['type'],
            $user,
            $sessionId,
            $email
        );

        if (! $revoked) {
            return response()->json([
                'message' => 'No consent found to revoke.',
            ], 404);
        }

        return response()->json([
            'message' => 'Consent revoked successfully.',
        ]);
    }

    /**
     * Get consent history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Authentication required to view consent history.',
            ], 401);
        }

        $history = $this->consentService->getConsentHistory($user);

        return response()->json([
            'data' => $history->map(fn ($c) => [
                'id' => $c->id,
                'type' => $c->consent_type,
                'typeLabel' => Consent::getTypes()[$c->consent_type] ?? $c->consent_type,
                'granted' => $c->granted,
                'context' => $c->context,
                'grantedAt' => $c->granted_at?->toISOString(),
                'revokedAt' => $c->revoked_at?->toISOString(),
                'createdAt' => $c->created_at->toISOString(),
            ]),
        ]);
    }
}
