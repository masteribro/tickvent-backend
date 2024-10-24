<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        table {
            border-spacing: 0;
            width: 100%;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        /* Header styles */
        .email-header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }

        /* Body styles */
        .email-body {
            padding: 20px;
            color: #333333;
        }

        .email-body h2 {
            margin: 0;
            padding-bottom: 10px;
            font-size: 20px;
        }

        .email-body p {
            margin: 0;
            padding-bottom: 10px;
        }

        .email-body a {
            color: #4CAF50;
            text-decoration: none;
        }

        /* Button styles */
        .email-button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        /* Footer styles */
        .email-footer {
            background-color: #f4f4f4;
            color: #666666;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }

        /* Responsive styles */
        @media screen and (max-width: 600px) {
            .email-body h2 {
                font-size: 18px;
            }

            .email-header h1 {
                font-size: 20px;
            }

            .email-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" class="email-container">
        <!-- Email Header -->
        <tr>
            <td class="email-header">
                <h1>Welcome to Our Service!</h1>
            </td>
        </tr>

        <!-- Email Body -->
        <tr>
            <td class="email-body">
                <h2>Hello, {{ $user->first_name . " " . $user->last_name }}!</h2>
                <p>Thank you for joining our service. We are excited to have you on board. To get started, please confirm your email entering this one-time password.</p>
                <p>{{ $data['otp'] }}</p>
                <p>If you did not request for this, please kindly ignore email.</p>
            </td>
        </tr>

        <!-- Email Footer -->
        <tr>
            <td class="email-footer">
                <p>© {{ date('Y') }} Our Company. All rights reserved.</p>
                <p>If you have any questions, feel free to <a href="">contact us</a>.</p>
            </td>
        </tr>
    </table>
</body>
</html>
