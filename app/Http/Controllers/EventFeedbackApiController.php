<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventFeedbackApiController extends Controller
{
    public function addFeedback(Request $request)
    {
        try {
            $validator = $validator = \Validator::make($request->all(),[
                "event_id" => "required|exists:events,id",
                "message"
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }


        } catch (\Throwable $th) {
            Log::warning("error in ordering ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to generate");
    }
}
