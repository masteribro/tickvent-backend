<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\PaystackService;
use Illuminate\Support\Facades\Log;

class PaymentService {

    protected $gateways = ['paystack', 'flutterwave'];

    public function generatePaymentUrl($data)
    {
        $data['amount'] = $data['amount'] * 100;
        try {

            if($this->gateways[0] === 'paystack') {
                $resp = (new PaystackService)->initializePayment($data);
                if($resp['status']) {
                    return [
                        "status" => true,
                        "data" => $resp["data"]
                    ];
                }
            }

        } catch (\Throwable $th) {
            Log::warning("error in adding role to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return [
            'status' => false
        ];
        
    }
}

?>
