<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Magic Login Link</title>
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
            <h1>🔐 Your Magic Login Link</h1>
        </div>

        <div class="content">
            <p>Hello{{ $user->first_name ? ', ' . $user->first_name : '' }}!</p>

            <p>You requested a magic link to log in to your Go Adventure account. Click the button below to log in instantly:</p>

            <div class="button-container">
                <a href="{{ $magicLink }}" class="button">Log In Now</a>
            </div>

            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #0D642E; font-size: 14px;">
                {{ $magicLink }}
            </p>

            <div class="security-notice">
                <p style="margin: 0;"><strong>⚠️ Security Notice:</strong></p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>This link expires in <span class="expiration">{{ $expiresInMinutes }} minutes</span></li>
                    <li>This link can only be used once</li>
                    <li>If you didn't request this, you can safely ignore this email</li>
                </ul>
            </div>

            <p>After logging in, you can access your bookings, update your profile, and more.</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Go Adventure. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
