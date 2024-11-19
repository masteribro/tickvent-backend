<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Event;
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
                    "bank_account_id" => "required|integer",
                    "is_free" => "required|boolean",
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

    public function verifyTicket(Request $request, $event_id)
    {

    }

    public
}
