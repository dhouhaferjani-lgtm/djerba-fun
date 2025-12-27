<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voucher - {{ $participant->voucher_code }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12pt;
        }
        .voucher {
            width: 100%;
            height: 100%;
            padding: 30px;
            box-sizing: border-box;
            position: relative;
        }
        .header {
            background: linear-gradient(135deg, {{ $colors['primary'] }} 0%, {{ $colors['accent'] }} 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24pt;
        }
        .header p {
            margin: 0;
            font-size: 14pt;
        }
        .content {
            padding: 20px;
        }
        .section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: {{ $colors['cream'] }};
            border-left: 4px solid {{ $colors['accent'] }};
            border-radius: 4px;
        }
        .section h2 {
            margin: 0 0 15px 0;
            color: {{ $colors['primary'] }};
            font-size: 16pt;
        }
        .detail-row {
            margin: 8px 0;
            display: table;
            width: 100%;
        }
        .detail-label {
            font-weight: bold;
            color: {{ $colors['primary'] }};
            display: table-cell;
            width: 150px;
        }
        .detail-value {
            display: table-cell;
        }
        .qr-container {
            text-align: center;
            padding: 20px;
            background: white;
            border: 2px solid {{ $colors['primary'] }};
            border-radius: 8px;
        }
        .qr-code {
            width: 200px;
            height: 200px;
        }
        .voucher-code {
            font-size: 18pt;
            font-weight: bold;
            color: {{ $colors['primary'] }};
            margin-top: 10px;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }
        @if($listing->isEvent() && $participant->badge_number)
        .badge-number {
            position: absolute;
            top: 30px;
            right: 30px;
            background: {{ $colors['accent'] }};
            color: white;
            font-size: 48pt;
            font-weight: bold;
            padding: 20px 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 1;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @endif
        .footer {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="voucher">
        @if($listing->isEvent() && $participant->badge_number)
        <div class="badge-number">
            {{ $participant->badge_number }}
        </div>
        @endif

        <div class="header">
            <h1>{{ $listing->getTranslation('title', 'en') }}</h1>
            <p>{{ $listing->isEvent() ? 'Event' : 'Tour' }} Voucher</p>
        </div>

        <div class="content">
            <div class="section">
                <h2>Participant Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $participant->full_name ?? 'Not provided' }}</span>
                </div>
                @if($listing->isEvent() && $participant->badge_number)
                <div class="detail-row">
                    <span class="detail-label">Badge Number:</span>
                    <span class="detail-value" style="font-size: 16pt; font-weight: bold; color: {{ $colors['accent'] }};">
                        #{{ $participant->badge_number }}
                    </span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">{{ ucfirst($participant->person_type ?? 'Adult') }}</span>
                </div>
            </div>

            <div class="section">
                <h2>{{ $listing->isEvent() ? 'Event' : 'Tour' }} Details</h2>
                @if($slot)
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $slot->date->format('l, F j, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">{{ $slot->start_time->format('g:i A') }}</span>
                </div>
                @endif
                @if($listing->meeting_point && isset($listing->meeting_point['address']))
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value">{{ $listing->meeting_point['address'] }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Booking:</span>
                    <span class="detail-value">{{ $booking->booking_number }}</span>
                </div>
            </div>

            <div class="qr-container">
                <img src="{{ $qrCode }}" alt="QR Code" class="qr-code">
                <div class="voucher-code">{{ $participant->voucher_code }}</div>
                <p style="margin: 10px 0 0 0; font-size: 10pt; color: #666;">
                    Scan this code at check-in
                </p>
            </div>
        </div>

        <div class="footer">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $platformName }}" style="height: 30px; margin-bottom: 10px;">
            @endif
            <p>
                <strong>{{ $platformName }}</strong><br>
                Generated: {{ now()->format('F j, Y \a\t g:i A') }}
            </p>
        </div>
    </div>
</body>
</html>
