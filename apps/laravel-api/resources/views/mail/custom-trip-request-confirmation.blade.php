<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.custom_trip_header') }}</title>
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
        <h1>{{ __('mail.custom_trip_header') }}</h1>
    </div>

    <div class="content">
        <p>{{ __('mail.dear') }} {{ $contactName }},</p>

        <p>{{ __('mail.custom_trip_intro') }}</p>

        <div class="reference-box">
            <p style="margin: 0 0 5px 0; color: #666;">{{ __('mail.reference_number') }}</p>
            <div class="reference-number">{{ $reference }}</div>
            <p style="margin: 10px 0 0 0; font-size: 0.85em; color: #666;">{{ __('mail.save_reference') }}</p>
        </div>

        <div class="request-details">
            <h2 style="margin-top: 0; color: #0D642E;">{{ __('mail.trip_summary') }}</h2>

            <div class="detail-row">
                <span class="label">{{ __('mail.travel_dates') }}:</span> {{ $travelDates }}
                @if($datesFlexible)
                    <span style="color: #666;">({{ __('mail.flexible') }})</span>
                @endif
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.duration') }}:</span> {{ trans_choice('mail.days', $durationDays, ['count' => $durationDays]) }}
            </div>

            <div class="detail-row">
                <span class="label">{{ __('mail.travelers_label') }}:</span> {{ $travelerSummary }}
            </div>

            @if($interests)
            <div class="detail-row">
                <span class="label">{{ __('mail.interests') }}:</span> {{ $interests }}
            </div>
            @endif

            <div class="detail-row">
                <span class="label">{{ __('mail.budget') }}:</span> {{ $budgetCurrency }} {{ number_format($budget, 0) }} {{ __('mail.per_person') }}
            </div>

            @if($accommodationStyle)
            <div class="detail-row">
                <span class="label">{{ __('mail.accommodation_style') }}:</span> {{ $accommodationStyle }}
            </div>
            @endif

            @if($travelPace)
            <div class="detail-row">
                <span class="label">{{ __('mail.travel_pace') }}:</span> {{ $travelPace }}
            </div>
            @endif

            @if($specialRequests)
            <div class="detail-row">
                <span class="label">{{ __('mail.special_requests') }}:</span><br>
                <span style="color: #666;">{{ $specialRequests }}</span>
            </div>
            @endif
        </div>

        <div class="next-steps">
            <h3 style="margin-top: 0; color: #0D642E;">{{ __('mail.what_happens_next_title') }}</h3>
            <ol style="margin: 0; padding-left: 20px;">
                <li style="margin-bottom: 10px;">{{ __('mail.step_review') }}</li>
                <li style="margin-bottom: 10px;">{{ __('mail.step_contact') }}</li>
                <li style="margin-bottom: 10px;">{{ __('mail.step_proposal') }}</li>
                <li>{{ __('mail.step_finalize') }}</li>
            </ol>
        </div>

        <div class="contact-info">
            <h3 style="margin-top: 0; color: #0D642E;">{{ __('mail.reach_sooner') }}</h3>
            <p style="margin-bottom: 10px;">{{ __('mail.urgent_questions') }}</p>
            <p style="margin: 5px 0;">
                <strong>Email:</strong> <a href="mailto:contact@djerba.fun" style="color: #0D642E;">contact@djerba.fun</a>
            </p>
            <p style="margin: 5px 0;">
                <strong>WhatsApp:</strong> <a href="https://wa.me/21612345678" style="color: #0D642E;">+216 12 345 678</a>
            </p>
            <p style="margin: 5px 0; font-size: 0.9em; color: #666;">
                {{ __('mail.include_reference', ['reference' => $reference]) }}
            </p>
        </div>

        <p>{{ __('mail.discover_tunisia') }}</p>

        <p>{{ __('mail.warm_regards') }}<br>
        <strong>{{ __('mail.the_team') }}</strong></p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ __('mail.brand_name') }}. {{ __('mail.all_rights_reserved') }}</p>
        <p>
            <a href="{{ config('app.frontend_url') }}/privacy" style="color: #0D642E;">{{ __('mail.privacy_policy') }}</a> |
            <a href="{{ config('app.frontend_url') }}/terms" style="color: #0D642E;">{{ __('mail.terms_of_service') }}</a>
        </p>
        <p style="font-size: 0.85em;">
            {{ __('mail.receiving_because_trip') }}
        </p>
    </div>
</body>
</html>
