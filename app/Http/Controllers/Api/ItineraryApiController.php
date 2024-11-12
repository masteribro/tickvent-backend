<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Services\ItineraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ItineraryApiController extends Controller
{

    public function __construct(protected ItineraryService $itineraryService)
    {

    }
    public function addItinerary(Request $request, $event_id)
    {
        try {
            $validator = \Validator::make($request->all(),[
                "title" => "required|string|max:60",
                'content' => "nullable|string",
                'time' => [
                    "required",
                    'date_format:H:i:s',
                    function ($attribute, $value, $fail) use ($event_id, $request) {
                        if (Itinerary::where('time', $value)
                            ->where('event_id', $event_id)
                            ->where('slug', '!=', str()->slug($request->title)) // Exclude the current itinerary
                            ->exists()) {
                            $fail('This time is already taken for this itinerary.');
                        }
                    }
                ]
            ]);

            $data = $request->all();
            $data["event_id"] = $event_id;

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            $resp = $this->itineraryService->addItinerary($data);

            if($resp["status"]) {
                return ResponseHelper::successResponse("Itinerary Created", $resp['data']);
            }

        } catch(\Throwable $th) {
            Log::warning("error in adding Itinerary to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return ResponseHelper::errorResponse("Unable to add Itinerary to events");

    }

    public function getItineraries(Request $request, $event_id)
    {
        try {
            $itineraries = Itinerary::where("event_id", $event_id)->when($request->allOrId !== 'all', function ($query) use ($request) {
                return $query->where('id', $request->allOrId);
            })->get();



            if(count($itineraries) > 0) {
                if($request->allOrId !== 'all') {
                    $itineraries = $itineraries->first();
                }
                return ResponseHelper::successResponse('Itineraries fetched successfully', $itineraries);
            }

            return ResponseHelper::errorResponse("No Itinerary Found");
        } catch (\Throwable $th) {
            Log::warning("error in adding Itinerary to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to get Itineraries");

    }

    public function deleteItineraries(Request $request, $event_id)
    {
        try {
            $validator = \Validator::make($request->all(),[
                'itinerary_ids' => ['required', 'array'],
                'itinerary_ids.*' => ['required', 'exists:itineraries,id',function ($attribute, $value, $fail) use ($event_id, $request) {
                    if (!Itinerary::where('id', $value)->where('event_id', $event_id)
                        ->exists()) {
                        $fail('This itinerary cannot be deleted');
                    }
                }]
            ]);
            $itineraries = $request->itinerary_ids;

            if(Itinerary::whereIn('id', $itineraries)->delete()) {
                return ResponseHelper::successResponse("Itineraries Deleted successfully");
            };


        } catch(\Throwable $th) {
            Log::warning("error in adding Itinerary to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to add Itinerary to events");
    }

    public function updateItineraryToDone(Request $request, $event_id, $id)
    {
        try {
            $itinerary = Itinerary::where('id', $id)->where('event_id', $event_id)->first();

            if($itinerary){
                $itinerary->update([
                    'status' => $itinerary->status ? 0 : 1
                ]);

                return ResponseHelper::successResponse("Itinerary completed", $itinerary);

            }

            return ResponseHelper::errorResponse("Not Found",[],404);

        } catch(\Throwable $th) {
            Log::warning("error in adding Itinerary to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return ResponseHelper::errorResponse("Unable to add Itinerary to events");
    }
}
