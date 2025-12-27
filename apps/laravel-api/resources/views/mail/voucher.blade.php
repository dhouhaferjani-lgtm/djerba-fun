<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Voucher{{ $isSingleVoucher ? '' : 's' }}</title>
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
        <h1>Your {{ $isSingleVoucher ? 'Voucher' : 'Vouchers' }}</h1>
    </div>

    <div class="content">
        <p>Dear {{ $booking->billing_contact['first_name'] ?? 'Traveler' }},</p>

        <p>
            @if($isSingleVoucher)
                Your voucher for <strong>{{ $listing->getTranslation('title', 'en') }}</strong> is attached to this email.
            @else
                Your vouchers for <strong>{{ $listing->getTranslation('title', 'en') }}</strong> are attached to this email.
            @endif
        </p>

        <div class="booking-details">
            <h2>{{ $listing->isEvent() ? 'Event' : 'Tour' }} Information</h2>

            <div class="detail-row">
                <span class="detail-label">Date:</span> {{ $slot?->date->format('l, F j, Y') ?? 'TBD' }}
            </div>

            <div class="detail-row">
                <span class="detail-label">Time:</span> {{ $slot?->start_time->format('g:i A') ?? 'TBD' }}
            </div>

            @if($listing->meeting_point && isset($listing->meeting_point['address']))
            <div class="detail-row">
                <span class="detail-label">Location:</span> {{ $listing->meeting_point['address'] }}
            </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Booking Number:</span> {{ $booking->booking_number }}
            </div>

            @if($listing->isEvent() && !$isSingleVoucher)
            <div class="detail-row">
                <span class="detail-label">Participants:</span> {{ $booking->participants()->count() }}
            </div>
            @endif
        </div>

        @if($listing->isEvent())
        <div class="instructions-box event">
            <h3>📋 Check-in Instructions</h3>
            <p>Each participant will need their individual voucher for check-in:</p>
            <ul>
                <li>Print your voucher or save it to your mobile device</li>
                <li>Bring a valid photo ID matching the name on the voucher</li>
                @if($participant && $participant->badge_number)
                <li>Your badge number is <strong>#{{ $participant->badge_number }}</strong></li>
                @else
                <li>Your badge number is displayed prominently on your voucher</li>
                @endif
                <li>Arrive at least 15 minutes before the event start time</li>
            </ul>
        </div>
        @else
        <div class="instructions-box tour">
            <h3>📋 Check-in Instructions</h3>
            <p>Please bring your voucher{{ $isSingleVoucher ? '' : 's' }} with you:</p>
            <ul>
                <li>Print or save to your mobile device</li>
                <li>The QR code will be scanned at check-in</li>
                <li>Arrive at the meeting point on time</li>
            </ul>
        </div>
        @endif

        @if($booking->magic_token)
        <a href="{{ url("/api/v1/bookings/magic/{$booking->magic_token}") }}" class="button">
            View Booking Details
        </a>
        @endif

        <p><strong>Need help?</strong></p>
        <p>If you have any questions or need assistance, please don't hesitate to contact us.</p>

        <p>We look forward to seeing you!</p>
        <p>The {{ $platformName }} Team</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $platformName }}. All rights reserved.</p>
    </div>
</body>
</html>
