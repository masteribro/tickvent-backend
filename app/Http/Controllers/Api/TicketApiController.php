<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Event;
use App\Models\EventInvitee;
use App\Models\PurchasedTicket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketApiController extends Controller
{
    public $ticketService;

    public function __construct()
    {
        $this->ticketService = (new TicketService);
    }
    public function addTickets(Request $request, $event_id)
    {
        try {
            $event = Event::where('id', $event_id)->first();
            if(!$event) {
                return ResponseHelper::errorResponse("Event does not exist",[], 404);
            }

                $validator = \Validator::make($request->all(),[
                    "is_free" => "required|boolean",
                    "bank_account_id" => ["required:is_free,false","integer","exists:bank_accounts,id", function ($attribute, $value, $fail) use($request) {
                        $bankAccount = BankAccount::find($value);
                        if($bankAccount->user_id !== $request->user()->id) {
                            $fail('Bank Account does not belong to user');
                        }
                    }],
                    "tickets" => "required_if:is_free,false|array",
                    "tickects.*.type" => "required|string|max:30",
                    "tickects.*.price" => "required|decimal:2",
                    "tickets.*.table_for" => "required|regex:/[0-9]{1,}/",
                ]);

                if($validator->fails()) {
                    return ResponseHelper::errorResponse("Validation error",$validator->errors());
                }

                $resp = $this->ticketService->addTicketsToEvent($event, $request->all());
                if(!$resp['status']) {
                    return ResponseHelper::errorResponse("Unable to add tickets to event",$validator->errors());
                }

            return ResponseHelper::successResponse("Tickets added successsfully");

        } catch (\Throwable $th) {
            Log::warning("Unable to add tickets to event", [
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to add tickets to event");
    }

    public function verifyTicket(Request $request, $purchase_ticket_id)
    {
        try {
            //code...
            $purchaseTicket = PurchasedTicket::find($purchase_ticket_id);

            if(!$purchaseTicket) {
                return ResponseHelper::errorResponse('Not found',[],404);
            }

            $resp = $this->ticketService->verifyTransaction($purchaseTicket);

            if($resp['status']) {
                return ResponseHelper::successResponse($resp['message'],$resp['data']);
            }

            return ResponseHelper::errorResponse($resp['message']);

        } catch (\Throwable $th) {
            Log::warning("error in verify the status of ticket", [
                'error' => $th
            ]);
        }
        return ResponseHelper::errorResponse('Unable to verify status of Ticket');
    }

    public function sendTicketInvite(Request $request, $purchase_ticket_id)
    {
        try {

        $purchaseTicket = PurchasedTicket::find($purchase_ticket_id);

        if(!$purchaseTicket) {
            return ResponseHelper::errorResponse("Ticket not found");
        }

        if($purchaseTicket && $purchaseTicket->user_id !== $request->user()->id ) {
            return ResponseHelper::errorResponse("Unauthorized");
        }

        if($purchaseTicket && $purchaseTicket->status !== 'paid') {
            return ResponseHelper::errorResponse("Ticket not paid");
        }

        if(!($purchaseTicket->invitations_sent < $purchaseTicket->invitations)) {
            return ResponseHelper::errorResponse("Invitations for this ticket have been exhausted");
        }

        $validator = \Validator::make($request->all(),[
            "email" => ['required', 'email', function ($attribute, $value, $fail) use ($request, $purchaseTicket) {
                $invitee = EventInvitee::where('email',$value)->where(
                    'purchased_ticket_id', $purchaseTicket->id
                )->exists();

                if($invitee) $fail('Invitation has already been sent to this email');
            }]
        ]);

        if($validator->fails()) {
            return ResponseHelper::errorResponse("Validation error",$validator->errors());
        }

        $resp = $this->ticketService->sendInvitation($request->email, $purchaseTicket);

            if($resp['status']) {
                return ResponseHelper::successResponse("Invitation has been sent");
            }
        } catch(\Throwable $th) {
            Log::warning("Error in Sending invitation on Ticket Controller",[
                'error' => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to send invitation, try again later");
    }

    public function updateTicketInvitation($purchase_ticket_id, $invitation_code)
    {
        try {
            $status = request('status');

            $resp = $this->ticketService->updateTicketInvitation($status, $purchase_ticket_id, $invitation_code);


            // return $resp;
            return view('events.update-event-invitation',[
                'status' => $resp['status'] === false ? "not-found" : $resp['data']->status,
                'invitee' => $resp['data'] ?? null
            ]);

        } catch (\Throwable $th) {

            Log::error('Error occurred: ', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
        }
        return view('events.update-event-invitation',[
            'status' => false,
            'invitee' => null
        ]);
    }

    public function getPurchasedTickets()
    {
        try {

            $user = request()->user();

            $purchaseTickets = PurchasedTicket::with([
                'invitees', 'event'
            ])->where('user_id', $user->id)->where('status', 'paid')->orderByDesc('created_at')->get();

            return ResponseHelper::successResponse('Booked Tickets', $purchaseTickets);

        } catch(\Throwable $th)
        {
            Log::error('Error occurred: ', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
        }

        return ResponseHelper::errorResponse('Something went wrong, please try again later');

    }
}
