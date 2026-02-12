<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.booking_confirmed_header') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0D642E;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .booking-details {
            background-color: #ffffff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #8BC34A;
        }
        .detail-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #0D642E;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9em;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0D642E;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('mail.booking_confirmed_header') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.dear') }} {{ $travelerInfo['firstName'] ?? __('mail.traveler') }} {{ $travelerInfo['lastName'] ?? '' }},</p>

        <p>{{ __('mail.booking_confirmed_body') }}</p>

        <div class="booking-details">
            <h2 style="margin-top: 0; color: #0D642E;">{{ __('mail.booking_details') }}</h2>

            <div class="detail-row">
                <span class="label">{{ __('mail.booking_number') }}:</span> {{ $booking->booking_number }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.listing') }}:</span> {{ $listingTitle }}
            </div>

            @if($slot)
            <div class="detail-row">
                <span class="label">{{ __('mail.date_time') }}:</span> {{ $slot->start_time->translatedFormat(__('mail.date_format_full')) }}
            </div>
            @endif

            <div class="detail-row">
                <span class="label">{{ __('mail.quantity') }}:</span> {{ $booking->quantity }} {{ $booking->quantity > 1 ? __('mail.guest_plural') : __('mail.guest_singular') }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.total_amount') }}:</span> {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.status') }}:</span> <strong style="color: #8BC34A;">{{ $booking->status->label() }}</strong>
            </div>
        </div>

        @if(!empty($booking->extras))
        <div class="booking-details">
            <h3 style="margin-top: 0; color: #0D642E;">{{ __('mail.add_ons') }}</h3>
            @foreach($booking->extras as $extra)
            <div class="detail-row">
                - {{ $extra['name'] ?? 'Extra' }} ({{ $extra['quantity'] ?? 1 }}x {{ $booking->currency }} {{ number_format($extra['price'] ?? 0, 2) }})
            </div>
            @endforeach
        </div>
        @endif

        @if($magicLink)
        <div class="booking-details" style="background-color: #f5f0d1; border-left-color: #0D642E;">
            <h3 style="margin-top: 0; color: #0D642E;">{{ __('mail.manage_booking') }}</h3>
            <p>{{ __('mail.manage_booking_desc') }}</p>

            <div style="margin: 15px 0;">
                <a href="{{ $magicLink }}" class="button" style="display: block; text-align: center; margin-bottom: 10px;">
                    {{ __('mail.view_booking_details') }}
                </a>
            </div>

            <div style="margin: 15px 0;">
                <a href="{{ $participantsLink }}" style="display: block; padding: 12px 24px; background-color: #f5f5f5; color: #333; text-decoration: none; border-radius: 5px; text-align: center; border: 1px solid #ddd; margin-bottom: 10px;">
                    {{ __('mail.enter_participant_names') }}
                </a>
            </div>

            <div style="margin: 15px 0;">
                <a href="{{ $vouchersLink }}" style="display: block; padding: 12px 24px; background-color: #f5f5f5; color: #333; text-decoration: none; border-radius: 5px; text-align: center; border: 1px solid #ddd;">
                    {{ __('mail.download_vouchers') }}
                </a>
            </div>

            <p style="font-size: 0.85em; color: #666; margin-top: 15px;">
                <strong>Note:</strong> {{ __('mail.links_expire_note', ['date' => $magicLinkExpiresAt]) }}
            </p>
        </div>
        @endif

        @if($booking->travelerDetailsPending() && $listing?->promptForNamesImmediately())
        <div class="booking-details" style="background-color: #fff8e1; border-left-color: #ffc107;">
            <h3 style="margin-top: 0; color: #f57c00;">{{ __('mail.action_required_participants') }}</h3>
            <p><strong>{{ __('mail.activity_requires_names') }}</strong></p>
            <p>{{ __('mail.provide_names_soon') }}</p>

            @if($participantsLink)
            <div style="margin: 20px 0; text-align: center;">
                <a href="{{ $participantsLink }}" style="display: inline-block; padding: 14px 30px; background-color: #f57c00; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    {{ __('mail.provide_names_now') }}
                </a>
            </div>
            @endif
        </div>
        @elseif($booking->travelerDetailsPending() && !$listing?->promptForNamesImmediately())
        <div class="booking-details" style="background-color: #e3f2fd; border-left-color: #2196F3;">
            <h3 style="margin-top: 0; color: #1976D2;">{{ __('mail.participant_names_optional') }}</h3>
            <p>{{ __('mail.participant_names_optional_desc') }}</p>
            <p>{{ __('mail.participant_names_early') }}</p>

            @if($participantsLink)
            <div style="margin: 20px 0; text-align: center;">
                <a href="{{ $participantsLink }}" style="display: inline-block; padding: 12px 24px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px;">
                    {{ __('mail.add_names') }}
                </a>
            </div>
            @endif
        </div>
        @endif

        @if(!$booking->user_id)
        <div class="booking-details" style="background: linear-gradient(135deg, rgba(139, 195, 74, 0.1) 0%, rgba(13, 100, 46, 0.1) 100%); border-left-color: #8BC34A;">
            <h3 style="margin-top: 0; color: #0D642E;">{{ __('mail.create_account') }}</h3>
            <p><strong>{{ __('mail.track_bookings') }}</strong></p>

            <ul style="margin: 15px 0; padding-left: 20px;">
                <li>{{ __('mail.view_all_bookings') }}</li>
                <li>{{ __('mail.one_click_checkout') }}</li>
                <li>{{ __('mail.save_favorites') }}</li>
                <li>{{ __('mail.exclusive_offers') }}</li>
            </ul>

            <p style="margin-bottom: 20px;">{{ __('mail.no_password_needed') }}</p>

            <div style="text-align: center;">
                <a href="{{ config('app.frontend_url') }}/auth/register-quick?email={{ urlencode($booking->billing_contact['email'] ?? '') }}&bookingId={{ $booking->id }}" style="display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #8BC34A 0%, #0D642E 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    {{ __('mail.create_free_account') }}
                </a>
            </div>

            <p style="font-size: 0.85em; color: #666; margin-top: 15px; text-align: center;">
                {{ __('mail.takes_30_seconds') }}
            </p>
        </div>
        @endif

        <p><strong>{{ __('mail.what_next') }}</strong></p>
        <ul>
            <li>{{ __('mail.next_enter_names') }}</li>
            <li>{{ __('mail.next_download_vouchers') }}</li>
            <li>{{ __('mail.next_save_email') }}</li>
            <li>{{ __('mail.next_contact_us') }}</li>
        </ul>

        <p>{{ __('mail.cancel_modify_note') }}</p>

        <p>{{ __('mail.thank_you') }}</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ __('mail.go_adventure') }}. {{ __('mail.all_rights_reserved') }}</p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy" style="color: #0D642E;">{{ __('mail.privacy_policy') }}</a> |
            <a href="{{ config('app.frontend_url') }}/terms" style="color: #0D642E;">{{ __('mail.terms_of_service') }}</a>
        </p>
        <p style="font-size: 0.85em;">
            {{ __('mail.receiving_because_booking') }}
        </p>
    </div>
</body>
</html>
