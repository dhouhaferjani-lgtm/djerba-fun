<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailLogStatus;
use App\Enums\EmailType;
use App\Models\Booking;
use App\Models\EmailLog;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailLogService
{
    /**
     * Queue an email and log it.
     *
     * @param  string  $to  Recipient email address
     * @param  Mailable  $mailable  The mailable instance
     * @param  Booking|null  $booking  Optional booking for context
     * @param  array  $recipientInfo  Optional recipient info ['name' => ..., 'phone' => ...]
     */
    public function queue(
        string $to,
        Mailable $mailable,
        ?Booking $booking = null,
        array $recipientInfo = []
    ): EmailLog {
        // Create the log entry BEFORE queuing
        $emailLog = $this->createLogEntry($to, $mailable, $booking, $recipientInfo);

        try {
            // Render the email content for storage
            $rendered = $mailable->render();
            $emailLog->update(['html_content' => $rendered]);

            // Queue the email
            // The UpdateEmailLogOnSent listener will update the status to 'sent'
            // when the email is actually processed by the queue worker
            Mail::to($to)->queue($mailable);

            return $emailLog;
        } catch (\Throwable $e) {
            $this->markAsFailed($emailLog, $e->getMessage());
            Log::error('Email queuing failed', [
                'email_log_id' => $emailLog->id,
                'recipient' => $to,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send an email immediately and log it.
     *
     * @param  string  $to  Recipient email address
     * @param  Mailable  $mailable  The mailable instance
     * @param  Booking|null  $booking  Optional booking for context
     * @param  array  $recipientInfo  Optional recipient info ['name' => ..., 'phone' => ...]
     */
    public function send(
        string $to,
        Mailable $mailable,
        ?Booking $booking = null,
        array $recipientInfo = []
    ): EmailLog {
        // Create the log entry BEFORE sending
        $emailLog = $this->createLogEntry($to, $mailable, $booking, $recipientInfo);

        try {
            // Render the email content for storage
            $rendered = $mailable->render();
            $emailLog->update(['html_content' => $rendered]);

            // Send the email immediately
            $sentMessage = Mail::to($to)->send($mailable);

            // Update with sent status and capture message ID if available
            $messageId = null;

            if ($sentMessage && method_exists($sentMessage, 'getMessageId')) {
                $messageId = $sentMessage->getMessageId();
            }

            $emailLog->update([
                'status' => EmailLogStatus::SENT,
                'sent_at' => now(),
                'mailgun_message_id' => $messageId,
            ]);

            return $emailLog;
        } catch (\Throwable $e) {
            $this->markAsFailed($emailLog, $e->getMessage());
            Log::error('Email sending failed', [
                'email_log_id' => $emailLog->id,
                'recipient' => $to,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create the initial log entry.
     */
    protected function createLogEntry(
        string $to,
        Mailable $mailable,
        ?Booking $booking,
        array $recipientInfo
    ): EmailLog {
        $mailClass = get_class($mailable);
        $emailType = EmailType::fromMailClass($mailClass);

        // Determine vendor_id and listing_id from booking
        $vendorId = null;
        $listingId = null;

        if ($booking) {
            $booking->loadMissing('listing');
            $vendorId = $booking->listing?->vendor_id;
            $listingId = $booking->listing_id;
        }

        // Extract recipient info from booking if not provided
        if (empty($recipientInfo) && $booking) {
            $primary = $booking->getPrimaryTraveler();

            if ($primary) {
                $firstName = $primary['first_name'] ?? $primary['firstName'] ?? '';
                $lastName = $primary['last_name'] ?? $primary['lastName'] ?? '';
                $recipientInfo = [
                    'name' => trim($firstName . ' ' . $lastName),
                    'phone' => $primary['phone'] ?? null,
                ];
            }
        }

        return EmailLog::create([
            'recipient_email' => $to,
            'recipient_name' => $recipientInfo['name'] ?? null,
            'recipient_phone' => $recipientInfo['phone'] ?? null,
            'email_type' => $emailType,
            'email_class' => $mailClass,
            'subject' => $this->extractSubject($mailable),
            'status' => EmailLogStatus::QUEUED,
            'booking_id' => $booking?->id,
            'listing_id' => $listingId,
            'vendor_id' => $vendorId,
            'queued_at' => now(),
        ]);
    }

    /**
     * Extract subject from mailable.
     */
    protected function extractSubject(Mailable $mailable): string
    {
        // Try to get subject from envelope
        if (method_exists($mailable, 'envelope')) {
            $envelope = $mailable->envelope();

            return $envelope->subject ?? 'No Subject';
        }

        // Fallback: try to get from subject property
        if (property_exists($mailable, 'subject') && $mailable->subject) {
            return $mailable->subject;
        }

        return 'No Subject';
    }

    /**
     * Mark email as failed.
     */
    public function markAsFailed(EmailLog $emailLog, string $errorMessage): void
    {
        $emailLog->update([
            'status' => EmailLogStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Update status from Mailgun webhook.
     */
    public function updateFromWebhook(string $messageId, string $event, array $eventData = []): bool
    {
        $emailLog = EmailLog::where('mailgun_message_id', $messageId)->first();

        if (! $emailLog) {
            Log::warning('Email log not found for Mailgun message', [
                'message_id' => $messageId,
                'event' => $event,
            ]);

            return false;
        }

        $updates = match ($event) {
            'delivered' => [
                'status' => EmailLogStatus::DELIVERED,
                'delivered_at' => now(),
            ],
            'opened' => [
                'status' => EmailLogStatus::OPENED,
                'opened_at' => now(),
            ],
            'bounced', 'failed' => [
                'status' => EmailLogStatus::BOUNCED,
                'bounced_at' => now(),
                'error_message' => $eventData['error'] ?? $eventData['description'] ?? 'Bounced',
            ],
            'dropped' => [
                'status' => EmailLogStatus::FAILED,
                'failed_at' => now(),
                'error_message' => $eventData['reason'] ?? 'Dropped by Mailgun',
            ],
            'complained' => [
                'status' => EmailLogStatus::COMPLAINED,
                'complained_at' => now(),
            ],
            default => null,
        };

        if ($updates) {
            $emailLog->update($updates);

            return true;
        }

        return false;
    }

    /**
     * Resend a failed/bounced email.
     */
    public function resend(EmailLog $emailLog): EmailLog
    {
        if (! $emailLog->canBeResent()) {
            throw new \RuntimeException('This email cannot be resent.');
        }

        // Get the original mailable class and booking
        $mailClass = $emailLog->email_class;
        $booking = $emailLog->booking;

        if (! $booking) {
            throw new \RuntimeException('Cannot resend: booking not found.');
        }

        // Reload booking with relationships
        $booking->load(['listing', 'availabilitySlot', 'user']);

        // Instantiate the mailable
        $mailable = new $mailClass($booking);

        // Create new email log entry and queue
        return $this->queue(
            $emailLog->recipient_email,
            $mailable,
            $booking,
            [
                'name' => $emailLog->recipient_name,
                'phone' => $emailLog->recipient_phone,
            ]
        );
    }
}
