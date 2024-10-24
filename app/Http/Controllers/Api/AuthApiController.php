<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Jobs\UserVerificationJob;
use App\Models\User;
use App\Services\UserService;
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
                return ResponseHelper::errorResponse("Invalid credentials",$user);
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

}