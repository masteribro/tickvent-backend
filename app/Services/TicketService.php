<?php

namespace App\Services;

use App\Models\EventInvitee;
use App\Models\PurchasedTicket;
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

    public static function sendInvitation($invitee_email, PurchasedTicket $purchasedTicket)
    {
        try {
            DB::beginTransaction();

            // Save Invites and send notification

            $invitee = EventInvitee::firstOrCreate([
                'email' => $invitee_email,
                'event_id' => $purchasedTicket->event_id,
                'user_id' => $purchasedTicket->user_id,
                'purchased_ticket_id' => $purchasedTicket->id,
                'code' => 'TKVT' . time()
            ]);
            $invitee->update([
                'invitation_url' => route('events.invitation_url',[
                    'purchased_ticket_id' => $invitee->purchased_ticket_id,
                    'invitation_code' => $invitee->code
                    ])
            ]);

            $invitee->refresh();

            NotificationService::sendEventInvitation($invitee);

            DB::commit();

            return [
                'status' => true
            ];

        }  catch (\Throwable $th) {
            DB::rollback();
            Log::warning('Error in creating invite and sending',[
                'error' => $th
            ]);
        }

        return [
            'status' => false
        ];
    }

    public function updateTicketInvitation($status, $purchase_ticket_id, $invitation_code) {
        try {
            DB::beginTransaction();
            $invitee = EventInvitee::where('code', $invitation_code)->first();

            if(!$invitee) {
                return [
                    'status' => false,
                    'message' => "Wrong Invitation Code"
                ];
            }
            if($invitee->status === 'pending') {
                $invitee->update([
                    'status' => $status
                ]);
            }
                $invitee->refresh();
            DB::commit();
            return [
                'status' => true,
                "data" => $invitee
            ];
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Error occurred: ', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

        }

        return [
            'status' => false,
            'message' => "Something went wrong, try again later"
        ];
    }
}
