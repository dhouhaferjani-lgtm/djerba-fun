<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.booking_cancelled_header') }}</title>
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
            background-color: #dc2626;
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
            border-left: 4px solid #dc2626;
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
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('mail.booking_cancelled_header') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.dear') }} {{ $travelerInfo['firstName'] ?? __('mail.traveler') }} {{ $travelerInfo['lastName'] ?? '' }},</p>

        <p>{{ __('mail.booking_cancelled_body') }}</p>

        <div class="booking-details">
            <h2 style="margin-top: 0; color: #dc2626;">{{ __('mail.cancelled_booking') }}</h2>

            <div class="detail-row">
                <span class="label">{{ __('mail.booking_number') }}:</span> {{ $booking->booking_number }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.listing') }}:</span> {{ $listingTitle }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.total_amount') }}:</span> {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.cancelled_on') }}:</span> {{ $booking->cancelled_at->translatedFormat(__('mail.date_format_full')) }}
            </div>

            @if($cancellationReason)
            <div class="detail-row">
                <span class="label">{{ __('mail.reason') }}:</span> {{ $cancellationReason }}
            </div>
            @endif
        </div>

        <div class="warning-box">
            <strong>{{ __('mail.refund_info') }}</strong>
            <p style="margin: 10px 0 0 0;">
                {{ __('mail.refund_body') }}
            </p>
        </div>

        <p><strong>{{ __('mail.what_happens_next') }}</strong></p>
        <ul>
            <li>{{ __('mail.reservation_released') }}</li>
            <li>{{ __('mail.refund_processed') }}</li>
            <li>{{ __('mail.refund_email') }}</li>
        </ul>

        <p>{{ __('mail.sorry_cancel') }}</p>

        <p>{{ __('mail.hope_serve_again') }}</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ __('mail.brand_name') }}. {{ __('mail.all_rights_reserved') }}</p>
        <p>{{ __('mail.automated_message') }}</p>
    </div>
</body>
</html>
