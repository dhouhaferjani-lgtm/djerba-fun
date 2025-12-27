<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Partner;
use App\Models\PartnerWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PartnerWebhookService
{
    /**
     * Dispatch a webhook event to a partner.
     */
    public function dispatch(Partner $partner, string $event, array $payload): ?PartnerWebhook
    {
        // Check if partner has webhook URL configured
        if (empty($partner->webhook_url)) {
            return null;
        }

        // Create webhook record
        $webhook = PartnerWebhook::create([
            'partner_id' => $partner->id,
            'event' => $event,
            'payload' => $payload,
            'url' => $partner->webhook_url,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
        ]);

        // Attempt delivery
        $this->attemptDelivery($webhook, $partner);

        return $webhook;
    }

    /**
     * Attempt to deliver a webhook.
     */
    protected function attemptDelivery(PartnerWebhook $webhook, Partner $partner): void
    {
        try {
            $webhook->incrementAttempts();

            $signature = $this->generateSignature($webhook->payload, $partner->webhook_secret);

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Partner-Webhook-Signature' => $signature,
                    'X-Partner-Webhook-Event' => $webhook->event,
                    'X-Partner-Webhook-ID' => $webhook->id,
                ])
                ->post($webhook->url, $webhook->payload);

            if ($response->successful()) {
                $webhook->markAsDelivered($response->status(), $response->body());
                Log::info('Partner webhook delivered successfully', [
                    'webhook_id' => $webhook->id,
                    'partner_id' => $partner->id,
                    'event' => $webhook->event,
                ]);
            } else {
                $this->handleFailedDelivery($webhook, $response->status(), $response->body());
            }
        } catch (\Exception $e) {
            $this->handleFailedDelivery($webhook, 0, $e->getMessage());
        }
    }

    /**
     * Handle failed webhook delivery.
     */
    protected function handleFailedDelivery(PartnerWebhook $webhook, int $statusCode, ?string $responseBody): void
    {
        $webhook->update([
            'last_response_status' => $statusCode,
            'last_response_body' => $responseBody,
        ]);

        if ($webhook->canRetry()) {
            $webhook->update(['status' => 'pending']);

            // Schedule retry with exponential backoff
            // In production, this would use a queued job
            Log::warning('Partner webhook delivery failed, will retry', [
                'webhook_id' => $webhook->id,
                'attempts' => $webhook->attempts,
                'max_attempts' => $webhook->max_attempts,
            ]);
        } else {
            $webhook->update(['status' => 'failed']);

            Log::error('Partner webhook delivery failed permanently', [
                'webhook_id' => $webhook->id,
                'attempts' => $webhook->attempts,
                'status_code' => $statusCode,
            ]);
        }
    }

    /**
     * Generate HMAC signature for webhook payload.
     */
    protected function generateSignature(array $payload, ?string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $signature, array $payload, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Send booking created webhook.
     */
    public function sendBookingCreated(Booking $booking, Partner $partner): ?PartnerWebhook
    {
        $payload = [
            'event' => 'booking.created',
            'timestamp' => now()->toISOString(),
            'data' => [
                'booking_id' => $booking->id,
                'listing_id' => $booking->listing_id,
                'listing_title' => $booking->listing->title ?? null,
                'status' => $booking->status,
                'quantity' => $booking->quantity,
                'pricing' => $booking->pricing_snapshot,
                'traveler_info' => $booking->traveler_info,
                'partner_reference' => $booking->partner_metadata['partner_reference'] ?? null,
                'created_at' => $booking->created_at->toISOString(),
            ],
        ];

        return $this->dispatch($partner, 'booking.created', $payload);
    }

    /**
     * Send booking confirmed webhook.
     */
    public function sendBookingConfirmed(Booking $booking, Partner $partner): ?PartnerWebhook
    {
        $payload = [
            'event' => 'booking.confirmed',
            'timestamp' => now()->toISOString(),
            'data' => [
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'payment_reference' => $booking->partner_metadata['payment_reference'] ?? null,
                'confirmed_at' => $booking->partner_metadata['payment_confirmed_at'] ?? now()->toISOString(),
            ],
        ];

        return $this->dispatch($partner, 'booking.confirmed', $payload);
    }

    /**
     * Send booking cancelled webhook.
     */
    public function sendBookingCancelled(Booking $booking, Partner $partner): ?PartnerWebhook
    {
        $payload = [
            'event' => 'booking.cancelled',
            'timestamp' => now()->toISOString(),
            'data' => [
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'cancelled_at' => $booking->cancelled_at?->toISOString(),
                'cancellation_reason' => $booking->cancellation_reason,
            ],
        ];

        return $this->dispatch($partner, 'booking.cancelled', $payload);
    }

    /**
     * Retry a failed webhook.
     */
    public function retry(PartnerWebhook $webhook): void
    {
        if (! $webhook->canRetry()) {
            throw new \RuntimeException('Webhook cannot be retried (max attempts reached)');
        }

        $partner = $webhook->partner;

        $this->attemptDelivery($webhook, $partner);
    }
}
