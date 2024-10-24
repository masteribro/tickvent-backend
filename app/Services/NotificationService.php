<?php 

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class NotificationService {

    public static function send($user, $data, $type) {
        if($type == 'otp') {
            Mail::to($user)->send(new OtpMail($data, $user));
        }
    }
}