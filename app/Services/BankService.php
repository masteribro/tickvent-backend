<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Services\Payment\Gateways\PaystackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankService {

    protected $gateways = ['paystack', 'flutterwave'];

    public function seedBanks($gateway)
    {

        if(!in_array($gateway, $this->gateways)) {
            return [
                "status" => false,
                "message" => "Unsupported gateway"
            ];
        }

        if($gateway === 'paystack') {
            $resp = (new PaystackService)->getBanks();
            if($resp['status']) {
                return [
                    "status" => true,
                    "data" => $resp["data"]
                ];
            }
        }

        return [
            'status' => false
        ];
    }

    public function getBanks()
    {
        try {
            return [
                "status" => true,
                "data" => Bank::select(['id', 'bank_code', 'bank_name'])->get()
            ];
        } catch(\Throwable $th) {
            Log::warning("Unable get banks", [
                "error" => $th
            ]);
        }
        return [
            'status' => false,
            'message' => "Unable to get banks"
        ];
    }

    public function verifyAccount($account_number, $bank_code)
    {
        try{
            if($this->gateways[0] === 'paystack') {
                $resp = (new PaystackService)->verfiyAccount($account_number, $bank_code);
                if($resp["status"]) {
                    return $resp;
                }
            }
        } catch (\Throwable $th) {
            Log::warning("Unable to verify account",[
                'error' => $th
            ]);
        }

        return [
            'status' => false,
            "message" => "Unable to verfify Account"
        ];
    }

    public function addBankAccount($payload, $user)
    {
        try {
            $bank = $this->getBank($payload["bank_id"]);
            $resp = $this->verifyAccount($payload["account_number"], $bank->bank_code);

            if($resp["status"]) {
                $gateway_resp = $this->addAccountToGateway($resp["data"]["account_number"], $payload['account_name'], $bank->bank_code,10);
                if($gateway_resp["status"]) {
                    $acc = $this->createBankAccount($gateway_resp['data']["account_number"], $gateway_resp["data"]["account_name"], $bank->bank_code, $gateway_resp["data"]['split_code'], $user->id);
                    if($acc) {
                        return [
                            'status' => true,
                            'data' => $acc,
                        ];
                    }
                }

            }

        } catch(\Throwable $th){
            Log::warning("Unable to add account", [
                "error" => $th
            ]);
        }

        return [
            "status" => false
        ];
    }

    private function getBank($bank_id){
        return Bank::find($bank_id) ?? null;
    }

    private function createBankAccount($account_number, $account_name, $bank_code, $split_code, $user_id)
    {
        $acc = BankAccount::updateOrcreate([
            'account_number' => $account_number,
            'bank_code' => $bank_code,
            'user_id' => $user_id,
        ],[
            'split_code' => $split_code,
            'account_name' => $account_name,
        ]);

        if(!BankAccount::where('user_id', $user_id)->where('default', true)->exists()){
            $acc->update(['default'=> true]);
        }

        return $acc;
    }

    private function addAccountToGateway($account_number, $account_name, $bank_code, $charge = 10)
    {
        try {
            if($this->gateways[0] == 'paystack') {
                $resp = (new PaystackService)->addAccount($account_number, $account_name,$bank_code,$charge);
                if($resp["status"]) {
                    return $resp;
                }
            }
        } catch(\Throwable $th) {
            Log::warning("Unable to add account to gateway");
        }

        return [
            'status' => false,
            "message" => "Unable to add account to gateway"
        ];
    }

    public function getBankAccounts($user)
    {
        return BankAccount::where("user_id", $user->id)->get();
    }
}

?>
