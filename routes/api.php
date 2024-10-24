<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\TicketApiController;
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

        Route::group(['prefix' => 'settings'], function() {
            Route::get("/profile", [AuthApiController::class, 'getProfile']);
            Route::post("/profile", [AuthApiController::class, 'updateProfile']);

            Route::get("/banks", [AuthApiController::class, 'getBanks']);
            Route::put("/banks/{bank_id}", [AuthApiController::class, 'updateBankAccount']);
            Route::post("/banks/{bank_id}", [AuthApiController::class, 'createBankAccount']);
            
            Route::get("/notifications", [AuthApiController::class, "getNotifications"]);
            Route::post("/notifications", [AuthApiController::class, "updatetNotifications"]);
        });

        Route::group(["prefix" => "events"], function() {
            Route::get("", [EventApiController::class, 'index']); // getting all events
            Route::get('/{idOrSlug}', [EventApiController::class, 'getEvent']); // gettings specific events

            Route::post('/create', [EventApiController::class, 'createEvent']);
            Route::post('/add-organizer', [EventApiController::class, 'addOrganizer']);
            Route::post('/add-tickets', [EventApiController::class, 'addTickets']);

            Route::get("/featured", [EventApiController::class, 'getFeaturedEvents']); // getting all featured
            Route::get("/upcoming", [EventApiController::class, 'getFeaturedEvents']); // getting all upcoming
            Route::get("/weekend", [EventApiController::class, 'getFeaturedEvents']); // getting all weekend
            
        });

        Route::get("/booked-events",[EventApiController::class,'getBookedEvents']);
        Route::get("/booked-events/{{booked_event_id}}", [EventApiController::class, "getBookedEvent"]);
            // Manage Events Endppoints
        Route::group(["prefix" => "manage-event"], static function() {
            /**
             * An Endpoint add roles, permissions,
             * An Endpoint to add users to event
             * An Endpoint to add confectionery
             * An Endpoint to verify a user for a specific events
             */

            Route::get("/roles", [EventApiController::class, "getBookedEvent"]);
            Route::get("/permissions", [EventApiController::class, "getPermission"]);
            Route::get("/confectionary", [EventApiController::class, "getConfectionary"]);

            Route::post("/roles", [EventApiController::class, "addRoles"]);
            Route::post("/permissions", [EventApiController::class, "addPermission"]);
            Route::post("/confectionary", [EventApiController::class, "addConfectionary"]);

            // verify ticket
            Route::get("/verify-ticket/{ticket_id}/{invite?}", [TicketApiController::class, "verifyTicket"]);
        });

        Route::group(["prefix" => "notification"], static function() {
            Route::get("",[]); // get notifications
            Route::delete("/{id}",[]); // deleteNotification 
        });



    });
});

// ->middleware('auth:sanctum'); 
