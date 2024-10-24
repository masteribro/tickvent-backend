<?php

namespace App\Services;

use App\Models\User;

class UserService 
{
    public static function getUser($identifier) 
    {
        return User::where("email", $identifier)->orWhere("phone_number",$identifier)->orWhere("api_token", $identifier)->first() ?? null;
    }
}