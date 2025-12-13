<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
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
        <h1>Booking Confirmed!</h1>
    </div>

    <div class="content">
        <p>Dear {{ $travelerInfo['firstName'] ?? 'Traveler' }} {{ $travelerInfo['lastName'] ?? '' }},</p>

        <p>Your booking has been confirmed! We're excited to have you join us.</p>

        <div class="booking-details">
            <h2 style="margin-top: 0; color: #0D642E;">Booking Details</h2>

            <div class="detail-row">
                <span class="label">Booking Number:</span> {{ $booking->booking_number }}
            </div>

            <div class="detail-row">
                <span class="label">Listing:</span> {{ $listing->title }}
            </div>

            @if($slot)
            <div class="detail-row">
                <span class="label">Date & Time:</span> {{ $slot->start_time->format('F j, Y \a\t g:i A') }}
            </div>
            @endif

            <div class="detail-row">
                <span class="label">Quantity:</span> {{ $booking->quantity }} {{ $booking->quantity > 1 ? 'guests' : 'guest' }}
            </div>

            <div class="detail-row">
                <span class="label">Total Amount:</span> {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
            </div>

            <div class="detail-row">
                <span class="label">Status:</span> <strong style="color: #8BC34A;">{{ $booking->status->label() }}</strong>
            </div>
        </div>

        @if(!empty($booking->extras))
        <div class="booking-details">
            <h3 style="margin-top: 0; color: #0D642E;">Add-ons</h3>
            @foreach($booking->extras as $extra)
            <div class="detail-row">
                - {{ $extra['name'] ?? 'Extra' }} ({{ $extra['quantity'] ?? 1 }}x {{ $booking->currency }} {{ number_format($extra['price'] ?? 0, 2) }})
            </div>
            @endforeach
        </div>
        @endif

        <p><strong>What's next?</strong></p>
        <ul>
            <li>Save this confirmation email for your records</li>
            <li>Check your email for any additional information from the vendor</li>
            <li>Contact us if you have any questions or need to make changes</li>
        </ul>

        <p>If you need to cancel or modify your booking, please contact us as soon as possible.</p>

        <p>Thank you for choosing Go Adventure!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
        <p>This is an automated message, please do not reply directly to this email.</p>
    </div>
</body>
</html>
