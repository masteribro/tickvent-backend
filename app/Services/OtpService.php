<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Log;


    class OtpService 
    {
        public static function sendOtp($otp_for,$user)
        {
            $otp = random_int(100000, 999999);

            $otp = Otp::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'otp_for' => $otp_for,
                ],
                [
                    'otp' => $otp,
                    'expiring_date' => now()->addMinutes(15)->format("Y-m-d H:i:s"),
                    'created_at' => now()->format("Y-m-d H:i:s")
                ]);
                $data = [
                    'title' => "OTP request",
                    'otp' => $otp->otp,
                    "body" => "Please find your one time password " . $otp->otp,
                    'notification_type' => $otp_for !== 'verify_phone' ? "email" : 'sms'
                ];
                // notification will be sent
                if($otp !== null && $user !== null ) {
                    NotificationService::send($user,$data ,'otp');
                    return [
                        "status" => true
                    ];
                }

                return [
                    "status" => true
                ];
        }

        public static function verifyOtp($otp,$user,$type) 
        {
            $otp = Otp::where("otp", $otp)->first();
            if($otp == null) {
                return [
                    'status' => false, 
                    'message' => "Invalid OTP"
                ];
            }
            Log::warning([$otp]);

            if($otp->user_id == $user->id && $otp->otp_for == $type) {
                if(now()->between($otp->created_at, $otp->expiring_date)) {

                    if($otp->otp_for == 'verify_email') {
                        $user->update(['email_verified_at' => now()->format("Y-m-d H:i:s")]);
                    }
                    if($otp->otp_for == 'verify_phone') {
                        $user->update(['phone_verified_at' => now()->format("Y-m-d H:i:s")]);
                    }

                    $otp->delete();

                    return [
                        "status" => true,
                        "message" => "OTP verified"
                    ];
                } else {
                    return [
                        "status" => false,
                        "message" => "OTP has expired"
                    ];
                }
            } else {
                return [
                    "status" => false,
                    "message" => "Invalid OTP"
                ];
            }

        }

        public static function registrationOtp(User $user) {
            $otp = random_int(100000, 999999);

            $otp = Otp::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'otp_for' => 'verify_email'
                ],
                [
                    'otp' => $otp,
                    'expiring_date' => now()->addMinutes(15)->format("Y-m-d H:i:s"),
                ]);
            $data = [
                    'title' => "User Email Verification",
                    'notification_type' => "email",
                    'otp' => $otp->otp
                ];
                // notification will be sent
                if($otp !== null && $user !== null ) {
                    NotificationService::send($user,$data,'otp');
                    return [
                        "status" => true
                    ];
                }

                return [
                    "status" => false
                ];

        }

        
    }

?>