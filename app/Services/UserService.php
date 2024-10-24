<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Models\User;

class UserService 
{
    public static function getUser($identifier) 
    {
        return User::where("email", $identifier)->orWhere('id', $identifier)->orWhere("phone_number",$identifier)->orWhere("api_token", $identifier)->first() ?? null;
    }

    public static function getUserOrganizationInfo($user)
    {
        return [
            "organizer_name" => $user->organizer_name,
            "organizer_info" => $user->organizer_info,
            "organizer_img" => $user->organizer_img,
        ];
    }

    public static function registerUser($email)
    {
        $user = self::getUser($email);
        if(!$user) {
            $user = User::create(["email" => request('email')]);

            $resp = retry(3, function () use ($user) {
                return OtpService::sendOtp('registration', $user);
            });
    
            if($resp["status"]) {
                return [
                    'status' => true,
                    "message" => "Verification code sent"
                ];
            } else {
                return [
                'status' => false,
                "message" => $resp["message"]
            ];
            }
        } else {
            if($user->is_verified) {
                
                return [
                    'status' => false,
                    "message" => "User already exists"
                ];

            } else {
                $resp = retry(3, function () use ($user) {
                    return OtpService::sendOtp('registration', $user);
                });

                if($resp["status"]) {
                    return [
                        'status' => true,
                        'message' => "Please verify your account, an OTP has been sent to your email"
                    ];
                }
                return [
                    'status' => false,
                    'message' => $resp['message']
                ];
            }
        }
    }
}