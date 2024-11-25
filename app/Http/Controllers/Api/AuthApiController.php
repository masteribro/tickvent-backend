<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Jobs\UserVerificationJob;
use App\Models\BankAccount;
use App\Models\Notification;
use App\Models\Otp;
use App\Models\PurchasedTicket;
use App\Models\User;
use App\Services\NotificationService;
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
    protected $user;

    public function __construct() {
        $this->user = auth('sanctum')->user();
    }

    public function register(Request $request)
    {
       try {

        $validation = Validator::make($request->all(), [
           // "first_name" => "required|string",
           // "last_name" => "required|string",
            "email" => "required|email",
            "is_mobile" => "required|boolean",
            "device_token" => "required_if:is_mobile,true|string",
            // "address" => "nullable|string",
            // "phone_number" => "required|string|unique:users,phone_number|regex:/0[7-9][0-1]\d{8}/",
            // "passcode" => "required_if:is_mobile,true|digits:6|confirmed",
            // "password" => ["required_if:is_mobile,false","confirmed", Password::min(8)->mixedCase()->letters()->symbols()]
        ]);

        if($validation->fails()){
            return ResponseHelper::errorResponse("Validation Error",$validation->errors());
        }
        $resp = UserService::registerUser(request('email'));

        if($resp["status"]) {
            return ResponseHelper::successResponse($resp["message"]);
        }

        return ResponseHelper::errorResponse($resp["message"]);

       } catch (\Throwable $throwable) {
        Log::warning("Registration Error", [
            "error" => $throwable->getMessage()
        ]);
       }
        return ResponseHelper::errorResponse("Unable to create an account, try again later", []);
    }

    public function registerVerification()
    {
        try{
            $validator = Validator::make(request()->all(), [
                "otp" => "required|digits:6",
                "email" => "required|exists:users,email"
            ]);

            if($validator->fails()){
                return ResponseHelper::errorResponse("Validation Error",$validator->errors());
            }

            $user = UserService::getUser(request('email'));

            $resp = OtpService::verifyOtp(request('otp'), $user, 'registration');
            if(!$resp["status"]) {
                $resp = OtpService::sendOtp("registration",$user);
                if($resp["status"]) return ResponseHelper::errorResponse('Invalid OTP, OTP resent!');
            }

            $user->update([
                "api_token" => $user->createToken($user->email)->plainTextToken,
                "api_test_token" => $user->createToken($user->email)->plainTextToken,
                "is_verified" => true
            ]);


            return ResponseHelper::successResponse("User verified",$user,201);

        } catch(\Throwable $throwable) {
            Log::warning("Error in registration verification", [
                "error" => $throwable
            ]);
        }

        return ResponseHelper::errorResponse("Unable to verify email");
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
            if(!$user->is_verified) {
                $resp = OtpService::sendOtp("registration", $user);
                if(!$resp['status']) {
                    Log::warning("Unable to send Otp");
                    return ResponseHelper::errorResponse("Unable to send verificatio code, please try again");
                }
                return ResponseHelper::errorResponse("Verify your account, Verification code has been sent to this email");
            } else if(!Hash::check($request->passcode ?? $request->password, $user->password)) {
                return ResponseHelper::errorResponse("Invalid credentials, please confirmed or set your password");
            }

            // if(!$user->email_verified_at) {

            //     UserVerificationJob::dispatch($user);

            //     return ResponseHelper::errorResponse("User not verifed",$user);
            // }

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
            $res = OtpService::verifyOtp($otp, $user, 'change_password');

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
                'otp_for' => "required|in:forget_password,change_password,verify_email,verify_phone,password_reset",
                'email' => "required_if:otp_for,change_password,forget_password,verify_email,password_reset|email|exists:users,email",
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
                "otp" => "required|digits:6",
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

                } else {
                    return ResponseHelper::errorResponse($res["message"]);
                }

            } catch (\Exception $e) {
                Log::warning("change password error",[
                    "" => $e
                ]);
            }

            return ResponseHelper::errorResponse("Unable to reset password");
    }

    public function getProfile()
    {
        $user = auth('sanctum')->user();

        return ResponseHelper::successResponse("User Profile Retrieved", [
            "full_name" => $user->full_name ?? '',
            "email" => $user->email,
            "phone_number" => $user->phone_number ?? '',
            "location" => $user->location ?? ''
        ]);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth('sanctum')->user();

            $validation = Validator::make($request->all(), [
                 "full_name" => "required|nullable|string",
                 "phone_number" => ["nullable" ,"string","unique:users,phone_number," . $user->id,"regex:/0[7-9][0-1]\d{8}/"],
                 "location" => "nullable|string",
             ]);

             if($validation->fails()){
                 return ResponseHelper::errorResponse("Validation Error",$validation->errors());
            }

             $data = $request->all();

             $user->update($data);

             return ResponseHelper::successResponse("Profile update successfully");

        } catch(\Throwable $throwable) {
            Log::warning("Update Profile Error", [
                "" => $throwable
            ]);

            return ResponseHelper::errorResponse("Unable to update profile");

        }

    }

    public function getOrganizerProfile()
    {
        try {
            return ResponseHelper::successResponse("Organizer information retrieved", UserService::getUserOrganizationInfo($this->user));

        } catch(\Throwable $throwable) {
            Log::warning("Error in get organizer profile", [
                "error" => $throwable
            ]);
        }
        return ResponseHelper::errorResponse("Unable to get organizer infomation");
    }

    public function updateOrganizerProfile(Request $request)
    {
        try {
            $user = auth('sanctum')->user();

            $validation = Validator::make($request->all(), [
                 "organizer_name" => "required|nullable|string",
                 "organizer_info" => ["nullable" ,"string"],
                 "organizer_img" => "nullable|string",
             ]);

             if($validation->fails()){
                 return ResponseHelper::errorResponse("Validation Error",$validation->errors());
            }

             $data = $request->all();

             $user->update($data);

             return ResponseHelper::successResponse("Organizer Profile updated successfully");

        } catch(\Throwable $throwable) {
            Log::warning("Update Organizer Profile Error", [
                "" => $throwable
            ]);

            return ResponseHelper::errorResponse("Unable to update profile");
        }

    }
    public function getNotificationsSettings()
    {
        try {
            $user = auth('sanctum')->user();

            $data = (new NotificationService())->getNotifications($user);

            return ResponseHelper::successResponse("Notification Settings Retrieved",$data);

        } catch(\Throwable $throwable) {
            Log::warning("Error in getting notification", [
                "" => $throwable
            ]);
            return ResponseHelper::errorResponse("Unable to get notification settings");
        }

    }

    public function updateNotificationSettings()
    {
        try {
            $user = $this->user;

            $request = request();

            $validation = Validator::make($request->all(),[
                "notifications" => "required|array",
                "notifications.*.channel" => "required|string|in:email,sms",
                "notifications.*.type" => "required|in:ticket-gift,event-invite,new-event,happening-around,login",
                "notifications.*.value" => "required|boolean"
            ]);

            if($validation->fails()){

            return ResponseHelper::errorResponse("Validation Error",$validation->errors());
        }

        $resp = (new NotificationService)->updateNotifications($request->notifications, $user);
            if($resp["status"]) {
                return ResponseHelper::successResponse("Notification updated successfull");
            }

        } catch(\Throwable $throwable){
            Log::warning("Error in setting notification",[
                "error" => $throwable
            ]);
        }
        return ResponseHelper::errorResponse("Unable to update notification");

    }

    public function getBanks()
    {
        try {
            $user = auth('sanctum')->user();

            $banks = $user->banks;
            return ResponseHelper::successResponse("Banks retrieve successfully",$banks);
        } catch(\Throwable $th) {
            Log::warning("Error in geting banks", [
                "error" => $th
            ]);
        }

        return ResponseHelper::errorResponse("Unable to get banks");
    }

    public function addBank()
    {
        // you will need to add authentication to this
        $user = auth('sanctum')->user();

        $request = request();
        $validation = Validator::make($request->all(), [
            "bank_code" => "required|string",
            "account_name" => "required|string",
            "account_number" => "required|string|regex:/[0-9]{10}/"
            ]);

        if($validation->fails()){
            return ResponseHelper::errorResponse("Validation Error",$validation->errors());
        }

        $data["user_id"] = $user->id;
        $data['bank_name'] = "These where you get the bank_name";

        BankAccount::create($data);

    }

    public function getBanksCodes()
    {
        // Talk to Paystack
        // PaystackService::getBankCodes
    }

    public function getTickets()
    {
        try {

            return ResponseHelper::successResponse("All successfully booked tickets", [
                'status' => true,
                'data' => PurchasedTicket::where('user_id', auth('sanctum')->user()->id)->where('status', 'paid')->get()
            ]);

        } catch(\Throwable $th){
            Log::warning("tickets" , [
                'error' => $th
            ]);
        }
    }
}
