<?php

namespace App\Console\Commands;

use App\Jobs\EventReminderNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEventReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickvent:send-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Event reminders to user';

    /**
     * Execute the console command.
     */
    public function handle()
    {

       Log::warning('message cron');


        EventReminderNotificationJob::dispatch();

        $this->info('Done');
    }
}
