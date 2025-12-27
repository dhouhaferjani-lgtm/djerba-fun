<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerWebhook extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'partner_id',
        'event',
        'url',
        'payload',
        'response_status',
        'response_body',
        'attempts',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'response_status' => 'integer',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the partner that owns this webhook.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Check if webhook was delivered successfully.
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Check if webhook delivery failed.
     */
    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }

    /**
     * Check if webhook is pending delivery.
     */
    public function isPending(): bool
    {
        return $this->delivered_at === null && $this->failed_at === null;
    }

    /**
     * Check if webhook can be retried.
     */
    public function canRetry(): bool
    {
        return $this->attempts < 5 && $this->failed_at === null;
    }

    /**
     * Mark webhook as delivered.
     */
    public function markAsDelivered(int $statusCode, ?string $responseBody = null): void
    {
        $this->update([
            'delivered_at' => now(),
            'response_status' => $statusCode,
            'response_body' => $responseBody,
        ]);
    }

    /**
     * Mark webhook as failed.
     */
    public function markAsFailed(int $statusCode, ?string $responseBody = null): void
    {
        $this->update([
            'failed_at' => now(),
            'response_status' => $statusCode,
            'response_body' => $responseBody,
        ]);
    }

    /**
     * Increment attempt counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Scope a query to only include delivered webhooks.
     */
    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    /**
     * Scope a query to only include failed webhooks.
     */
    public function scopeFailed($query)
    {
        return $query->whereNotNull('failed_at');
    }

    /**
     * Scope a query to only include pending webhooks.
     */
    public function scopePending($query)
    {
        return $query->whereNull('delivered_at')
            ->whereNull('failed_at');
    }

    /**
     * Scope a query to only include webhooks that can be retried.
     */
    public function scopeRetryable($query)
    {
        return $query->where('attempts', '<', 5)
            ->whereNull('failed_at')
            ->whereNull('delivered_at');
    }

    /**
     * Scope a query to filter by event type.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to filter by partner.
     */
    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include successful deliveries (2xx status codes).
     */
    public function scopeSuccessful($query)
    {
        return $query->whereNotNull('delivered_at')
            ->whereBetween('response_status', [200, 299]);
    }
}
