<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\EventApiController;
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

        Route::group(['prefix' => 'user'], function() {
            ROute::get("/profile", [AuthApiController::class, 'getProfile']);
            ROute::post("/profile", [AuthApiController::class, 'updateProfile']);
        });

        Route::get("/notifications", [AuthApiController::class, 'getNotifications']);
        Route::post("/notifications", [AuthApiController::class, 'postNotifications']);

        Route::group(["prefix" => "events"], function() {
            Route::get("", [EventApiController::class, 'index']); // getting all events
            Route::get('/{idOrSlug}', [EventApiController::class, 'getEvent']); // gettings specific events

            Route::post('/create', [EventApiController::class, 'createEvent']);
            Route::post('/add-organizer', [EventApiController::class, 'addOrganizer']);
            Route::post('/add-tickets', [EventApiController::class, 'addTickets']);

            // Manage Events Endppoints

            Route::group(["prefix" => "manage-event"], static function() {
                /**
                 * An Endpoint add roles, permissions,
                 * An Endpoint to add users to event
                 * An Endpoint to add confectionery
                 * An Endpoint to verify a user for a specific events
                 * An Endpoint 
                 */
            });

            
        });
    });



});

// ->middleware('auth:sanctum'); 
