<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\OtpService;

class UserVerificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user)
    {
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $res = OtpService::sendOtp('verify_email', $this->user);
        if($res["status"]) {
            Log::info("Otp Sent");
        } else {
            Log::warning("Unable to send otp");
        }

     }
}
