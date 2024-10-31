<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Confectionary;
use App\Models\Event;
use App\Services\ConfectionaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfectionaryApiController extends Controller
{

    public function __construct(protected ConfectionaryService $confectionaryService)
    {

    }
    public function addEventConfectionary(Request $request, $event_id)
    {
        try {
            $event = Event::where("id", $event_id)->first();


            if(!$event) {
                return ResponseHelper::errorResponse("Event does not exists");
            }

            if($event->has_ended) {
                return ResponseHelper::errorResponse("The event has ended");
            }

            $validator = \Validator::make($request->all(),[
                "confectionary_name" => "required|string|max:50",
                "confectionary_price" => "required|decimal:2",
                "confectionary_images" => "nullable|array",
                "confectionay_images.*" => "mimes:jpeg,png,svg",
                "confectionary_additions" => "nullable|array",
                "confectionary_additions.*.name" => "string|max:50",
                "confectionary_additions.*.price" => "decimal:2",
                "confectionary_additions.*.image" => "mimes:jpeg,png,svg",
                "category" => ["nullable", 'array'],
                "category.*" => ['string']
            ]);

            $data = $request->all();
            $data['event_id'] = $event_id;

            // dd($data);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            $resp = $this->confectionaryService->addConfectionary($data, request()->user());
            if($resp['status']){
                return ResponseHelper::successResponse($resp["message"], $resp["data"]);
            }

        } catch (\Throwable $th) {
            Log::warning("Error in adding confectionary",[
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to add confectionary to events");
    }

    public function updateConfectionaryAttachment(Request $request, $event_id ,$confectionary_id)
    {
        try {

            $confectionary = Confectionary::find($confectionary_id);
            if($confectionary_id) {

            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function  getEventConfectionary($event_id, $allOrId )
    {
        try {
            $confectionary = $this->confectionaryService->getConfectionary($event_id, $allOrId);

            if(!$confectionary['status']) {
                return ResponseHelper::errorResponse($confectionary['message']);
            }

            return ResponseHelper::successResponse("Confectionary retrieved", $confectionary['data']);

        } catch (\Throwable $th){
            Log::warning("Error in trying to fetch confectionaryies", [
                "" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Confectionary not found");

    }

    public function updateEventConfectionary(Request $request,$event_id, $confectionary_id)
    {
        $confectionary = $this->confectionaryService->updateEventConfectionary($event_id, $confectionary_id, $request->all());
        return $confectionary;
    }
}
