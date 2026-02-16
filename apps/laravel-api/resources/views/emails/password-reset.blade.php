<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.password_reset_header') }}</title>
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
        .reset-button {
            display: block;
            background-color: #0D642E;
            color: white !important;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .reset-button:hover {
            background-color: #095020;
        }
        .expiry-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        .ignore-notice {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
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
        <h1>{{ __('mail.go_adventure') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.hello') }},</p>

        <p>{{ __('mail.password_reset_body') }}</p>

        <a href="{{ $resetLink }}" class="reset-button" style="background-color: #0D642E; color: white !important; text-decoration: none;">
            {{ __('mail.password_reset_button') }}
        </a>

        <div class="expiry-notice">
            <strong>{{ __('mail.password_reset_expires', ['minutes' => $expiresInMinutes]) }}</strong>
        </div>

        <p class="ignore-notice">{{ __('mail.password_reset_ignore') }}</p>

        <p>{{ __('mail.thank_you') }}</p>
    </div>

    <div class="footer">
        <p>
            {{ __('mail.go_adventure') }}<br>
            <a href="{{ config('app.frontend_url') }}">{{ config('app.frontend_url') }}</a>
        </p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy">{{ __('mail.privacy_policy') }}</a> |
            <a href="{{ config('app.frontend_url') }}/terms">{{ __('mail.terms_of_service') }}</a>
        </p>
    </div>
</body>
</html>
