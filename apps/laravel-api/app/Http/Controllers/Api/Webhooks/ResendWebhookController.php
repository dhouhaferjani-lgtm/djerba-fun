<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\EmailLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResendWebhookController extends Controller
{
    public function __construct(
        private readonly EmailLogService $emailLogService
    ) {}

    /**
     * Handle incoming Resend webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify the webhook signature
        if (! $this->verifySignature($request)) {
            Log::warning('Resend webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $type = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];
        $emailId = $data['email_id'] ?? null;

        if (! $type || ! $emailId) {
            Log::warning('Resend webhook missing required data', [
                'has_type' => (bool) $type,
                'has_email_id' => (bool) $emailId,
            ]);

            return response()->json(['error' => 'Missing required data'], 400);
        }

        // Map Resend event to our internal event names
        $event = $this->mapResendEvent($type);

        if ($event) {
            $additionalData = [
                'error' => $data['bounce']['message'] ?? null,
                'description' => $data['bounce']['description'] ?? null,
                'reason' => $data['reason'] ?? null,
            ];

            $updated = $this->emailLogService->updateFromWebhook(
                $emailId,
                $event,
                $additionalData
            );

            Log::info('Resend webhook processed', [
                'type' => $type,
                'event' => $event,
                'email_id' => $emailId,
                'updated' => $updated,
            ]);
        } else {
            Log::debug('Resend webhook event not handled', [
                'type' => $type,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Map Resend event type to our internal event names.
     */
    protected function mapResendEvent(string $type): ?string
    {
        return match ($type) {
            'email.sent' => 'sent',
            'email.delivered' => 'delivered',
            'email.opened' => 'opened',
            'email.clicked' => 'clicked',
            'email.bounced' => 'bounced',
            'email.complained' => 'complained',
            default => null,
        };
    }

    /**
     * Verify Resend webhook signature using Svix.
     */
    protected function verifySignature(Request $request): bool
    {
        $signingSecret = config('services.resend.webhook_secret');

        // If no signing secret configured, skip verification in local/testing environments
        if (! $signingSecret) {
            return app()->environment('local', 'testing');
        }

        $signature = $request->header('svix-signature');
        $timestamp = $request->header('svix-timestamp');
        $webhookId = $request->header('svix-id');

        if (! $signature || ! $timestamp || ! $webhookId) {
            return false;
        }

        // Check if timestamp is recent (within 5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $payload = $request->getContent();
        $signedContent = "{$webhookId}.{$timestamp}.{$payload}";

        // Strip the "whsec_" prefix if present (Resend/Svix format)
        if (str_starts_with($signingSecret, 'whsec_')) {
            $signingSecret = substr($signingSecret, 6);
        }

        // Decode the secret (it's base64 encoded)
        $secretBytes = base64_decode($signingSecret);

        $expectedSignature = base64_encode(
            hash_hmac('sha256', $signedContent, $secretBytes, true)
        );

        // Resend/Svix sends multiple signatures separated by space, check each
        $signatures = explode(' ', $signature);

        foreach ($signatures as $sig) {
            // Format is "v1,signature"
            if (str_starts_with($sig, 'v1,')) {
                $providedSig = substr($sig, 3);

                if (hash_equals($expectedSignature, $providedSig)) {
                    return true;
                }
            }
        }

        return false;
    }
}
