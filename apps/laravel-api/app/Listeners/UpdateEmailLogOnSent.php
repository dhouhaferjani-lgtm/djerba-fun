<?php

namespace App\Listeners;

use App\Enums\EmailLogStatus;
use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class UpdateEmailLogOnSent
{
    /**
     * Handle the MessageSent event.
     *
     * Updates the EmailLog status from 'queued' to 'sent' when an email
     * is actually sent by the queue worker.
     */
    public function handle(MessageSent $event): void
    {
        try {
            // Get the message ID from the sent message
            $messageId = $event->sent?->getMessageId();

            // Get the recipient email from the message
            $to = $event->message->getTo();
            $recipientEmail = !empty($to) ? array_key_first($to) : null;

            if (!$recipientEmail) {
                Log::warning('UpdateEmailLogOnSent: No recipient email found in message');
                return;
            }

            // Find the most recent queued email for this recipient
            // We match by recipient email and status to find the correct log entry
            $emailLog = EmailLog::where('recipient_email', $recipientEmail)
                ->where('status', EmailLogStatus::QUEUED)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($emailLog) {
                $emailLog->update([
                    'status' => EmailLogStatus::SENT,
                    'sent_at' => now(),
                    'mailgun_message_id' => $messageId,
                ]);

                Log::info('UpdateEmailLogOnSent: Email log updated to sent', [
                    'email_log_id' => $emailLog->id,
                    'recipient' => $recipientEmail,
                    'message_id' => $messageId,
                ]);
            } else {
                Log::debug('UpdateEmailLogOnSent: No queued email log found for recipient', [
                    'recipient' => $recipientEmail,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('UpdateEmailLogOnSent: Failed to update email log', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
