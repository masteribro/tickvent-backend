<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Confectionary;
use App\Models\ConfectionaryAttachment;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderApiController extends Controller
{
    public function __construct(public OrderService $orderService)
    {
        
    }

    public function orderConfectionary(Request $request, $event_id)
    {
        try {
            $validator = $validator = \Validator::make($request->all(),[
                "event_id" => "required|exists:events,id",
                "items" => ["required", "array"],
                "items.*.id" => ["required",
                function ($attribute, $value, $fail) use ($request, $event_id) {
                    $existsInConfectionary = false;
                    $existsInConfectionaryAttachment = false;
                    $index = str_replace('items.', '', explode('.', $attribute)[1] ?? 0);
            
                    // Check the type of item and validate existence in the corresponding table
                    if ($request->items[$index]['type'] === 'confectionary') {
                        $existsInConfectionary = Confectionary::where('id', $value)
                            ->where('event_id', $event_id)
                            ->exists();
                    } elseif ($request->items[$index]['type'] === 'attachment') {
                        $existsInConfectionaryAttachment = ConfectionaryAttachment::where('id', $value)
                            ->exists();
                    }
            
                    // Fail validation if the item doesn't exist in either table
                    if (!$existsInConfectionary && !$existsInConfectionaryAttachment) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
    
                    ],
                "items.*.type" => "required|string|in:confectionary,attachment",
                "items.*.quantity" => "required|integer"
            ]);
    
            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }
            $data = $request->all();
            $data['user_id'] = $request->user()->id;
     
            $res = $this->orderService->getPaymentUrl($data);

            if($res['status']) {
                return ResponseHelper::successResponse("Payment Link generated", $res['data']);
            }
        } catch (\Throwable $th) {
            Log::warning("error in ordering ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return ResponseHelper::errorResponse("Unable to generate");
        
    }
}