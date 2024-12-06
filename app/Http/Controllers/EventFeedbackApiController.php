<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Event;
use App\Models\EventRating;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventFeedbackApiController extends Controller
{
    public function addFeedback(Request $request)
    {
        try {
            $validator = $validator = \Validator::make($request->all(),[
                "event_id" => "required|exists:events,id",
                "message" => ["required", "string", function ($attribute, $value, $fail) {

                    if(str()->wordCount($value) < 5) return $fail('The feedback message must be more than 5 words');

                }]
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }
            $data = $request->all();

            Feedback::create([
                'event_id' => $data['event_id'],
                'message' => $data['message'],
                'attendee' => $request->user()->id,
            ]);

            return ResponseHelper::successResponse("Feedback saved successfully");

        } catch (\Throwable $th) {
            Log::warning("error in sending feedbacks ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to send feedbacks");
    }

    public function getEventFeedbacks(Request $request)
    {
        try {

            $event = Event::find($request->event_id);

            if(!$event || $event->user_id !== $request->user()->id) {
                return ResponseHelper::errorResponse("Events  doesn't exists");
            }

            $feedbacks = Feedback::where('event_id',$event->id)->get();

            return ResponseHelper::successResponse("Feedbacks for event retrieved it successfully", $feedbacks);

        } catch (\Throwable $th) {
            Log::warning("error in retrieving feedbacks ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to retrieved feedbacks");

    }

    public function rateEvent(Request $request)
    {
        try {

            $validator = \Validator::make($request->all(),[
                'rating' => "required|integer|min:1|max:5",
            ]);

            if($validator->fails()) {

                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            EventRating::create([
                'event_id' => $request->event_id,
                'rating' => $request->rating,
                'user_id' => $request->user()->id
            ]);

            return ResponseHelper::successResponse("Event rated successfully");

        } catch (\Throwable $th) {
            Log::warning("error in sending feedbacks ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to rate event");
    }
}
