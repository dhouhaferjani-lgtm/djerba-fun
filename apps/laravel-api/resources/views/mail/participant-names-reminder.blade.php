<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Names Reminder</title>
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
            <strong>⏰ Urgent: {{ $daysUntilActivity }} {{ $daysUntilActivity === 1 ? 'day' : 'days' }} until your activity!</strong>
            <p style="margin: 10px 0 0 0;">Please provide participant names as soon as possible to ensure smooth check-in.</p>
        </div>
        @endif

        <div class="header">
            <h1>{{ $isUrgent ? '⚠️' : '📝' }} Participant Names Needed</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $travelerName }}! 👋
            </div>

            <div class="message">
                @if($isUrgent)
                <p><strong>Your upcoming activity is just {{ $daysUntilActivity }} {{ $daysUntilActivity === 1 ? 'day' : 'days' }} away!</strong></p>
                <p>We still need the names of all participants for your booking. Adding them now will help ensure a quick and smooth check-in when you arrive.</p>
                @else
                <p>We're looking forward to having you join us for your upcoming adventure!</p>
                <p>To help us prepare for your arrival, please take a moment to provide the names of all participants in your booking.</p>
                @endif
            </div>

            <div class="booking-summary">
                <h3>Booking Summary</h3>
                <div class="detail-row">
                    <span class="label">Booking Number:</span>
                    <strong>{{ $booking->booking_number }}</strong>
                </div>
                <div class="detail-row">
                    <span class="label">Activity:</span>
                    {{ $listing->title }}
                </div>
                @if($slot)
                <div class="detail-row">
                    <span class="label">Date & Time:</span>
                    {{ $slot->start_time->format('l, F j, Y \a\t g:i A') }}
                </div>
                @endif
                <div class="detail-row">
                    <span class="label">Participants:</span>
                    {{ $booking->quantity }} {{ $booking->quantity > 1 ? 'people' : 'person' }}
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $participantsLink }}" class="add-names-button">
                    {{ $isUrgent ? 'Add Names Now' : 'Provide Participant Names' }}
                </a>
            </div>

            <div class="why-section">
                <h3>Why do we need participant names?</h3>
                <ul>
                    <li>✅ Faster check-in process when you arrive</li>
                    <li>✅ Personalized vouchers for each participant</li>
                    <li>✅ Better preparation by our activity providers</li>
                    <li>✅ Improved safety and emergency contact procedures</li>
                </ul>
            </div>

            <div class="message" style="margin-top: 30px;">
                <p>It only takes a minute and makes everyone's experience better!</p>
                <p>If you have any questions, feel free to reach out to our support team.</p>
                <p>We can't wait to see you! 🏔️</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
            <p style="font-size: 0.85em; margin-top: 10px;">
                You're receiving this reminder because participant names are still pending for booking {{ $booking->booking_number }}.
            </p>
        </div>
    </div>
</body>
</html>
