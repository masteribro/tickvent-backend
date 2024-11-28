<?php

namespace App\Services;

use App\Jobs\EventInvitation;
use App\Jobs\EventInvitationJob;
use App\Mail\OtpMail;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
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

                    ]);
            });

        }
        return [
            "email" => Notification::where("channel", "email")->where("user_id", $user->id)->get(),
            "sms" => Notification::where("channel", "sms")->where("user_id", $user->id)->get()
        ];
    }

    public function updateNotifications(array $notifications, $user)
    {
        try {
            $notifications = array_map(function ($item) use($user) {
                $item["user_id"] = $user->id;
                return $item;
            }, $notifications);


            collect($notifications)->map(function ($notification) {
                Notification::updateOrCreate([
                    'user_id' => $notification["user_id"],
                    'channel' => $notification["channel"],
                    'type' => $notification["type"],
                ], [
                    'value' => $notification["value"]
                ]);
            });

            dd("done");
            return [
                "status" => true
            ];
        } catch(\Throwable $th) {
            Log::warning("Update Notification Error",[
                "error" => $th
            ]);
        }
        return [
            "status" => false
        ];
    }

    public static function sendEventInvitation($invitee)
    {
        EventInvitationJob::dispatch($invitee);
    }
}
