<?php 

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class NotificationService {

    public static function send($user, $data, $type) {
        if($data["notification_type"] == "email") {
            if($type == 'otp') {
                Mail::to($user)->send(new OtpMail($data, $user));
            }
        } else if ($data["notification_type"] == "sms") {
            (new SmsMessage)->send($user->phone_number, env("APP_NAME"), $data["body"]);
        }
    }
}