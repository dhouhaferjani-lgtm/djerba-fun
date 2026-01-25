<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\EmailLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailgunWebhookController extends Controller
{
    public function __construct(
        private readonly EmailLogService $emailLogService
    ) {}

    /**
     * Handle incoming Mailgun webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify the webhook signature
        if (! $this->verifySignature($request)) {
            Log::warning('Mailgun webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $eventData = $request->input('event-data', []);
        $event = $eventData['event'] ?? null;
        $messageId = $eventData['message']['headers']['message-id'] ?? null;

        if (! $event || ! $messageId) {
            Log::warning('Mailgun webhook missing required data', [
                'has_event' => (bool) $event,
                'has_message_id' => (bool) $messageId,
            ]);

            return response()->json(['error' => 'Missing required data'], 400);
        }

        // Extract delivery status info
        $deliveryStatus = $eventData['delivery-status'] ?? [];
        $additionalData = [
            'error' => $deliveryStatus['message'] ?? null,
            'description' => $deliveryStatus['description'] ?? null,
            'reason' => $eventData['reason'] ?? null,
        ];

        $updated = $this->emailLogService->updateFromWebhook(
            $messageId,
            $event,
            $additionalData
        );

        Log::info('Mailgun webhook processed', [
            'event' => $event,
            'message_id' => $messageId,
            'updated' => $updated,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify Mailgun webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signingKey = config('services.mailgun.webhook_signing_key');

        // If no signing key configured, skip verification in local/testing environments
        if (! $signingKey) {
            return app()->environment('local', 'testing');
        }

        $signature = $request->input('signature', []);
        $timestamp = $signature['timestamp'] ?? '';
        $token = $signature['token'] ?? '';
        $providedSignature = $signature['signature'] ?? '';

        // Check if timestamp is recent (within 5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // Verify the signature
        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp . $token,
            $signingKey
        );

        return hash_equals($expectedSignature, $providedSignature);
    }
}
