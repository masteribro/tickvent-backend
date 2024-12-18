<!DOCTYPE html>
<html>
<head>
    <title>Event Reminder</title>
</head>
<body>
    <h1>Reminder: {{ $event->name }}</h1>
    <p>Hi there!</p>
    <p>This is a friendly reminder about your upcoming event to attend:</p>
    @php
                $eventDetails = '';
                switch ($event->type) {
                    case 'physical':
                        $eventDetails = $event->location;
                        break;
                    case 'virtual':
                        $eventDetails = $event->stream_url;
                        break;
                    case 'hybrid':
                        $eventDetais = $event->location . ' or use streaming link ' . $invitee->event->stream_url;
                    default:
                        $eventDetails = '';
                }
            @endphp
    <ul>
        <li><strong>Name:</strong> {{ $event->name }}</li>
        <li><strong>Date:</strong> {{ \Carbon\Carbon::parse($event->start_date)->format('F j, Y') }}</li>
        <li><strong>Time:</strong> {{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}</li>
        <li><strong>Location:</strong> {{ $eventDetails }}</li>
    </ul>
    <p>We look forward to seeing you there!</p>
    <p>Best regards,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
