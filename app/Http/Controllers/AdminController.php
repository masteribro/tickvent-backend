<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function featuredEvent($id)
    {
        try {
            $event = Event::find($id);

            if(!$event) {
                return ResponseHelper::errorResponse("Event does not exist");
            }

            $event->update([
                "featured" => true
            ]);

            return ResponseHelper::successResponse("Event set to a featured event, succesfully");

        } catch (\Throwable $th) {
            Log::warning("Error in setting event to featured", [
                "error" => $th
            ]);
        }
        return ResponseHelper::errorResponse("unable to change event to featured event");
    }
}
