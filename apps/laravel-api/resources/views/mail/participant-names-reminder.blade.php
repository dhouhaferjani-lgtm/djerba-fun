<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.participant_reminder_header') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, {{ $isUrgent ? '#f57c00' : '#0D642E' }} 0%, {{ $isUrgent ? '#ffc107' : '#8BC34A' }} 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .urgent-banner {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px;
            border-radius: 4px;
        }
        .urgent-banner strong {
            color: #f57c00;
            font-size: 18px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #0D642E;
        }
        .message {
            margin-bottom: 20px;
            color: #555;
        }
        .booking-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #8BC34A;
            margin: 25px 0;
        }
        .booking-summary h3 {
            margin-top: 0;
            color: #0D642E;
        }
        .detail-row {
            margin: 10px 0;
            padding: 5px 0;
        }
        .label {
            font-weight: 600;
            color: #666;
            display: inline-block;
            width: 140px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .add-names-button {
            display: inline-block;
            background-color: {{ $isUrgent ? '#f57c00' : '#0D642E' }};
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .add-names-button:hover {
            background-color: {{ $isUrgent ? '#e65100' : '#094d22' }};
        }
        .why-section {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .why-section h3 {
            margin-top: 0;
            color: #1976D2;
        }
        .why-section ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        .why-section li {
            margin: 8px 0;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
            color: #888;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($isUrgent)
        <div class="urgent-banner">
            <strong>{{ $daysUntilActivity === 1 ? __('mail.participant_reminder_upcoming_singular', ['days' => $daysUntilActivity]) : __('mail.participant_reminder_upcoming_plural', ['days' => $daysUntilActivity]) }}</strong>
            <p style="margin: 10px 0 0 0;">{{ __('mail.participant_reminder_urgent_desc') }}</p>
        </div>
        @endif

        <div class="header">
            <h1>{{ __('mail.participant_reminder_header') }}</h1>
        </div>

        <div class="content">
            <div class="greeting">
                {{ __('mail.hello') }} {{ $travelerName }}!
            </div>

            <div class="message">
                @if($isUrgent)
                <p><strong>{{ $daysUntilActivity === 1 ? __('mail.participant_reminder_upcoming_singular', ['days' => $daysUntilActivity]) : __('mail.participant_reminder_upcoming_plural', ['days' => $daysUntilActivity]) }}</strong></p>
                <p>{{ __('mail.participant_reminder_need_names') }}</p>
                @else
                <p>{{ __('mail.participant_reminder_looking_forward') }}</p>
                <p>{{ __('mail.participant_reminder_take_moment') }}</p>
                @endif
            </div>

            <div class="booking-summary">
                <h3>{{ __('mail.booking_summary') }}</h3>
                <div class="detail-row">
                    <span class="label">{{ __('mail.booking_number') }}:</span>
                    <strong>{{ $booking->booking_number }}</strong>
                </div>
                <div class="detail-row">
                    <span class="label">{{ __('mail.activity') }}:</span>
                    {{ $listing->title }}
                </div>
                @if($slot)
                <div class="detail-row">
                    <span class="label">{{ __('mail.date_time') }}:</span>
                    {{ $slot->start_time->translatedFormat(__('mail.date_format_day_full')) }}
                </div>
                @endif
                <div class="detail-row">
                    <span class="label">{{ __('mail.participants') }}:</span>
                    {{ $booking->quantity }} {{ $booking->quantity > 1 ? __('mail.person_plural') : __('mail.person_singular') }}
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $participantsLink }}" class="add-names-button">
                    {{ $isUrgent ? __('mail.add_names_now') : __('mail.provide_participant_names') }}
                </a>
            </div>

            <div class="why-section">
                <h3>{{ __('mail.why_names') }}</h3>
                <ul>
                    <li>{{ __('mail.why_faster_checkin') }}</li>
                    <li>{{ __('mail.why_personalized') }}</li>
                    <li>{{ __('mail.why_preparation') }}</li>
                    <li>{{ __('mail.why_safety') }}</li>
                </ul>
            </div>

            <div class="message" style="margin-top: 30px;">
                <p>{{ __('mail.takes_a_minute') }}</p>
                <p>{{ __('mail.support_questions') }}</p>
                <p>{{ __('mail.cant_wait') }}</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ __('mail.go_adventure') }}. {{ __('mail.all_rights_reserved') }}</p>
            <p style="font-size: 0.85em; margin-top: 10px;">
                {{ __('mail.receiving_because_pending', ['number' => $booking->booking_number]) }}
            </p>
        </div>
    </div>
</body>
</html>
