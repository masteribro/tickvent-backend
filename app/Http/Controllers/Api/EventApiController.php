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
                    "start_date" => "required|date_format:Y-m-d", // There is a validation of date
                    "end_date" => "sometimes|date_format:Y-m-d|after_or_equal:start_date",
                    "start_time" => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/'],
                    "end_time" => ["nullable", 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', function($attribute, $value, Closure $fail) use ($request) {
                        if($request->end_date == $request->start_date) {
                            if($request->start_time < $value) {
                                $fail("Invalid time");
                            }
                        }
                    }],
                    "type" => "required|in:physical,hybrid,virtual",
                    "location" => "required_if:type,physical,hybrid",
                    "reminders" => "sometimes|array",
                    "reminders.*" => "required_if:reminder,array|string",
                    "tags" => "nullable|array",
                    "tags.*" => "required|string",
                    "images" => "required|mimes:jpeg,png,jpg,gif,mp4,mkv,avi",
                    // "images.*" => "required|mimes:jpeg,png,jpg,gif,mp4,mkv,avi,webm|max:10240"
                ]);


                // $reminders = ReminderService::setReminders($event, $request->reminders);
                // $tags = TagsService::createTag($event, $request->tags);
                // $images_or_video = EventImageService::saveImages($event, $request->images);


                if($validator->fails()) {
                    return ResponseHelper::errorResponse("Validation Error", $validator->errors());
                }

                $event = Event::create([
                    "user_id" => $user->id,
                    "name" => $request->name,
                    "description" => $request->description,
                    "start_date" => $request->start_date,
                    "end_date" => $request->end_date,
                    "slug" => Str::slug($request->name, '-'),
                    "start_time" => $request->start_time,
                    "type" => $request->type,
                    "location" => $request->location,

                ]);

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
        //
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
