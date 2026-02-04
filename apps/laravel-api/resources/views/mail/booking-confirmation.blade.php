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
                <span class="label">Listing:</span> {{ $listingTitle }}
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

        @if($magicLink)
        <div class="booking-details" style="background-color: #f5f0d1; border-left-color: #0D642E;">
            <h3 style="margin-top: 0; color: #0D642E;">Manage Your Booking</h3>
            <p>Use these secure links to access your booking anytime:</p>

            <div style="margin: 15px 0;">
                <a href="{{ $magicLink }}" class="button" style="display: block; text-align: center; margin-bottom: 10px;">
                    View Booking Details
                </a>
            </div>

            <div style="margin: 15px 0;">
                <a href="{{ $participantsLink }}" style="display: block; padding: 12px 24px; background-color: #f5f5f5; color: #333; text-decoration: none; border-radius: 5px; text-align: center; border: 1px solid #ddd; margin-bottom: 10px;">
                    Enter Participant Names
                </a>
            </div>

            <div style="margin: 15px 0;">
                <a href="{{ $vouchersLink }}" style="display: block; padding: 12px 24px; background-color: #f5f5f5; color: #333; text-decoration: none; border-radius: 5px; text-align: center; border: 1px solid #ddd;">
                    Download Vouchers
                </a>
            </div>

            <p style="font-size: 0.85em; color: #666; margin-top: 15px;">
                <strong>Note:</strong> These links expire on {{ $magicLinkExpiresAt }}. If they expire, you can request new links at any time.
            </p>
        </div>
        @endif

        @if($booking->travelerDetailsPending() && $listing?->promptForNamesImmediately())
        <div class="booking-details" style="background-color: #fff8e1; border-left-color: #ffc107;">
            <h3 style="margin-top: 0; color: #f57c00;">⚠️ Action Required: Participant Names</h3>
            <p><strong>This activity requires participant names before departure.</strong></p>
            <p>Please provide the names of all participants as soon as possible to ensure a smooth check-in experience.</p>

            @if($participantsLink)
            <div style="margin: 20px 0; text-align: center;">
                <a href="{{ $participantsLink }}" style="display: inline-block; padding: 14px 30px; background-color: #f57c00; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Provide Names Now
                </a>
            </div>
            @endif
        </div>
        @elseif($booking->travelerDetailsPending() && !$listing?->promptForNamesImmediately())
        <div class="booking-details" style="background-color: #e3f2fd; border-left-color: #2196F3;">
            <h3 style="margin-top: 0; color: #1976D2;">📝 Participant Names (Optional)</h3>
            <p>You can provide participant names now or later before your activity date.</p>
            <p>Adding names early helps us prepare better for your arrival!</p>

            @if($participantsLink)
            <div style="margin: 20px 0; text-align: center;">
                <a href="{{ $participantsLink }}" style="display: inline-block; padding: 12px 24px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px;">
                    Add Names
                </a>
            </div>
            @endif
        </div>
        @endif

        @if(!$booking->user_id)
        <div class="booking-details" style="background: linear-gradient(135deg, rgba(139, 195, 74, 0.1) 0%, rgba(13, 100, 46, 0.1) 100%); border-left-color: #8BC34A;">
            <h3 style="margin-top: 0; color: #0D642E;">✨ Create Your Account</h3>
            <p><strong>Track all your bookings in one place!</strong></p>

            <ul style="margin: 15px 0; padding-left: 20px;">
                <li>📊 View all bookings anytime</li>
                <li>⚡ One-click future checkouts</li>
                <li>⭐ Save favorite activities</li>
                <li>🎁 Get exclusive offers</li>
            </ul>

            <p style="margin-bottom: 20px;">No password needed – we'll send you a magic link!</p>

            <div style="text-align: center;">
                <a href="{{ config('app.frontend_url') }}/auth/register-quick?email={{ urlencode($booking->billing_contact['email'] ?? '') }}&bookingId={{ $booking->id }}" style="display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #8BC34A 0%, #0D642E 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Create Free Account →
                </a>
            </div>

            <p style="font-size: 0.85em; color: #666; margin-top: 15px; text-align: center;">
                Takes less than 30 seconds • No credit card required
            </p>
        </div>
        @endif

        <p><strong>What's next?</strong></p>
        <ul>
            <li>Enter participant names to receive personalized vouchers</li>
            <li>Download your vouchers before your activity date</li>
            <li>Save this confirmation email for your records</li>
            <li>Contact us if you have any questions or need to make changes</li>
        </ul>

        <p>If you need to cancel or modify your booking, use the links above or contact us as soon as possible.</p>

        <p>Thank you for choosing Go Adventure!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy" style="color: #0D642E;">Privacy Policy</a> |
            <a href="{{ config('app.frontend_url') }}/terms" style="color: #0D642E;">Terms of Service</a>
        </p>
        <p style="font-size: 0.85em;">
            You're receiving this email because you made a booking on Go Adventure.
        </p>
    </div>
</body>
</html>
