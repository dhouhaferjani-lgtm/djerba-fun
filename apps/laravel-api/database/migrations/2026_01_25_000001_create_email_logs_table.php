<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Recipient information (for vendor to contact traveler)
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();

            // Email type/classification
            $table->string('email_type', 50); // confirmation, cancellation, voucher, etc.
            $table->string('email_class'); // Full class name: App\Mail\BookingConfirmationMail
            $table->string('subject');

            // Content storage
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();

            // Status tracking
            $table->string('status', 30)->default('queued'); // queued, sent, delivered, opened, bounced, failed, complained
            $table->text('error_message')->nullable();

            // Mailgun integration
            $table->string('mailgun_message_id')->nullable();

            // Relations (nullable for non-booking emails)
            $table->uuid('booking_id')->nullable();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable();

            // Timestamps for status tracking
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('complained_at')->nullable();

            $table->timestamps();

            // Indexes for vendor panel queries
            $table->index(['vendor_id', 'created_at']);
            $table->index(['booking_id']);
            $table->index(['mailgun_message_id']);
            $table->index(['status', 'created_at']);
            $table->index(['email_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
