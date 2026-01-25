<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailLogStatus;
use App\Enums\EmailType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'recipient_phone',
        'email_type',
        'email_class',
        'subject',
        'html_content',
        'text_content',
        'status',
        'error_message',
        'mailgun_message_id',
        'booking_id',
        'listing_id',
        'vendor_id',
        'queued_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'bounced_at',
        'failed_at',
        'complained_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailLogStatus::class,
            'email_type' => EmailType::class,
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'bounced_at' => 'datetime',
            'failed_at' => 'datetime',
            'complained_at' => 'datetime',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if email can be resent.
     */
    public function canBeResent(): bool
    {
        return in_array($this->status, [
            EmailLogStatus::FAILED,
            EmailLogStatus::BOUNCED,
        ], true);
    }

    /**
     * Get traveler info from related booking.
     */
    public function getTravelerInfo(): ?array
    {
        if (! $this->booking) {
            return null;
        }

        return [
            'name' => $this->recipient_name,
            'email' => $this->recipient_email,
            'phone' => $this->recipient_phone,
        ];
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by vendor.
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope to filter resendable emails.
     */
    public function scopeResendable($query)
    {
        return $query->whereIn('status', [
            EmailLogStatus::FAILED->value,
            EmailLogStatus::BOUNCED->value,
        ]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, EmailLogStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for failed/bounced emails.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [
            EmailLogStatus::FAILED->value,
            EmailLogStatus::BOUNCED->value,
            EmailLogStatus::COMPLAINED->value,
        ]);
    }
}
