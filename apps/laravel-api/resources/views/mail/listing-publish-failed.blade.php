<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Required: Listing Cannot Be Published</title>
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
        <h1>Action Required</h1>
    </div>

    <div class="content">
        <p>Dear {{ $vendor->display_name ?? $vendor->name ?? 'Vendor' }},</p>

        <p>Our team tried to publish your listing but couldn't complete the action because some required information is missing.</p>

        <div class="listing-details">
            <h2 style="margin-top: 0; color: #0D642E;">Listing Details</h2>

            <div class="detail-row">
                <span class="label">Title:</span> {{ $listingTitle }}
            </div>

            @if($listing->location)
            <div class="detail-row">
                <span class="label">Location:</span> {{ $listing->location->name ?? 'Not set' }}
            </div>
            @endif
        </div>

        <div class="errors-box">
            <h3>Missing Information</h3>
            <ul>
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>

        <p>Please update your listing with the required information so we can publish it and make it visible to travelers.</p>

        <p style="text-align: center;">
            <a href="{{ $editUrl }}" class="button">Edit Your Listing</a>
        </p>

        <p>If you have any questions, please don't hesitate to contact our support team.</p>
    </div>

    <div class="footer">
        <p>Thank you for being a Go Adventure partner!</p>
        <p>&copy; {{ date('Y') }} Go Adventure. All rights reserved.</p>
    </div>
</body>
</html>
