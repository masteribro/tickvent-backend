<?php 

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Notification;
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

    public function getNotifications($user)
    {
        $notification_types = ['happenning-around', 'new-event', 'ticket-gift', 'event-invite','login'];
        $channels = ['email', 'sms'];

        foreach($channels as $channel) {
            $notification_types = collect($notification_types);
            $notification_types->map(function ($item) use($user, $channel) {
                Notification::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        "channel" => $channel,
                        "type" => $item,
                    ],
                    [
                        "value" => true,
                    ]);
            });
            
        }
        return [
            "email" => Notification::where("channel", "email")->where("user_id", $user->id)->get(),
            "sms" => Notification::where("channel", "sms")->where("user_id", $user->id)->get()
        ];
    }
}