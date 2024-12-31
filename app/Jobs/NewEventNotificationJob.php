<?php

namespace App\Jobs;

use App\Mail\NewEventNotificationMail;
use App\Models\Event;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewEventNotificationJob implements ShouldQueue
{
    use Queueable;

    public $event;
    public $notificationService;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
        $this->notificationService =  new NotificationService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {


            $users = User::all();

            $usersEmails = $users->filter(function($user) {
                $notificationSettings = $this->notificationService->getNotifications($user);
                if($notificationSettings['email']->where('type', 'new-event')->where('value',true)->isNotEmpty()) {
                    return $user;
                }
            });

            $usersSms = $users->filter(function($user) {
                $notificationSettings = $this->notificationService->getNotifications($user);

                if($notificationSettings['sms']->where('type', 'new-event')->where('value',true)->isNotEmpty()) {
                    return $user;
                }
            });

            $this->sendNotification('email', $this->event, $usersEmails);
            $this->sendNotification('sms', $this->event, $usersSms);


            Log::warning('Notification sent for new event');


        } catch (\Throwable $th) {

            Log::warning('Error in Notification for new event',[
                'error' => $th
            ]);

            Log::warning('Email Unable to send notification');


        }
    }


    public function sendNotification($type, $event, $users)
    {

            if($type === 'email') {
                Mail::to($users->pluck('email'))->send(new NewEventNotificationMail($event));
            }
            // else if($type === 'sms') {
            //     (new SmsMessage)->sendBulk($attendees->pluck('phone_number'), env("APP_NAME"), "Message Here");
            // }

    }
}

