<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Services\BankService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $banks = (new BankService)->seedBanks('paystack');

            if(!$banks["status"]) {
                Log::warning('Error in getting banks');
            }
            foreach($banks["data"] as $bank) {
                Bank::updateOrCreate([
                    'bank_code' => $bank["bank_code"],
                    'bank_name' => $bank["bank_name"],
                ]);
            }

            Log::warning("banks seed");
        } catch(\Throwable $th) {
            Log::warning("get banks issue", [
                "error" => $th
            ]);
        }


    }
}
