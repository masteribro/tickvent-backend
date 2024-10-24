<?php

use App\Http\Controllers\Api\AuthApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'v1'],function() {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);

    Route::post("/verify-otp", [AuthApiController::class, 'verifyOtp']);
    Route::post("/send-otp", [AuthApiController::class, 'sendOtp']);


    Route::group(["middleware" => ['auth:sanctum']], function() {
        Route::post('/change-password', [AuthApiController::class, 'changePassword']);

        Route::group(["prefix" => "events"], function() {
            Route::post('/create', [EventApiController::class, 'create']);
        });
    });



});

// ->middleware('auth:sanctum'); 
