<?php
namespace App\Services\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
}
