<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.magic_login_header') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #0D642E;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            margin: 0 0 15px 0;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background-color: #0D642E;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .button:hover {
            background-color: #094d22;
        }
        .security-notice {
            background-color: #f5f0d1;
            border-left: 4px solid #8BC34A;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9em;
            border-top: 1px solid #eee;
        }
        .expiration {
            color: #d32f2f;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('mail.magic_login_header') }}</h1>
        </div>

        <div class="content">
            <p>{{ __('mail.hello') }}{{ $user->first_name ? ', ' . $user->first_name : '' }}!</p>

            <p>{{ __('mail.magic_login_body') }}</p>

            <div class="button-container">
                <a href="{{ $magicLink }}" class="button">{{ __('mail.log_in_now') }}</a>
            </div>

            <p>{{ __('mail.copy_paste_link') }}</p>
            <p style="word-break: break-all; color: #0D642E; font-size: 14px;">
                {{ $magicLink }}
            </p>

            <div class="security-notice">
                <p style="margin: 0;"><strong>{{ __('mail.security_notice') }}</strong></p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>{{ __('mail.link_expires_minutes', ['minutes' => $expiresInMinutes]) }}</li>
                    <li>{{ __('mail.link_one_use') }}</li>
                    <li>{{ __('mail.didnt_request') }}</li>
                </ul>
            </div>

            <p>{{ __('mail.after_login') }}</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ __('mail.brand_name') }}. {{ __('mail.all_rights_reserved') }}</p>
            <p>{{ __('mail.automated_message') }}</p>
        </div>
    </div>
</body>
</html>
