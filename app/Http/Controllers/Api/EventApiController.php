<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\EventOrganizer;
use App\Models\EventTag;
use App\Models\Ticket;
use App\Services\EventImageService;
use App\Services\ReminderService;
use App\Services\TagsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Str;

class EventApiController extends Controller
{
   
    public function index(Request $request)
    {
        // comming  back to you 
        try {
            
            $tags = $request->tags ?? [];
            $date = $request->date ?? "";
            $slug = $request->slug ?? "";
            
            $events = Event::where('is_complete', true);

            if(!empty($tags)) {
                $event_tags = collect(EventTag::whereIn("slug",$tags)->select(['event_id'])->get())->values();

                $events = $events->whereIn("id", $event_tags);
            }

            if($date) {
                $events = $events->orWhere('start_date', $date);
            }

            if($slug) {
                $events = $events->orWhere('slug', $slug);
            }
            

            return ResponseHelper::successResponse("Message",$events->get());

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
                    "images.*" => "required|string"
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

    public function addOrganizer(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(),[
                'event_id' => 'required|exists:events,id',
                'name' => ["required","string"],
                'organizer_info' => "nullable|string",
                'email' => "nullable|email",
                'phone_number' => "nullable|array",
                'phone_number.*' => "required|string|regex:/0[7-9][0-1]\d{8}/"
            ]);
    
            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }
    
            $data = $request->all();
    
            $organizer = EventOrganizer::updateOrCreate([
                "name" => strtolower($data["name"]),
            ],[
                "organizer_info" => $data["organizer_info"],
                "email" => $data["email"] ?? null,
                "phone_number" => json_encode($data["phone_number"] ?? null),
            ]);
    
            $event = Event::find($data["event_id"]);
    
            $event->update([
                "organizer_id" => $organizer->id
            ]);

            return ResponseHelper::successResponse("Organizer added successfully");

        } catch(\Throwable $throwable) {
            Log::warning("Adding Organizer info", [
                "error" => $throwable
            ]);
        }

        return ResponseHelper::errorResponse("Unable to add organizer");


    }

    
    public function getEvent(Request $request, $idOrSlug)
    {
        try {
            $event = Event::where("slug", $idOrSlug)->orWhere('id', $idOrSlug)->first();

            if($event) {
                return ResponseHelper::successResponse("Event retrieved successfully", $event);
            } else {
                return ResponseHelper::errorResponse("Event not found");
            }
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

    
    public function destroy(Request $request, Event $event)
    {
        
    }
}
