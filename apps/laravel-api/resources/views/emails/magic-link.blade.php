<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Your Booking</title>
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
            background-color: #f5f5f5;
            color: #333 !important;
            border: 1px solid #ddd;
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
        <h1>Go Adventure</h1>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p>Here is your secure link to access your booking. Use it to view details, enter participant names, and download your vouchers.</p>

        <div class="booking-info">
            <h2>Booking #{{ $booking->booking_number }}</h2>
            @if($booking->listing)
                <p><strong>Activity:</strong> {{ $booking->listing->title }}</p>
            @endif
            @if($booking->availabilitySlot)
                <p><strong>Date:</strong> {{ $booking->availabilitySlot->start_time->format('l, F j, Y') }}</p>
                <p><strong>Time:</strong> {{ $booking->availabilitySlot->start_time->format('g:i A') }}</p>
            @endif
            <p><strong>Guests:</strong> {{ $booking->quantity }}</p>
        </div>

        <div class="links-section">
            <h3>Manage Your Booking</h3>

            <a href="{{ $magicLink }}" class="link-button">
                View Booking Details
            </a>

            <a href="{{ $participantsLink }}" class="link-button secondary">
                Enter Participant Names
            </a>

            <a href="{{ $vouchersLink }}" class="link-button secondary">
                Download Vouchers
            </a>
        </div>

        <div class="expiry-notice">
            <strong>Note:</strong> These links expire on {{ $expiresAt }}. If they expire, you can request new links at any time by visiting our website and entering your email and booking number.
        </div>

        <p>If you have any questions about your booking, please don't hesitate to contact us.</p>

        <p>Thank you for choosing Go Adventure!</p>
    </div>

    <div class="footer">
        <p>
            Go Adventure<br>
            <a href="{{ config('app.frontend_url') }}">{{ config('app.frontend_url') }}</a>
        </p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy">Privacy Policy</a> |
            <a href="{{ config('app.frontend_url') }}/terms">Terms of Service</a>
        </p>
        <p>
            You're receiving this email because you requested access to your booking on Go Adventure.
        </p>
    </div>
</body>
</html>
