<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.action_required') }}</title>
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
            background-color: #f59e0b;
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
        .listing-details {
            background-color: #ffffff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .errors-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .errors-box h3 {
            color: #dc2626;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .errors-box ul {
            margin: 0;
            padding-left: 20px;
            color: #991b1b;
        }
        .errors-box li {
            margin: 5px 0;
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
        <h1>{{ __('mail.action_required') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.dear') }} {{ $vendor->display_name ?? $vendor->name ?? __('mail.vendor') }},</p>

        <p>{{ __('mail.publish_failed_body') }}</p>

        <div class="listing-details">
            <h2 style="margin-top: 0; color: #0D642E;">{{ __('mail.listing_details') }}</h2>

            <div class="detail-row">
                <span class="label">{{ __('mail.title') }}:</span> {{ $listingTitle }}
            </div>

            @if($listing->location)
            <div class="detail-row">
                <span class="label">{{ __('mail.location') }}:</span> {{ $listing->location->name ?? __('mail.not_set') }}
            </div>
            @endif
        </div>

        <div class="errors-box">
            <h3>{{ __('mail.missing_info') }}</h3>
            <ul>
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>

        <p>{{ __('mail.update_listing') }}</p>

        <p style="text-align: center;">
            <a href="{{ $editUrl }}" class="button">{{ __('mail.edit_listing') }}</a>
        </p>

        <p>{{ __('mail.questions_contact') }}</p>
    </div>

    <div class="footer">
        <p>{{ __('mail.partner_thanks') }}</p>
        <p>&copy; {{ date('Y') }} {{ __('mail.go_adventure') }}. {{ __('mail.all_rights_reserved') }}</p>
    </div>
</body>
</html>
