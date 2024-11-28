<!DOCTYPE html>
<html>
<head>
    <title>Event Status</title>
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
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .accepted {
            color: #fff;
            background-color: #28a745;
        }
        .rejected {
            color: #fff;
            background-color: #dc3545;
        }
        .not-found {
            color: #fff;
            background-color: #6c757d;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Accepted Status -->
        <div id="accepted" style="display: none;">
            <h1>Event Accepted</h1>
            <p>Thank you for accepting the invitation to <strong>{{ $invitee->event->name ?? null }}</strong>.</p>
            <p>We’re excited to have you join us!</p>
            <span class="status accepted">Accepted</span>
        </div>

        <!-- Rejected Status -->
        <div id="rejected" style="display: none;">
            <h1>Event Rejected</h1>
            <p>You have declined the invitation to <strong>{{ $invitee->event->name ?? null }}</strong>.</p>
            <p>We’re sorry to miss you, but thank you for letting us know.</p>
            <span class="status rejected">Rejected</span>
        </div>

        <!-- Not Found Status -->
        <div id="not-found" style="display: none;">
            <h1>Event Not Found</h1>
            <p>We couldnt find the event you were looking for. It may have been removed or does not exist.</p>
            <span class="status not-found">Not Found</span>
        </div>

        <div class="footer">
            <p>If you have any questions, please contact us at [Contact Email].</p>
        </div>
    </div>

    <!-- Script to Handle Dynamic Display -->
    <script>
        // Simulate dynamic status (replace with server-side logic)
        const status = '<?php echo $status ?>'; // Change to "rejected" or "not-found" to test
        console.log(status);
        document.getElementById(status)?.style.setProperty('display', 'block');
    </script>
</body>
</html>
