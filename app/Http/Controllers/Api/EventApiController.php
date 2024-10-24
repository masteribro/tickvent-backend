<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EventImageService;
use App\Services\ReminderService;
use App\Services\TagsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Str;

class EventApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // comming  back to you
    }

    /**
     * Show the form for creating a new resource.
     */
    public function createEvent(Request $request)
    {
        try {
                $user = auth('sanctum')->user();

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

    /**
     * Store a newly created resource in storage.
     */
    public function addOrganizer(Request $request)
    {
        $validator = \Validator::make($request->all(),[
            'event_id' => 'required|exists:events,id',
            'name' => "required|string",
            'organizer_info' => "required|string",
            'email' => "nullable|email",
            'phone_number' => "nullable|array",
            'phone_number.*' => "required|string|regex:/0[7-9][0-1]\d{8}/"
        ]);

        if($validator->fails()) {
            return ResponseHelper::errorResponse("Validation Error", $validator->errors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Event $event)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function addTicket(Request $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Event $event)
    {
        
    }
}
