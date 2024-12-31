<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Event Announcement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #007BFF;
        }
        .content {
            padding: 20px 0;
        }
        .content h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        .cta-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            color: #fff;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .cta-button:hover {
            background-color: #0056b3;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 New Event Announcement!</h1>
        </div>
        <div class="content">
            <h2>Hello Dear,</h2>
            <p>We're thrilled to announce a brand-new event hosted by {{ $event->user->organization_name }}! Here are the details:</p>
            <ul>
                <li><strong>Event Name:</strong> {{ $event->name }}</li>
                <li><strong>Date:</strong> {{ Carbon\Carbon::parse($event->start_date)->format('F j, Y') }}</li>
                <li><strong>Time:</strong> {{ $event->start_time }}</li>
                <li><strong>Location:</strong> {{ $event->location }}</li>
            </ul>
            <p>This is an opportunity you won't want to miss! Secure your spot now and join us for an unforgettable experience.</p>
            {{-- <a href="{{ $event->registration_link }}" class="cta-button">Register Now</a> --}}
        </div>
        {{-- <div class="footer">
            <p>If you have any questions, feel free to reach out to us at <a href="mailto:support@{{ config('app.url') }}">support@{{ config('app.url') }}</a>.</p>
            <p>Thank you for being a valued member of our community.</p>
        </div> --}}
    </div>
</body>
</html>
