<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Cancellation</title>
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
        <h1>Booking Cancelled</h1>
    </div>

    <div class="content">
        <p>Dear {{ $travelerInfo['firstName'] ?? 'Traveler' }} {{ $travelerInfo['lastName'] ?? '' }},</p>

        <p>This email confirms that your booking has been cancelled.</p>

        <div class="booking-details">
            <h2 style="margin-top: 0; color: #dc2626;">Cancelled Booking</h2>

            <div class="detail-row">
                <span class="label">Booking Number:</span> {{ $booking->booking_number }}
            </div>

            <div class="detail-row">
                <span class="label">Listing:</span> {{ $listingTitle }}
            </div>

            <div class="detail-row">
                <span class="label">Total Amount:</span> {{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}
            </div>

            <div class="detail-row">
                <span class="label">Cancelled On:</span> {{ $booking->cancelled_at->format('F j, Y \a\t g:i A') }}
            </div>

            @if($cancellationReason)
            <div class="detail-row">
                <span class="label">Reason:</span> {{ $cancellationReason }}
            </div>
            @endif
        </div>

        <div class="warning-box">
            <strong>Refund Information</strong>
            <p style="margin: 10px 0 0 0;">
                If you are eligible for a refund according to the cancellation policy,
                it will be processed within 5-10 business days to your original payment method.
            </p>
        </div>

        <p><strong>What happens next?</strong></p>
        <ul>
            <li>Your reservation has been released and is no longer active</li>
            <li>Any applicable refunds will be processed according to the cancellation policy</li>
            <li>You will receive a separate email when the refund is processed (if applicable)</li>
        </ul>

        <p>We're sorry to see you cancel. If you have any questions or concerns, please don't hesitate to contact us.</p>

        <p>We hope to serve you again in the future!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
        <p>This is an automated message, please do not reply directly to this email.</p>
    </div>
</body>
</html>
