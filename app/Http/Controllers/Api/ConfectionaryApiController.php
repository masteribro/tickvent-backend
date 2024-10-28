<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\ConfectionaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfectionaryApiController extends Controller
{

    public function __construct(protected ConfectionaryService $confectionaryService)
    {

    }
    public function addConfectionary(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(),[
                "event_id" => "required|exists:events,id",
                "confectionary_name" => "required|string|max:50",
                "confectionary_price" => "required|decimal:10,2",
                "confectionary_image" => "nullable|mime:jpeg,png",
                "confectionary_additions" => "nullable|array",
                "confectionary_additions.*.name" => "string|max:50",
                "confectionary_additions.*.price" => "decimal:16,2"
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            $resp = $this->confectionaryService->addConfectionary($request()->all, request()->user());


        } catch (\Throwable $th) {
            Log::warning("Error in adding",[
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to add confectionary to events");
    }
}
