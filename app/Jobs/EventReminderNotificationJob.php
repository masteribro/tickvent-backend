<?php

namespace App\Jobs;

use App\Mail\EventReminderMail;
use App\Models\EventReminder;
use App\Models\PurchasedTicket;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmsMessage;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EventReminderNotificationJob implements ShouldQueue
{
    use Queueable;

    public $notificationService;

    /**
     *
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // first get the event reminders,
            Log::info('TestJob executed successfully!');

            $eventReminders = EventReminder::where('reminder_date', Carbon::now()->format('Y-m-d'))->where('reminder_sent', 0)->get();

            foreach($eventReminders as $reminder) {
                $this->sendReminder($reminder);

            };


        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function sendReminder($reminder)
    {
        try {

            $attendeesId = PurchasedTicket::where('event_id', $reminder->event_id)->where('status', 'paid')->pluck('user_id');

            Log::warning("Attendees",[
                'Users' => $attendeesId
            ]);

            $attendees = User::whereIn('id', $attendeesId)->get();

            $attendeesEmails = $attendees->filter(function($attendee) {
                $notificationSettings = $this->notificationService->getNotifications($attendee);

                if($notificationSettings['email']->where('type', 'event-reminder') == true) {
                    return $attendee;
                }
            });

            $attendeesSms = $attendees->filter(function($attendee) {
                $notificationSettings = $this->notificationService->getNotifications($attendee);

                if($notificationSettings['sms']->where('type', 'event-reminder') == true) {
                    return $attendee;
                }
            });

            $this->sendNotification('email', $reminder, $attendeesEmails);
            $this->sendNotification('sms', $reminder, $attendeesSms);

            return true;

        } catch (\Throwable $th) {

            Log::warning('Error in sendReminder',[
                'error' => $th
            ]);

        }

        return false;

    }

    public function sendNotification($type, $reminder, $attendees)
    {
        if($type === 'email') {
            Mail::to($attendees->pluck('email'))->send(new EventReminderMail($reminder->event));
        }
        // else if($type === 'sms') {
        //     (new SmsMessage)->sendBulk($attendees->pluck('phone_number'), env("APP_NAME"), "Message Here");
        // }

        $reminder->update([
            'reminder_sent' => 1
        ]);
    }
}
