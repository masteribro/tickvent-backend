<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class TicketService {

    public static function addTicketsToEvent($event, $payload)
    {
        try {
            if($payload["is_free"]) {
                return ['status' => true ];
            }
            collect($payload['tickets'])->map(function ($ticket) use ($event, $payload) {
                Ticket::updateOrCreate([
                    'event_id' => $event->id,
                    'slug' => str()->slug($ticket["type"], '-')
                ],[
                    'type' => $ticket["type"],
                    'price' => $ticket["price"],
                    'bank_account_id' => $payload['bank_account_id'],
                    'table_for' => $ticket["type"] === 'Regular' ? 1 : $ticket["table_for"]
                ]);
            });

            $event->update([
                "is_complete" => true,
                "is_free" => $payload["is_free"],
                "bank_account_id" => $payload['bank_account_id']
            ]);
            Log::warning("Done");

            return [ 'status' => true ];
        } catch (\Throwable $th) {
            Log::warning("Error in setting tickets",[
                "error" => $th
            ]);
        }
        return [ 'status' => false ];

    }

}
