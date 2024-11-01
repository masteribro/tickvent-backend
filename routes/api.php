<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BankApiController;
use App\Http\Controllers\Api\ConfectionaryApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Models\Confectionary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'v1'],function() {
    Route::post('/register', [AuthApiController::class, 'register']); // endpoint for password
    Route::post('/register-verification', [AuthApiController::class, 'registerVerification']); // endpoint for password
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);

    Route::post("/verify-otp", [AuthApiController::class, 'verifyOtp']);
    Route::post("/send-otp", [AuthApiController::class, 'sendOtp']);

    Route::group(["middleware" => ['auth:sanctum']], function() {
        Route::post('/change-password', [AuthApiController::class, 'changePassword']);

        Route::group(['prefix' => 'banks'], static function () {
            Route::get("", [BankApiController::class, 'getBanks']);
            Route::post("/add-bank", [BankApiController::class, 'addBankAccount']);
            Route::get("/accounts", [BankApiController::class, 'getBankAccounts']);
        });

        Route::group(['prefix' => 'settings'], function() {
            // personal profile
            Route::get("/profile", [AuthApiController::class, 'getProfile']);
            Route::put("/profile", [AuthApiController::class, 'updateProfile']);

            // Organization Profile
            Route::get("/organizer/profile", [AuthApiController::class, 'getOrganizerProfile']);
            Route::put("/organizer/profile", [AuthApiController::class, 'updateOrganizerProfile']);

            // Notifications
            Route::get("/notifications", [AuthApiController::class, "getNotificationsSettings"]);
            Route::put("/notifications", [AuthApiController::class, "updateNotificationSettings"]);
        });

        Route::group(["prefix" => "events"], function() {
            Route::get("", [EventApiController::class, 'index']); // getting all events
            Route::get('/{idOrSlug}', [EventApiController::class, 'getEvent']); // gettings specific events

            Route::post('/create', [EventApiController::class, 'createEvent']);

            // Route::post("/order", [EventApiController::class, ""]);
            // Route::post("/cart", [EventApiController::class, ""]);
        });

        // Manage Events Endppoints
        Route::group(["prefix" => "manage-event", 'middleware' => 'event-owner'], static function() {

            Route::get("/roles", [EventApiController::class, "getEventRoles"]);
            Route::post("/roles", [EventApiController::class, "getEventRoles"]);

            Route::get("/permissions", [EventApiController::class, "getEventRolesPermission"]);
            Route::post("/permissions", [EventApiController::class, "getEventRolesPermission"]);

            Route::get("/{event_id}/confectionary/{allOrId}", [ConfectionaryApiController::class, "getEventConfectionary"]);
            Route::post("/{event_id}/confectionary", [ConfectionaryApiController::class, "addEventConfectionary"]);
            Route::post("/{event_id}/confectionary/{confectionary_id}", [ConfectionaryApiController::class, "updateEventConfectionary"]);

            Route::delete("/{event_id}/confectionary", [ConfectionaryApiController::class, "deleteEventConfectionary"]);
            Route::delete("/{event_id}/confectionary/", [ConfectionaryApiController::class, "deleteEventConfectionary"]);
            Route::delete("/{event_id}/confectionary/{confectionary_id}/images", [ConfectionaryApiController::class, "deleteEventConfectionaryImage"]);
            Route::delete("/{event_id}/confectionary/{confectionary_id}/attachments", [ConfectionaryApiController::class, "deleteConfectionaryAttachment"]);

            Route::post("/add-worker", [EventApiController::class, "addEventWorker"]);
            Route::delete("/delete-workers", [EventApiController::class, "deleteEventWorkers"]);

            Route::get("/itinerary", [EventApiController::class, "getItinerary"]);
            Route::post("/itinerary", [EventApiController::class, "addItinerary"]);

            // verify ticket
            Route::post('/ticket/{event_id}', [TicketApiController::class, 'addTickets']);
            Route::get("/verify-ticket/{ticket_id}/{invite?}", [TicketApiController::class, "verifyTicket"]);
        });

        // Admin Endpoint

        Route::group(['prefix' => 'admin', ['middleware' => 'admin']], static function () {
            Route::patch("events/featured/{id?}",[AdminController::class, 'featuredEvent']);
        });

    });
});


// ->middleware('auth:sanctum');
