<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CustomTripRequestConfirmationMail;
use App\Models\CustomTripRequest;
use App\Services\EmailLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestCustomTripEmail extends Command
{
    protected $signature = 'app:test-custom-trip-email
                            {--email= : Override recipient email address}
                            {--sync : Send synchronously instead of via queue}
                            {--id= : Specific CustomTripRequest ID to use}';

    protected $description = 'Diagnostic: Test sending a custom trip confirmation email';

    public function handle(EmailLogService $emailLogService): int
    {
        $this->info('=== Custom Trip Email Diagnostic ===');
        $this->newLine();

        // Find a custom trip request to use
        $requestId = $this->option('id');
        if ($requestId) {
            $tripRequest = CustomTripRequest::find($requestId);
        } else {
            $tripRequest = CustomTripRequest::orderBy('created_at', 'desc')->first();
        }

        if (! $tripRequest) {
            $this->error('No custom trip requests found in the database.');
            $this->info('Submit a custom trip request via the frontend first, then re-run this command.');

            return self::FAILURE;
        }

        $this->info("Using CustomTripRequest: {$tripRequest->reference} (ID: {$tripRequest->id})");
        $this->info("Original recipient: {$tripRequest->contact_email}");

        $recipientEmail = $this->option('email') ?: $tripRequest->contact_email;
        $this->info("Sending to: {$recipientEmail}");
        $this->newLine();

        // Step 1: Test mailable construction
        $this->info('[1/4] Constructing mailable...');
        try {
            $mailable = new CustomTripRequestConfirmationMail($tripRequest);
            $this->info('  OK - Mailable constructed');
        } catch (\Throwable $e) {
            $this->error("  FAILED - {$e->getMessage()}");
            $this->error("  Trace: {$e->getTraceAsString()}");

            return self::FAILURE;
        }

        // Step 2: Test rendering
        $this->info('[2/4] Rendering email HTML...');
        try {
            $html = $mailable->render();
            $this->info('  OK - Rendered ' . strlen($html) . ' bytes of HTML');
        } catch (\Throwable $e) {
            $this->error("  FAILED - {$e->getMessage()}");
            $this->error("  Trace: {$e->getTraceAsString()}");

            return self::FAILURE;
        }

        // Step 3: Test serialization (simulates what the queue does)
        $this->info('[3/4] Testing serialization (queue simulation)...');
        try {
            $serialized = serialize($mailable);
            $deserialized = unserialize($serialized);
            $this->info('  OK - Serialized/deserialized (' . strlen($serialized) . ' bytes)');

            // Test rendering after deserialization (this is what the queue worker does)
            $html2 = $deserialized->render();
            $this->info('  OK - Re-rendered after deserialization (' . strlen($html2) . ' bytes)');
        } catch (\Throwable $e) {
            $this->error("  FAILED - {$e->getMessage()}");
            $this->error("  This confirms the issue is in queue serialization/deserialization!");
            $this->error("  Trace: {$e->getTraceAsString()}");

            return self::FAILURE;
        }

        // Step 4: Actually send the email
        if ($this->option('sync')) {
            $this->info('[4/4] Sending email SYNCHRONOUSLY via ' . config('mail.default') . '...');
            try {
                $emailLog = $emailLogService->send(
                    $recipientEmail,
                    new CustomTripRequestConfirmationMail($tripRequest),
                    null,
                    ['name' => $tripRequest->contact_name, 'phone' => $tripRequest->contact_phone]
                );
                $this->info("  OK - Email sent! EmailLog ID: {$emailLog->id}, Status: {$emailLog->status->value}");
            } catch (\Throwable $e) {
                $this->error("  FAILED - {$e->getMessage()}");
                $this->error("  Trace: {$e->getTraceAsString()}");

                return self::FAILURE;
            }
        } else {
            $this->info('[4/4] Queuing email via ' . config('queue.default') . ' queue...');
            try {
                $emailLog = $emailLogService->queue(
                    $recipientEmail,
                    new CustomTripRequestConfirmationMail($tripRequest),
                    null,
                    ['name' => $tripRequest->contact_name, 'phone' => $tripRequest->contact_phone]
                );
                $this->info("  OK - Email queued! EmailLog ID: {$emailLog->id}, Status: {$emailLog->status->value}");
            } catch (\Throwable $e) {
                $this->error("  FAILED - {$e->getMessage()}");
                $this->error("  Trace: {$e->getTraceAsString()}");

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('=== Diagnostic Summary ===');
        $this->info("Mail driver: " . config('mail.default'));
        $this->info("Queue driver: " . config('queue.default'));
        $this->info("From address: " . config('mail.from.address'));
        $this->newLine();

        if (! $this->option('sync') && config('queue.default') !== 'sync') {
            $this->warn('Email was queued (not sent immediately).');
            $this->warn('If the email does not arrive, the queue worker may be failing.');
            $this->warn('Try running with --sync to bypass the queue and send directly.');
        } else {
            $this->info('Email was sent synchronously. Check inbox (and spam folder).');
        }

        return self::SUCCESS;
    }
}
