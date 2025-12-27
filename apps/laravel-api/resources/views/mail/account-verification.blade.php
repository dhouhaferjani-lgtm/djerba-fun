<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
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
            background: linear-gradient(135deg, #0D642E 0%, #8BC34A 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
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
            margin-bottom: 30px;
            color: #555;
        }
        .bookings-banner {
            background: linear-gradient(135deg, #8BC34A 0%, #0D642E 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
            font-weight: 600;
        }
        .bookings-count {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            background-color: #0D642E;
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .verify-button:hover {
            background-color: #094d22;
        }
        .link-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #8BC34A;
        }
        .link-section p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .link-url {
            word-break: break-all;
            color: #0D642E;
            font-size: 12px;
            margin-top: 10px;
        }
        .expiration-notice {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .expiration-notice strong {
            color: #f57c00;
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
        <div class="header">
            <h1>✨ Welcome to Go Adventure!</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $user->first_name ?? $user->display_name ?? 'Adventurer' }}! 👋
            </div>

            <div class="message">
                <p>Thank you for creating your Go Adventure account!</p>
                <p>To get started, please verify your email address by clicking the button below:</p>
            </div>

            @if($claimableBookingsCount > 0)
            <div class="bookings-banner">
                <div>🎉 Great News!</div>
                <div class="bookings-count">{{ $claimableBookingsCount }}</div>
                <div>
                    {{ $claimableBookingsCount === 1 ? 'Booking Found!' : 'Bookings Found!' }}
                </div>
                <div style="margin-top: 10px; font-size: 14px; font-weight: normal;">
                    We found {{ $claimableBookingsCount }} {{ $claimableBookingsCount === 1 ? 'booking' : 'bookings' }} matching your email.
                    You can link {{ $claimableBookingsCount === 1 ? 'it' : 'them' }} to your account after verifying.
                </div>
            </div>
            @endif

            <div class="button-container">
                <a href="{{ $verificationLink }}" class="verify-button">
                    Verify Email Address
                </a>
            </div>

            <div class="link-section">
                <p><strong>If the button doesn't work, copy and paste this link into your browser:</strong></p>
                <div class="link-url">{{ $verificationLink }}</div>
            </div>

            <div class="expiration-notice">
                <strong>⏰ Important:</strong> This verification link will expire in {{ $expiresInHours }} hours for your security.
            </div>

            <div class="message" style="margin-top: 30px;">
                <p>If you didn't create this account, you can safely ignore this email.</p>
                <p>Happy adventuring! 🏔️</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
