<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.access_booking_header') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #0D642E;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0D642E;
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px 0;
        }
        .booking-info {
            background-color: #f5f0d1;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .booking-info h2 {
            margin-top: 0;
            color: #0D642E;
            font-size: 18px;
        }
        .links-section {
            margin: 30px 0;
        }
        .links-section h3 {
            color: #0D642E;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .link-button {
            display: block;
            background-color: #0D642E;
            color: white !important;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
        }
        .link-button:hover {
            background-color: #095020;
        }
        .link-button.secondary {
            background-color: #0D642E;
            color: white !important;
            border: none;
        }
        .expiry-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .footer a {
            color: #0D642E;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('mail.go_adventure') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.hello') }},</p>

        <p>{{ __('mail.access_booking_body') }}</p>

        <div class="booking-info">
            <h2>{{ __('mail.booking_number') }} #{{ $booking->booking_number }}</h2>
            @if($booking->listing)
                <p><strong>{{ __('mail.activity') }}:</strong> {{ $booking->listing->title }}</p>
            @endif
            @if($booking->availabilitySlot)
                <p><strong>{{ __('mail.date') }}:</strong> {{ $booking->availabilitySlot->start_time->translatedFormat(__('mail.date_format_day')) }}</p>
                <p><strong>{{ __('mail.time') }}:</strong> {{ $booking->availabilitySlot->start_time->translatedFormat(__('mail.time_format')) }}</p>
            @endif
            <p><strong>{{ __('mail.guests') }}:</strong> {{ $booking->quantity }}</p>
        </div>

        <div class="links-section">
            <h3>{{ __('mail.manage_booking') }}</h3>

            <a href="{{ $magicLink }}" class="link-button">
                {{ __('mail.view_booking_details') }}
            </a>

            <a href="{{ $participantsLink }}" class="link-button secondary" style="background-color: #0D642E; color: white !important; text-decoration: none;">
                {{ __('mail.enter_participant_names') }}
            </a>

            <a href="{{ $vouchersLink }}" class="link-button secondary" style="background-color: #0D642E; color: white !important; text-decoration: none;">
                {{ __('mail.download_vouchers') }}
            </a>
        </div>

        <div class="expiry-notice">
            <strong>Note:</strong> {{ __('mail.links_expire_on', ['date' => $expiresAt]) }}
        </div>

        <p>{{ __('mail.questions_contact') }}</p>

        <p>{{ __('mail.thank_you') }}</p>
    </div>

    <div class="footer">
        <p>
            {{ __('mail.go_adventure') }}<br>
            <a href="{{ config('app.frontend_url') }}">{{ config('app.frontend_url') }}</a>
        </p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy">{{ __('mail.privacy_policy') }}</a> |
            <a href="{{ config('app.frontend_url') }}/terms">{{ __('mail.terms_of_service') }}</a>
        </p>
        <p>
            {{ __('mail.receiving_because_requested') }}
        </p>
    </div>
</body>
</html>
