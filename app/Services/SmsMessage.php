<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Vonage\SMS\Message\SMS;
use Illuminate\Support\Str;

class SmsMessage {
    
    protected $basic;
    protected $client;
    public function __construct()
    {
        $this->basic  = new \Vonage\Client\Credentials\Basic(env("VONAGE_KEY"), env("VONAGE_SECRET"));
        $this->client = new \Vonage\Client($this->basic);
    }

    public function send($to, $brand_name, $message)
    {
        $to = '234' . Str::substr($to,1,10);
        $response = $this->client->sms()->send(
            new SMS($to, $brand_name, $message)
        );
        
        $message = $response->current();
        
        if ($message->getStatus() == 0) {
            Log::info("Sms Sent");
        } else {
            Log::warning("Sms sending failed");
        }
    }
}