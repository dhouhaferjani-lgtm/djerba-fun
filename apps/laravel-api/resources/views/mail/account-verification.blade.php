<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.welcome_header') }}</title>
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
            <h1>{{ __('mail.welcome_header') }}</h1>
        </div>

        <div class="content">
            <div class="greeting">
                {{ __('mail.hello') }} {{ $user->first_name ?? $user->display_name ?? __('mail.adventurer') }}!
            </div>

            <div class="message">
                <p>{{ __('mail.thank_you_account') }}</p>
                <p>{{ __('mail.verify_email_intro') }}</p>
            </div>

            @if($claimableBookingsCount > 0)
            <div class="bookings-banner">
                <div>{{ __('mail.great_news') }}</div>
                <div class="bookings-count">{{ $claimableBookingsCount }}</div>
                <div>
                    {{ $claimableBookingsCount === 1 ? __('mail.booking_found_singular') : __('mail.booking_found_plural') }}
                </div>
                <div style="margin-top: 10px; font-size: 14px; font-weight: normal;">
                    {{ $claimableBookingsCount === 1 ? __('mail.bookings_matching_singular', ['count' => $claimableBookingsCount]) : __('mail.bookings_matching_plural', ['count' => $claimableBookingsCount]) }}
                    {{ $claimableBookingsCount === 1 ? __('mail.link_after_verify_singular') : __('mail.link_after_verify_plural') }}
                </div>
            </div>
            @endif

            <div class="button-container">
                <a href="{{ $verificationLink }}" class="verify-button">
                    {{ __('mail.verify_email') }}
                </a>
            </div>

            <div class="link-section">
                <p><strong>{{ __('mail.button_not_working') }}</strong></p>
                <div class="link-url">{{ $verificationLink }}</div>
            </div>

            <div class="expiration-notice">
                <strong>{{ __('mail.link_expires_hours', ['hours' => $expiresInHours]) }}</strong>
            </div>

            <div class="message" style="margin-top: 30px;">
                <p>{{ __('mail.didnt_create_account') }}</p>
                <p>{{ __('mail.happy_adventuring') }}</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ __('mail.go_adventure') }}. {{ __('mail.all_rights_reserved') }}</p>
            <p>{{ __('mail.automated_message') }}</p>
        </div>
    </div>
</body>
</html>
