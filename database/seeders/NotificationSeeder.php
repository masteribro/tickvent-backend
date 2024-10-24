<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Notification::factory()->create([
        ]);
        Notification::factory()->create([
            "type" => "happening-around"
        ]);
        Notification::factory()->create([
            "type" => "new-event"
        ]);
        Notification::factory()->create([
            "type" => "event-invite"
        ]);
        Notification::factory()->create([
            "type" => "ticket-gift"
        ]);
        

        Notification::factory()->create([
            "channel" => "sms"
        ]);
        Notification::factory()->create([
            "channel" => "sms",
            "type" => "happening-around"
        ]);
        Notification::factory()->create([
            "channel" => "sms",
            "type" => "new-event"
        ]);
        Notification::factory()->create([
            "channel" => "sms",
            "type" => "event-invite"
        ]);
        Notification::factory()->create([
            "channel" => "sms",
            "type" => "ticket-gift"
        ]);
    }
}
