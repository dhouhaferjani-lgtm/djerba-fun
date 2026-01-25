<?php

declare(strict_types=1);

namespace App\Enums;

use App\Mail\AccountVerificationMail;
use App\Mail\BookingCancellationMail;
use App\Mail\BookingConfirmationMail;
use App\Mail\ContactFormMail;
use App\Mail\ListingPublishFailedMail;
use App\Mail\MagicLinkMail;
use App\Mail\MagicLoginMail;
use App\Mail\ParticipantNamesReminderMail;
use App\Mail\VoucherMail;

enum EmailType: string
{
    case BOOKING_CONFIRMATION = 'confirmation';
    case BOOKING_CANCELLATION = 'cancellation';
    case VOUCHER = 'voucher';
    case MAGIC_LINK = 'magic_link';
    case MAGIC_LOGIN = 'magic_login';
    case PARTICIPANT_REMINDER = 'participant_reminder';
    case ACCOUNT_VERIFICATION = 'account_verification';
    case CONTACT_FORM = 'contact_form';
    case LISTING_PUBLISH_FAILED = 'listing_publish_failed';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BOOKING_CONFIRMATION => 'Booking Confirmation',
            self::BOOKING_CANCELLATION => 'Booking Cancellation',
            self::VOUCHER => 'Voucher',
            self::MAGIC_LINK => 'Magic Link',
            self::MAGIC_LOGIN => 'Magic Login',
            self::PARTICIPANT_REMINDER => 'Participant Reminder',
            self::ACCOUNT_VERIFICATION => 'Account Verification',
            self::CONTACT_FORM => 'Contact Form',
            self::LISTING_PUBLISH_FAILED => 'Listing Publish Failed',
            self::OTHER => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BOOKING_CONFIRMATION => 'success',
            self::BOOKING_CANCELLATION => 'danger',
            self::VOUCHER => 'info',
            self::MAGIC_LINK => 'warning',
            self::MAGIC_LOGIN => 'warning',
            self::PARTICIPANT_REMINDER => 'info',
            self::ACCOUNT_VERIFICATION => 'primary',
            self::CONTACT_FORM => 'gray',
            self::LISTING_PUBLISH_FAILED => 'danger',
            self::OTHER => 'gray',
        };
    }

    /**
     * Map Mail class to EmailType.
     */
    public static function fromMailClass(string $mailClass): self
    {
        return match ($mailClass) {
            BookingConfirmationMail::class => self::BOOKING_CONFIRMATION,
            BookingCancellationMail::class => self::BOOKING_CANCELLATION,
            VoucherMail::class => self::VOUCHER,
            MagicLinkMail::class => self::MAGIC_LINK,
            MagicLoginMail::class => self::MAGIC_LOGIN,
            ParticipantNamesReminderMail::class => self::PARTICIPANT_REMINDER,
            AccountVerificationMail::class => self::ACCOUNT_VERIFICATION,
            ContactFormMail::class => self::CONTACT_FORM,
            ListingPublishFailedMail::class => self::LISTING_PUBLISH_FAILED,
            default => self::OTHER,
        };
    }
}
