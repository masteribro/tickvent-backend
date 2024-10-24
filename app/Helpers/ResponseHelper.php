<?php

namespace App\Helpers;

class ResponseHelper {

    //function to format a success response
    public  static function successResponse($msg ,$data = [],$status_code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true ,
            'data' => $data,
            'message' => $msg
        ],$status_code);

    }

    //function to format an error response
    public static function errorResponse($msg, $error = [] ,$status_code = 422): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "status" => false,
            "error" => $error,
            "message" => $msg
        ], $status_code);
    }


}
