<?php
namespace App\Services;

use App\Models\Confectionary;
use App\Models\ConfectionaryAttachment;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderService {

    private $paymentService;

    public function __construct()
    {
        $this->paymentService = (new PaymentService);
    }

    
    public function getPaymentUrl($data)
    {
        try {

            DB::beginTransaction();
             $total = 0;
             $order = Order::create([
                'user_id' => request()->user()->id,
                'event_id' => $data['event_id']
             ]);
            collect($data['items'])->each(function ($item) use (&$total, $data, $order) {
                $item_name = '';
                $item_price = null;
                if ($item['type'] === 'confectionary') {
                    $confectionary = Confectionary::where('id', $item['id'])
                        ->where('event_id', $data['event_id'])->first();

                        $item_name = $confectionary->name;
                        $item_price = (int) $confectionary->price;
                } elseif ($item['type'] === 'attachment') {

                    $attachment = ConfectionaryAttachment::where('id', $item['id'])
                        ->first();

                    $item_name = $attachment->name;
                    $item_price = (int) $attachment->price;
                }
                
                $total += (int) $item["quantity"] * $item_price;
                OrderItem::create([
                    'item_id' => $item['id'],
                    'item_name' => $item_name,
                    'order_id' => $order->id,
                    'price' => $item_price,
                    'event_id' => $data['event_id'],
                    'quantity' => $item['quantity'],
                    'item' => $item['type']
                ]);

            });
            
            $order->update([
                'total_amount' => $total
            ]);

            $data['amount'] = $total;
            $data['email'] = request()->user()->email;
            $data['currency'] = 'NGN';
            $data['metadata'] = $data;

            $resp = $this->generatePaymentUrl($data);
            
            if($resp['status']) {
                DB::commit();

                $order->update($resp['data']);
                return [
                    'status' => true,
                    'data' =>  [
                        'checkout_url' => $order->authorization_url
                    ]
                ];
            }             
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::warning("error in ordering service ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        } 

        return [
            'status' => false
        ];
    }

    private function generatePaymentUrl($data)
    {
        try {
            $res = $this->paymentService->generatePaymentUrl($data);

            if($res['status']) {
                return [
                    'status' => true,
                    'data' => $res['data']
                ];
            }

        } catch(\Throwable $th) {
            Log::warning("error in payment link ",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return [
            'status' => false,
        ];
    }
}
