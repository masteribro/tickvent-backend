<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentApiController extends Controller
{

    protected $paymentService;

    public function __construct() {
        $this->paymentService = (new PaymentService());
    }

    public function handleWebHook(Request $request)
    {
        try {

            $payload = $request->all();

            $gateway = $request->gateway;

            $resp = $this->paymentService->handleWebHook($gateway, $payload);

            if($resp['status']) {
                return ResponseHelper::successResponse("Payment received");
            }

            return ResponseHelper::errorResponse($resp['message']);

        } catch(\Throwable $th) {
            Log::warning("Error in verify transaction",[
                'error' => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to handle request");
    }
}
