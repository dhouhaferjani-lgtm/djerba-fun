<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isSingleVoucher ? __('mail.your_voucher') : __('mail.your_vouchers') }}</title>
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
            background-color: {{ $colors['primary'] }};
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
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
            border-left: 4px solid {{ $colors['accent'] }};
        }
        .booking-details h2 {
            margin-top: 0;
            color: {{ $colors['primary'] }};
        }
        .detail-row {
            margin: 10px 0;
        }
        .detail-label {
            font-weight: bold;
            color: {{ $colors['primary'] }};
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: {{ $colors['primary'] }};
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .instructions-box {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .instructions-box.event {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
        }
        .instructions-box.tour {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
        }
        .instructions-box h3 {
            margin-top: 0;
            color: #f57c00;
        }
        .instructions-box.tour h3 {
            color: #1976D2;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" style="height: 40px; margin-bottom: 10px;">
        @endif
        <h1>{{ $isSingleVoucher ? __('mail.your_voucher') : __('mail.your_vouchers') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.dear') }} {{ $booking->billing_contact['first_name'] ?? __('mail.traveler') }},</p>

        <p>
            @if($isSingleVoucher)
                {{ __('mail.voucher_attached_single', ['listing' => $listing->getTranslation('title', app()->getLocale())]) }}
            @else
                {{ __('mail.voucher_attached_plural', ['listing' => $listing->getTranslation('title', app()->getLocale())]) }}
            @endif
        </p>

        <div class="booking-details">
            <h2>{{ $listing->isEvent() ? __('mail.event_info') : __('mail.tour_info') }}</h2>

            <div class="detail-row">
                <span class="detail-label">{{ __('mail.date') }}:</span> {{ $slot?->date->translatedFormat(__('mail.date_format_day')) ?? 'TBD' }}
            </div>

            <div class="detail-row">
                <span class="detail-label">{{ __('mail.time') }}:</span> {{ $slot?->start_time->translatedFormat(__('mail.time_format')) ?? 'TBD' }}
            </div>

            @if($listing->meeting_point && isset($listing->meeting_point['address']))
            <div class="detail-row">
                <span class="detail-label">{{ __('mail.location') }}:</span> {{ $listing->meeting_point['address'] }}
            </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">{{ __('mail.booking_number') }}:</span> {{ $booking->booking_number }}
            </div>

            @if($listing->isEvent() && !$isSingleVoucher)
            <div class="detail-row">
                <span class="detail-label">{{ __('mail.participants') }}:</span> {{ $booking->participants()->count() }}
            </div>
            @endif
        </div>

        @if($listing->isEvent())
        <div class="instructions-box event">
            <h3>{{ __('mail.checkin_instructions') }}</h3>
            <p>{{ __('mail.checkin_event_intro') }}</p>
            <ul>
                <li>{{ __('mail.checkin_print_voucher') }}</li>
                <li>{{ __('mail.checkin_photo_id') }}</li>
                @if($participant && $participant->badge_number)
                <li>{{ __('mail.checkin_badge', ['number' => $participant->badge_number]) }}</li>
                @else
                <li>{{ __('mail.checkin_badge_on_voucher') }}</li>
                @endif
                <li>{{ __('mail.checkin_arrive_early') }}</li>
            </ul>
        </div>
        @else
        <div class="instructions-box tour">
            <h3>{{ __('mail.checkin_instructions') }}</h3>
            <p>{{ $isSingleVoucher ? __('mail.checkin_tour_intro_single') : __('mail.checkin_tour_intro_plural') }}</p>
            <ul>
                <li>{{ __('mail.checkin_print_save') }}</li>
                <li>{{ __('mail.checkin_qr') }}</li>
                <li>{{ __('mail.checkin_on_time') }}</li>
            </ul>
        </div>
        @endif

        @if($booking->magic_token)
        <a href="{{ url("/api/v1/bookings/magic/{$booking->magic_token}") }}" class="button">
            {{ __('mail.view_booking_details') }}
        </a>
        @endif

        <p><strong>{{ __('mail.need_help') }}</strong></p>
        <p>{{ __('mail.need_help_body') }}</p>

        <p>{{ __('mail.look_forward') }}</p>
        <p>{{ __('mail.the_team') }}</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $platformName }}. {{ __('mail.all_rights_reserved') }}</p>
    </div>
</body>
</html>
