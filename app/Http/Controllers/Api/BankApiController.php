<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\BankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankApiController extends Controller
{
    public function getBanks()
    {
        try {
            $resp = (new BankService)->getBanks();

            if($resp["status"]) {
                return ResponseHelper::successResponse("Banks retrieved successfully", $resp["data"]);
            }

        } catch(\Throwable $th) {
            Log::warning("Errror in get banks", [
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("error in getting banks");
    }

    public function addBankAccount()
    {
        try {
            $validator = \Validator::make(request()->all(), [
                "default" => "nullable|boolean",
                "bank_id" => "required|integer|exists:banks,id",
                "account_number" => "required|digits:10",
                "account_name" => "required|string",
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

            $resp = (new BankService)->addBankAccount(request()->all(), request()->user());

            if($resp["status"]) {
                return ResponseHelper::successResponse("Bank Account added successfully", $resp["data"]);
            }

        } catch(\Throwable $th) {
            Log::warning("Unable to add account banks", [
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to add bank account, try again later");
    }

    public function getBankAccounts()
    {

        try {

            $resp = (new BankService)->getBankAccounts(request()->user());


            return ResponseHelper::successResponse("Bank Accounts fetched successfully", $resp);

        } catch(\Throwable $th) {
            Log::warning("Unable to get bank accounts", [
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to get bank accounts, try again later");
    }
}
