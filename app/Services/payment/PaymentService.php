<?php

namespace App\Services\Payment;

use App\Services\OrderService;
use App\Services\Payment\Gateways\PaystackService;
use App\Services\TicketService;
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

    public function verifyTransaction($reference)
    {
        try {

            if($this->gateways[0] === 'paystack') {
                $resp = (new PaystackService)->verifyTransaction($reference);
                if($resp['status']) {
                    return [
                        "status" => true,
                        "data" => $resp["data"]
                    ];
                }
            }

        } catch (\Throwable $th) {
            Log::warning("error in verifying transaction",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return [
            'status' => false
        ];
    }

    public function handleWebHook($gateway, $payload)
    {
        try {

            if($gateway === 'paystack') {

                $resp = (new PaystackService)->handleWebHook($payload['data']['reference']);

                if($resp['status']) {
                    $data = $resp['data'];

                    $metaData = $data['metadata'];


                    if($data['status'] === 'success') {
                        $resp = [];

                        if($metaData['payment_for'] == 'confectionary_order') {
                            $orderService = (new OrderService());

                            $resp = $orderService->updateOrder($data['reference']);
                        }

                        if($metaData['payment_for'] == 'book_ticket') {
                            $ticket = (new TicketService());

                            $resp = $ticket->updateTicket($data['reference']);
                        }

                    }

                    return $resp;
                }

                return [
                    'status' => false,
                    'message' => $resp['message']
                ];
            }
        }  catch (\Throwable $th) {
            Log::warning("error in verifying transaction",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return [
            'status' => false,
            'message' => 'Something went wrong, please try again later'
        ];

    }
}

?>
