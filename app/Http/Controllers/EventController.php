<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
                $auth = auth()->user_err;
                $validator = \Validator::make($request->all(),[
                    "name" => "required|string",
                    "description" => "sometimes|nullable|string",
                    "start_date" => "required|date",
                    "end_date" => "sometimes|date",
                    "start_time" => "required",
                    "type" => "required|in:physical,hybrid,virtual",
                    "reminders" => "required|string",
                    "tags" => "required|array",
                    "tags.*" => "required|string",
                    "images" => "nullable|array",
                    "images.*" => "mimes:jpeg,png,jpg,gif,mp4,mkv,avi,webm|max:10240"
                ]);

                if($validator->fails()) {
                    return ResponseHelper::errorResponse("Validation Error", $validator->errors());
                }

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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        //
    }
}
