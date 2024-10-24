<?php 

namespace App\Services;

use App\Models\Event;
use App\Models\EventReminder;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReminderService {
    public static function setReminders(Event $event, array $dates) {
        try {

            foreach($dates as $date) {
                EventReminder::create([
                    'event_id' => $event->id,
                    'reminder_date' => $date,
                ]);
            }
            Log::warning("Done setting reminders");
        } catch(\Throwable $throwable) 
        {
           Log::warning('Reminder error', [
                "error" => $throwable
           ]);
        }
    }
}

?>