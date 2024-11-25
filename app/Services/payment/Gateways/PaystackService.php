<?php
namespace App\Services\Payment\Gateways;

use App\Models\BankAccount;
use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\Type\FalseType;

class PaystackService
{
    protected $headers;

    protected $secret_key;

    public function __construct()
    {
        $this->secret_key = config('paystack.secret_key');

        $this->headers = [
            "Authorization" => "Bearer " . $this->secret_key,
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ];
    }

    public function getBanks()
    {
        try {
            $resp = Http::withHeaders($this->headers)->get(config('paystack.base_url') . '/bank');
            if($resp->failed()) {
                return [
                    "status" => false,
                    "data" => $resp->json()
                ];
            }
            if($resp["status"]) {
                $data = collect($resp["data"])->map(function($item) {
                    return [
                        "bank_name" => $item["name"],
                        "bank_code" => $item["code"]
                    ];
                })->toArray();

                return [
                    "status" => true,
                    "data" => $data,
                    "message" => $resp["message"]
                ];
            }
        } catch(\Throwable $th) {
            Log::warning("Error in getting list of banks", [
                "error" => $th
            ]);
        }

       // Log::warning($resp);

        return [
            "status" => false,
            "message" => "Unable to get banks"
        ];

    }

    public function verfiyAccount($account_number, $bank_code)
    {
        try {
        $resp = Http::withHeaders($this->headers)->get(config('paystack.base_url') . '/bank/resolve?account_number=' . $account_number . '&bank_code=' .$bank_code);
            if($resp->failed()) {
            Log::warning("verify erorr",["" => $resp->json()]);
                return [
                    "status" => false,
                    "data" => $resp->json()
                ];
            }
            if($resp["status"]) {
              return [
                    "status" => true,
                    "data" => $resp["data"],
                    "message" => $resp["message"]
                ];
            }
        } catch(\Throwable $th) {
            Log::warning("Error verifying bank details", [
                "error" => $th
            ]);
        }

        return [
            "status" => false,
            "message" => "Unable to get verify account"
        ];
    }

    public function addAccount($account_number, $account_name, $bank_code, $charge)
    {
        try {
            $resp = Http::withHeaders($this->headers)->post(config('paystack.base_url') .'/subaccount', [
                    "business_name" => $account_name,
                    "bank_code" => $bank_code,
                    "account_number" => $account_number,
                    "percentage_charge" => $charge
            ]);

            if($resp->successful()) {
                $resp_payload = $resp->json();
                if($resp_payload["status"]) {
                    return [
                    'status' => true,
                    "data" => [
                        'account_name' => $resp_payload["data"]['account_name'],
                        'account_number' => $resp_payload["data"]['account_number'],
                        'split_code' => $resp_payload["data"]['subaccount_code']
                    ]
                ];
            }
        }
        } catch(\Throwable $th) {
                Log::warning("Error in adding account to get way", [
                    "Error" => $th
                ]);
        }

            return [
                'status' => false,
                'meesage' => 'Unable to add account'
            ];
    }

    public function initializePayment($data)
    {
        try {
            $url = config('paystack.base_url') . "/transaction/initialize";
            $event = Event::where('id', $data['event_id'])->first();
            if($event) {
                $bank_account = BankAccount::where('id', $event->bank_account_id)->first();
            } else {
                $bank_account = BankAccount::where('user_id', $event->user_id)->where('default', 1)->first();
            }

            if(!$bank_account) {
                return [
                    'status' => false
                ];
            }

            $data["subaccount"] = $bank_account->split_code;

            unset($data['bank_account_id']);
            Log::warning("Order Data", [
                'data' => $data
            ]);
            $res = Http::withHeaders($this->headers)->post($url,$data);

            if($res->successful()) {
                $resp = $res->json();

                if($resp['status'] == true) {
                    return [
                        'status' => true,
                        'data' => [
                            "access_code" => $resp['data']['access_code'],
                            "reference" => $resp['data']['reference'],
                            "authorization_url" => $resp['data']['authorization_url']
                        ]
                    ];
                } else {
                    Log::warning("paystack error", [
                        'err' => $res
                    ]);
                }
            } else {
                Log::warning("paystack error", [
                    'err' => $res
                ]);
            }
        } catch(\Throwable $th) {
            Log::warning("error in adding role to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }

        return [
            'status' => false,
        ];
    }

    public function verifyTransaction($reference)
    {
        try {
            $resp = Http::withHeaders($this->headers)->get( config('paystack.base_url') . '/transaction/verify/' . $reference );

                if($resp->failed()) {
                    return [
                        "status" => false,
                        "data" => $resp->json()
                    ];
                }

                if($resp["status"]) {
                    return [
                        "status" => true,
                        "data" => $resp["data"],
                        "message" => $resp["message"]
                    ];
                }

            } catch(\Throwable $th) {
                Log::warning("Verify Transaction", [
                    "error" => $th
                ]);
            }

            return [
                "status" => false,
                "message" => "Unable to verify transaction"
            ];
    }
}
