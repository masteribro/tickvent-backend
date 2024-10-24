<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Jobs\UserVerificationJob;
use App\Models\User;
use App\Services\OtpService;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthApiController extends Controller
{
    public function register(Request $request) 
    {
       try {
        $validation = Validator::make($request->all(), [
            "first_name" => "required|string",
            "last_name" => "required|string",
            "email" => "required|email|unique:users,email",
            "is_mobile" => "required|boolean",
            "device_token" => "required_if:is_mobile,true|string",
            "address" => "nullable|string",
            "phone_number" => "required|string|unique:users,phone_number|regex:/0[7-9][0-1]\d{8}/",
            "passcode" => "required_if:is_mobile,true|digits:6|confirmed",
            "password" => ["required_if:is_mobile,false","confirmed", Password::min(8)->mixedCase()->letters()->symbols()]
        ]);

        if($validation->fails()){
            return ResponseHelper::errorResponse("Validation Error",$validation->errors());
        }
        $data = $request->all();
        $data['password'] = Hash::make($request->passcode ?? $request->password);

        $user = User::create($data);

        $user->api_token = $user->createToken($user->email)->plainTextToken;
        $user->api_test_token = $user->createToken($user->email)->plainTextToken;

        $user->save();

        UserVerificationJob::dispatch($user);

        return ResponseHelper::successResponse("Registration successfull", $user->select(['first_name', 'last_name','email','api_token','phone_number'])->get(),200);
    
       } catch (\Throwable $throwable) {
        Log::warning("Registration Error", [
            "" => $throwable
        ]);
       }

        return ResponseHelper::errorResponse("Unable to create an account, try again later", []);
    }

    public function login(Request $request)
    {
        try {
            $validation = Validator::make(request()->all(), [
                "email" => "required|email|exists:users,email",
                "is_mobile" => "required|boolean",
                "device_token" => "required_if:is_mobile,true|string",
                "passcode" => "required_if:is_mobile,true|digits:6",
                "password" => ["required_if:is_mobile,false", Password::min(8)->mixedCase()->letters()->symbols()]
            ]);
    
            if($validation->fails()){
                return ResponseHelper::errorResponse("Validation Error",$validation->errors());
            }

            $user = UserService::getUser($request->email); 
            if(!Hash::check($request->passcode ?? $request->password, $user->password)) {
                return ResponseHelper::errorResponse("Invalid credentials");
            }
            
            if($user->email_verified_at == null) {
                UserVerificationJob::dispatch($user);

                return ResponseHelper::errorResponse("User not verifed",$user);
            }

            // add device token implementation

            $user->device_token = $request->device_token;

            $user->save();
            return ResponseHelper::successResponse("Login successful", $user);
        } catch(\Throwable $throwable) {
            Log::warning('Login error', [
                "" => $throwable
            ]);
        }

        return ResponseHelper::errorResponse("Unable to login right now, please try again later", []);
    }

    public function changePassword(Request $request) 
    {
        try {

            $token = request()->bearerToken();

            $user = UserService::getUser($token);
            
            if($user == null) {
                return   ResponseHelper::errorResponse("User not found");
            }

            $validator = \Validator::make($request->all(), [
                "is_mobile" => "required|boolean",
                "otp"=> "required|digits:6", 
                "old_passcode" => ["required_if:is_mobile,true", "digits:6", function($attribute, $value, Closure $fail) use ($user) {
                    if(!Hash::check($value, $user->password) ){
                        $fail("The {$attribute} must matched the current password");
                    }
                }],
                'passcode' => "required_if:is_mobile,true|digits:6|confirmed",
                'old_password' => ["required_if:ismoblie,false", "string", "min:8", function($attribute, $value, Closure $fail) use ($user) {
                    if(!Hash::check($value, $user->password) ){
                        $fail("The {$attribute} must matched the current password");
                    }
                }],
                'password' => ["required_if:ismoblie,false", "string", "confirmed", Password::min(8)
                ->letters()
                ->symbols()
                ->numbers()
                ->mixedCase()
                ],
            ]);

            if($validator->fails()){
                return ResponseHelper::errorResponse("Validation Error",$validator->errors());
            }
            
            $otp = $request->otp;
            $res = OtpService::verifyOtp($otp, $user, 'password_reset');

            if($res['status']) {
                $user->update(
                    [
                       "password" => Hash::make($request->password ?? $request->passcode),
                       "password_reset_time" => now()->format("Y-m-d H:i:s")
                    ]
                );
              
                return ResponseHelper::successResponse($request->is_mobile ? 'Passcode changed successfully' : 'Password changed successfully');

            }

            return ResponseHelper::errorResponse($res["message"]);


        } catch (\Exception $e) {
            Log::warning("change password error",[ 
                "" => $e
            ]);
        }
        return ResponseHelper::errorResponse("Unable to change password, something went wrong");
    }

    public function verifyOtp(Request $request) 
    {
    
        try {
            $validator = \Validator::make($request->all(), [
                'email' => "required_if:otp_for,forget_password,verify_email,password_reset|exists:users,email",
                'phone' => "required_if:otp_for,verify_phone|exists:users,phone_number",
                'otp' => "required",
                'otp_for' => "required|in:forget_password,verify_email,verify_phone,password_reset",
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation message", $validator->errors(), 422);
            }

            $otp = $request->otp;
            $email = $request->email ?? null;
            $phone = $request->phone ?? null;

            $user = null;

            $otp_type = request('otp_for');

            if($email !== null) {
                $user = UserService::getUser($email);
            } else if($phone !== null) {
                $user = UserService::getUser($phone);
            }

            if($user == null) {
                return ResponseHelper::errorResponse("User not found", $validator->errors(), 422);
            }

            $res = OtpService::verifyOtp($otp,$user,$otp_type);
            $message = '';
            if($res && $res['status'] == 'success') {
                if($otp_type == 'verify_email') {
                    $message = 'Email verified';
                }
                if($otp_type == 'verify_phone' ) {
                    $message = 'Phone number verified';
                }
                return ResponseHelper::successResponse($message);
            } 

            return ResponseHelper::errorResponse($res["message"]);

        }catch(\Throwable $throwable) {
            Log::warning("Otp Verification error", [
                "" => $throwable
            ]);
        }

        return ResponseHelper::errorResponse("Unable to verify otp");

    }

    public function sendOtp() 
    {
        try {

            $validator = \Validator::make(request()->all(), [
                'otp_for' => "required|in:forget_password,verify_email,verify_phone,password_reset",
                'email' => "required_if:otp_for,forget_password,verify_email,password_reset|email|exists:users,email",
                "phone" => 'required_if:otp_for,verify_phone|string|regex:/0[7-9][0-1]\d{8}/|exists:users,phone_number'
            ]);

            if($validator->fails()){
                return ResponseHelper::errorResponse("Validation Error",$validator->errors());
            }

            $otp_for = request()->otp_for;
            $phone = request()->phone;
            $email = request()->email;

            $user = UserService::getUser($email ?? $phone);
            if($user == null) {

            }
            // calling otp service
            $res = OtpService::sendOtp($otp_for, $user);
            if($res && $res["status"] == 'success') {
                return ResponseHelper::successResponse("OTP sent successfully");
            }

        } catch(\Throwable $throwable) {
            Log::warning("Otp generation issue", [
                "" => $throwable
            ]);
        }

        return ResponseHelper::errorResponse("Unable to send otp");

    }


    public function resetPassword(Request $request) 
    {
        try {
            $validator = \Validator::make(request()->all(), [
                'email' => "required|exists:users,email",
                "is_mobile" => "required|boolean",
                "passcode" => "required_if:is_mobile,true|digits:6|confirmed",
                "password" => ["required_if:is_mobile,false","confirmed",Password::min(8)->letters()->numbers()->mixedCase()->symbols()],
            ]);

                if($validator->fails()) {
                    return ResponseHelper::errorResponse("Validation message", $validator->errors(), 422);
                }

            $email = request('email');
            $password = request('password') ?? request("passcode");

            $user = UserService::getUser($email);

                if($user == null) {
                    return ResponseHelper::errorResponse("User not found");
                }

                $user->update(['password' => Hash::make($password)]);

                return ResponseHelper::successResponse("Password reset successfully");
                
            } catch (\Exception $e) {
                Log::warning("change password error",[ 
                    "" => $e
                ]);
            }

            return ResponseHelper::errorResponse("Unable to reset password");
    }
}