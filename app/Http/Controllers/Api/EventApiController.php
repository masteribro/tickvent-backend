<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\BreakLog;
use App\Models\EventOrganizer;
use App\Models\EventTag;
use App\Models\PurchasedTicket;
use App\Models\Ticket;
use App\Services\EventImageService;
use App\Services\Payment\PaymentService;
use App\Services\ReminderService;
use App\Services\TagsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\FuncCall;

class EventApiController extends Controller
{

    public function index(Request $request)
    {
        // comming  back to you
        try {

            $events = Event::where('is_complete', true);

            $filters = ["featured", "upcoming", "weekend"];
            $tags = $request->tags;
            $filter = $request->filter;


            if(isset($filter)) {

                if(!in_array($filter, $filters)) {
                    return ResponseHelper::errorResponse("Unknown Filter");
                }

                switch ($filter) {
                    case 'featured': {
                        $events = $events->where('featured', true);
                        break;
                    }

                    case 'upcoming': {
                        $currentDate = Carbon::now()->format("Y-m-d");
                        $commingWeeks = Carbon::now()->addWeeks(3)->format('Y-m-d');

                        $events = $events->whereBetween("start_date",[$currentDate, $commingWeeks]);

                        break;
                    }

                    case 'weekend': {
                        $weekend[] = Carbon::now()->next(Carbon::FRIDAY)->format('Y-m-d');
                        $weekend[] = Carbon::now()->next(Carbon::SATURDAY)->format('Y-m-d');
                        $weekend[] = Carbon::now()->next(Carbon::SUNDAY)->format('Y-m-d');

                        $events = $events->whereIn("start_date", $weekend);
                        break;
                    }

                    default:
                        $events = $events;
                    break;
                }
            } else {
                $events = $events->where("tags", "LIKE" , '%' . $tags . '%');
            }

            return ResponseHelper::successResponse("Events retrived successfully",$events->get());


     } catch(\Throwable $throwable) {
            Log::warning("Getting Events Error",[
                "" => $throwable
            ]);
        }
        return ResponseHelper::errorResponse("Unable to process event");
    }


    public function createEvent(Request $request)
    {
        try {
                $user = auth("sanctum")->user();

                $validator = \Validator::make($request->all(),[
                    "name" => "required|string",
                    "is_free" => "required|boolean",
                    "description" => "sometimes|nullable|string",
                    "start_date" => ["required","date_format:Y-m-d", function ($attribute, $value, Closure $fail){
                        if($value < Carbon::now()->format('Y-m-d')) {
                            $fail("Date must be a future date");
                        }
                    }], // There is a validation of date
                    "end_date" => "sometimes|date_format:Y-m-d|after_or_equal:start_date",
                    "start_time" => ['required', 'date_format:H:i:s'],
                    "end_time" => ["nullable", 'date_format:H:i:s', function($attribute, $value, Closure $fail) use ($request) {
                        if($request->end_date == $request->start_date) {
                            if($request->start_time > $value) {
                               Log::warning($request->start_time);
                                $fail("Invalid time");
                            }
                        }
                    }],
                    "type" => "required|in:physical,hybrid,virtual",
                    "location" => "required_if:type,physical,hybrid",
                    "reminders" => "sometimes|array",
                    "reminders.*" =>[ "required_if:reminders,array","date_format:Y-m-d" ,"before_or_equal:start_date", function ($attribute, $value, Closure $fail){
                        if($value < Carbon::now()->format('Y-m-d')) {
                            $fail("Date must be between today and the date of the event date");
                        }
                    } ],
                    "tags" => "nullable|array",
                    "tags.*" => "required|string",
                    "images" => "nullable|array",
                    "images.*" => "required|mimes:jpeg,png,svg,gif,mp4,webm,avi,avchd,mkv,wmv"
                ]);

                if($validator->fails()) {
                    return ResponseHelper::errorResponse("Validation Error", $validator->errors());
                }



                $event = Event::firstOrcreate([
                    "user_id" => $user->id,
                    "name" => strtolower($request->name),
                    "description" => $request->description ?? null,
                    "start_date" => $request->start_date,
                    "end_date" => $request->end_date,
                    "slug" => Str::slug($request->name, '-'),
                    "start_time" => $request->start_time,
                    "end_time" => $request->endtime,
                    "type" => $request->type,
                    "location" => $request->location,
                    "is_free" => $request->is_free
                ], [
                    "user_id" => $user->id,
                    "name" => strtolower($request->name),
                    "description" => $request->description ?? null,
                    "start_date" => $request->start_date,
                    "end_date" => $request->end_date,
                    "slug" => Str::slug($request->name, '-'),
                    "start_time" => $request->start_time,
                    "end_time" => $request->endtime,
                    "type" => $request->type,
                    "location" => $request->location,
                    "is_free" => $request->is_free
                ]);

                $images_or_video = EventImageService::saveImages($event, $request->images ?? []);

                $reminders = ReminderService::setReminders($event, $request->reminders ?? []);

                $tags = TagsService::createTag($event, $request->tags ?? []);

                return ResponseHelper::successResponse("Event created successfully", $event, 201);

            } catch(\Throwable $throwable) {
                Log::warning("Creating Events Error",[
                    "" => $throwable
                ]);
            }

        return ResponseHelper::errorResponse("Unable to create event");
    }
    public function getEvent(Request $request, $idOrSlug)
    {
        try {
            $event = Event::with("tickets")->where("slug", $idOrSlug)->orWhere('id', $idOrSlug)->first();

            if($event) {
                return ResponseHelper::successResponse("Event retrieved successfully", [
                "event" => $event,
                "organizer" => $event->user->select("organizer_name", "organizer_info")->get()
            ]);
            }
            return ResponseHelper::errorResponse("Event not found");

        } catch (\Throwable $throwable) {
            Log::warning("Show specific events", [
                "get event error" => $throwable
            ]);
        }

        return ResponseHelper::errorResponse("Unable to process request");
    }


    public function addTickets(Request $request)
    {
        try {

            $validator = \Validator::make($request->all(),[
                'event_id' => 'required|exists:events,id',
                'is_free' => 'required|boolean',
                'tickets' => 'required_if:is_free,false|array',
                'tickets.*.type' => 'required|string',
                'tickets.*.price' => 'required|decimal:2|string',
                'tickets.*.persons_per_section' => 'required|decimal:2|string',
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            $event = Event::find($request->event_id);

            if($request->is_free) {
                $event->is_free = true;
            } else {
                $tickets = collect($request->tickets);
                $tickets->each(function ($ticket) use ($request) {
                    Ticket::create([
                        'event_id' => $request->event_id,
                        'type' => $ticket["type"],
                        'slug' => Str::slug($ticket["type"]),
                        'price' => $ticket["price"],
                        'persons_per_section' => $ticket["persons_per_section"]
                    ]);
                    Log::warning("Tickets created");
                });
            }

            $event->is_complete = true;
            $event->save();

            return ResponseHelper::successResponse("Tickets for the events created");

            } catch(\Throwable $throwable) {
                Log::warning("Creating tickets error", [
                    "error" => $throwable
                ]);
            }

        return ResponseHelper::errorResponse("Unable to add tickets, try again later");
    }

    public function bookEvent(Request $request, $event_id)
    {
        try {

            $validator = \Validator::make($request->all(),[
                'ticket_id' => ['required', 'bail','exists:tickets,id' ,function($attribute, $value, $fail) use($event_id) {
                        $ticket = Ticket::where('id', $value)->first();

                        if($ticket->event_id != $event_id) {
                            $fail('wrong ticket id');
                        }
                }],
                'quantity' => 'required|integer'
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            $resp = $this->purchaseTicket($request,$event_id);

            if($resp['status']) {
                return ResponseHelper::successResponse("Event ticket checkout",$resp);
            }

        } catch (\Throwable $th) {
            Log::warning("error in ordering ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to book");
    }

    private function purchaseTicket($request, $event_id) {
        try {
            DB::beginTransaction();

            $ticket = Ticket::where('id', $request->ticket_id)->first();

            $payload = $request->all();
            $payload['user_id'] = auth('sanctum')->user()->id;
            $payload['event_id'] = $event_id;
            $payload['invitations'] = $ticket->table_for * $payload['quantity'];

            $payload['reference'] = "TKVT" . time();

            PurchasedTicket::create($payload);

            $payload['payment_for'] = 'book_ticket';
            $payload['subaccount'] = $ticket->account->split_code;
            $payload['amount'] = $ticket->price * $payload['quantity'];
            $payload['email'] = auth('sanctum')->user()->email;
            // $payload['verification_url'] = ""


            $resp = (new PaymentService)->generatePaymentUrl($payload);

            if($resp['status'])  {
                DB::commit();
                return [
                    'status' => true,
                    'data' => [
                        'checkout_url' => $resp['data']['authorization_url']
                    ]
                ];
            }

        } catch(\Throwable $th) {
            DB::rollBack();
            Log::warning('Error in setting up purchase tickvent',[
                'error' => $th
            ]);
        }

        return [
            'status' => false,
        ];
    }

    public function addBreakLog(Request $request)
    {
        try {

            $validator = \Validator::make($request->all(),[
                'event_id' => ['required', 'exists:events,id'],
                'reason' => ['required','string'],
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            BreakLog::create([
                'attendee' => auth()->user()->id,
                'reason' => request('reason'),
                'event_id' => request('event_id'),
                'break_time' => Carbon::now()
            ]);

            return ResponseHelper::successResponse("Break Logged successfully");

        } catch (\Throwable $th) {
            Log::warning('Error in adding logs', [
                'error' => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to log activity");
    }
}
