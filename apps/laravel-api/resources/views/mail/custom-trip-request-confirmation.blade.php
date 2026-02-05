<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Trip Request Received</title>
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
        .request-details {
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
        .reference-box {
            background-color: #f5f0d1;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .reference-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #0D642E;
            letter-spacing: 1px;
        }
        .next-steps {
            background-color: #e8f5e9;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #0D642E;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9em;
        }
        .contact-info {
            background-color: #fff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>We've Received Your Request!</h1>
    </div>

    <div class="content">
        <p>Dear {{ $contactName }},</p>

        <p>Thank you for choosing Go Adventure for your custom trip! We've received your request and our travel experts are excited to start crafting your perfect Tunisia experience.</p>

        <div class="reference-box">
            <p style="margin: 0 0 5px 0; color: #666;">Your Reference Number</p>
            <div class="reference-number">{{ $reference }}</div>
            <p style="margin: 10px 0 0 0; font-size: 0.85em; color: #666;">Please save this for future correspondence</p>
        </div>

        <div class="request-details">
            <h2 style="margin-top: 0; color: #0D642E;">Your Trip Summary</h2>

            <div class="detail-row">
                <span class="label">Travel Dates:</span> {{ $travelDates }}
                @if($datesFlexible)
                    <span style="color: #666;">(Flexible)</span>
                @endif
            </div>

            <div class="detail-row">
                <span class="label">Duration:</span> {{ $durationDays }} days
            </div>

            <div class="detail-row">
                <span class="label">Travelers:</span> {{ $travelerSummary }}
            </div>

            @if($interests)
            <div class="detail-row">
                <span class="label">Interests:</span> {{ $interests }}
            </div>
            @endif

            <div class="detail-row">
                <span class="label">Budget:</span> {{ $budgetCurrency }} {{ number_format($budget, 0) }} per person
            </div>

            @if($accommodationStyle)
            <div class="detail-row">
                <span class="label">Accommodation Style:</span> {{ $accommodationStyle }}
            </div>
            @endif

            @if($travelPace)
            <div class="detail-row">
                <span class="label">Travel Pace:</span> {{ $travelPace }}
            </div>
            @endif

            @if($specialRequests)
            <div class="detail-row">
                <span class="label">Special Requests:</span><br>
                <span style="color: #666;">{{ $specialRequests }}</span>
            </div>
            @endif
        </div>

        <div class="next-steps">
            <h3 style="margin-top: 0; color: #0D642E;">What Happens Next?</h3>
            <ol style="margin: 0; padding-left: 20px;">
                <li style="margin-bottom: 10px;"><strong>Review:</strong> Our travel experts will review your preferences</li>
                <li style="margin-bottom: 10px;"><strong>Contact:</strong> We'll reach out within 24-48 hours to discuss your trip</li>
                <li style="margin-bottom: 10px;"><strong>Proposal:</strong> You'll receive a personalized itinerary and quote</li>
                <li><strong>Finalize:</strong> We'll refine the details until it's perfect for you</li>
            </ol>
        </div>

        <div class="contact-info">
            <h3 style="margin-top: 0; color: #0D642E;">Need to Reach Us Sooner?</h3>
            <p style="margin-bottom: 10px;">If you have urgent questions or additional details to share:</p>
            <p style="margin: 5px 0;">
                <strong>Email:</strong> <a href="mailto:hello@go-adventure.net" style="color: #0D642E;">hello@go-adventure.net</a>
            </p>
            <p style="margin: 5px 0;">
                <strong>WhatsApp:</strong> <a href="https://wa.me/21612345678" style="color: #0D642E;">+216 12 345 678</a>
            </p>
            <p style="margin: 5px 0; font-size: 0.9em; color: #666;">
                Please include your reference number: <strong>{{ $reference }}</strong>
            </p>
        </div>

        <p>We can't wait to help you discover the beauty of Tunisia!</p>

        <p>Warm regards,<br>
        <strong>The Go Adventure Team</strong></p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy" style="color: #0D642E;">Privacy Policy</a> |
            <a href="{{ config('app.frontend_url') }}/terms" style="color: #0D642E;">Terms of Service</a>
        </p>
        <p style="font-size: 0.85em;">
            You're receiving this email because you submitted a custom trip request on Go Adventure.
        </p>
    </div>
</body>
</html>
