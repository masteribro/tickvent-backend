<?php

namespace App\Services;

use App\Models\EventInvitee;
use App\Models\Ticket;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService {

    public $paymentService;

    public function __construct()
    {
        $this->paymentService = (new PaymentService());

    }

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

    public static function verifyTransaction($purchaseTicket)
    {
        try {

            $response = (new PaymentService)->verifyTransaction($purchaseTicket->reference);

            if($response['status']) {
                $data = $response["data"];

                if($data["status"] == 'success') {
                    $purchaseTicket->update([
                        'status' => 'paid'
                    ]);

                }

                $purchaseTicket->refresh();
            }

            return [
                'status' => true,
                "data" => $purchaseTicket,
                'message' => "Verification successful"
            ];

        } catch (\Throwable $th) {
            Log::warning("Error in verifying transaction in tickets",[
                'error' => $th
            ]);
        }

        return [
            'status' => false,
            'message' => "Verification unsuccessful"
        ];
    }

    public static function sendInvitation($invitee_email, $ticket_owner_id, $event_id, $purchase_ticket_id)
    {
        try {
            DB::beginTransaction();

            // Save Invites and send notification

            $invitee = EventInvitee::firstOrCreate([
                'email' => $invitee_email,
                'event_id' => $event_id,
                'user_id' => $ticket_owner_id,
                'purchased_ticket_id' => $purchase_ticket_id
            ]);

            $invitee->refresh();
            
            NotificationService::sendEventInvitation($invitee);

        }  catch (\Throwable $th) {
            DB::rollback();
            Log::warning('Error in creating invite and sending',[
                'error' => $th
            ]);
        }
    }
}
