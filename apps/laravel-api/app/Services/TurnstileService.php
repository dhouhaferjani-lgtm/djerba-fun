<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $verifyUrl,
        private readonly int $timeout,
        private readonly bool $failOpen,
        private readonly bool $enabled,
    ) {}

    /**
     * Verify a Turnstile token.
     *
     * @param  string  $token  The cf-turnstile-response token
     * @param  string|null  $remoteIp  Optional IP for validation
     * @return array{success: bool, error_codes: array<string>, message: string}
     */
    public function verify(string $token, ?string $remoteIp = null): array
    {
        // If disabled, always pass
        if (! $this->enabled) {
            return ['success' => true, 'error_codes' => [], 'message' => 'Turnstile disabled'];
        }

        // Empty token = definitely invalid
        if (empty($token)) {
            return [
                'success' => false,
                'error_codes' => ['missing-input-response'],
                'message' => 'Turnstile token is required',
            ];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->verifyUrl, [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            if (! $response->successful()) {
                Log::warning('Turnstile API request failed', [
                    'status' => $response->status(),
                    'ip' => $remoteIp,
                ]);

                return $this->handleFailure('Turnstile verification service error');
            }

            $data = $response->json();

            if ($data['success'] ?? false) {
                return ['success' => true, 'error_codes' => [], 'message' => 'Verified'];
            }

            Log::info('Turnstile verification failed', [
                'error_codes' => $data['error-codes'] ?? [],
                'ip' => $remoteIp,
            ]);

            return [
                'success' => false,
                'error_codes' => $data['error-codes'] ?? ['unknown-error'],
                'message' => 'Turnstile verification failed',
            ];

        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
                'ip' => $remoteIp,
            ]);

            return $this->handleFailure('Turnstile verification unavailable');
        }
    }

    /**
     * Handle failure based on fail-open setting.
     *
     * @return array{success: bool, error_codes: array<string>, message: string}
     */
    private function handleFailure(string $message): array
    {
        if ($this->failOpen) {
            Log::warning('Turnstile fail-open triggered', ['message' => $message]);

            return ['success' => true, 'error_codes' => [], 'message' => 'Fail-open: '.$message];
        }

        return ['success' => false, 'error_codes' => ['service-unavailable'], 'message' => $message];
    }

    /**
     * Check if Turnstile is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
