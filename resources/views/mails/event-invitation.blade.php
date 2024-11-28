<!DOCTYPE html>
<html>
<head>
    <title>You're Invited! {{ env("APP_NAME") }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 24px;
            color: #0056b3;
        }
        p {
            font-size: 16px;
            margin: 10px 0;
        }

        .accept, .reject {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .reject {
            background-color: #ff3f3f;
        }
        .accept:hover {
            background-color: #218838;
        }
        .reject:hover {
            background-color: #fd2e2e;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>You're Invited to {{ $invitee->event->name }}!</h1>
        <p>{{ $invitee->user->name ?? $invitee->user->email }} has invited you to join them at <strong>{{ $invitee->event->name }}</strong>.</p>
        <p><strong>Event Details:</strong></p>
        <ul>
            @php
                $eventDetails = '';
                switch ($invitee->event->type) {
                    case 'physical':
                        $eventDetails = $invitee->event->location;
                        break;
                    case 'virtual':
                        $eventDetails = $invitee->event->stream_url;
                        break;
                    case 'hybrid':
                        $eventDetais = $invitee->event->location . ' or use streaming link ' . $invitee->event->stream_url;
                    default:
                        $eventDetails = '';
                }
            @endphp
            <li><strong>Event Invitation Code:</strong> {{ $invitee->code }}</li>
            <li><strong>Date:</strong> {{ $invitee->event->start_date }}</li>
            <li><strong>Time:</strong> {{ $invitee->event->start_time }}</li>
            <li><strong>Location:</strong> {{ $eventDetails }}</li>
        </ul>
        <div style="text-align: center">
            <a class="accept" href="{{ $invitee->invitation_url .'?status=accepted' }}">Accept</a>
            <a class="reject" href="{{ $invitee->invitation_url .'?status=rejected' }}">Reject</a>
        </div>
        <p>If you have any questions, feel free to reach out to .</p>
        <p class="footer">This invitation was sent on behalf of {{ $invitee->user->name }} via {{ env('APP_NAME ') }}.</p>
    </div>
</body>
</html>
