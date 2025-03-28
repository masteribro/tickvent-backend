<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BankApiController;
use App\Http\Controllers\Api\ConfectionaryApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\ItineraryApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\RolePermissionApiController;
use App\Http\Controllers\EventFeedbackApiController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'v1'],function() {
    Route::post('/register', [AuthApiController::class, 'register']); // endpoint for password
    Route::post('/register-verification', [AuthApiController::class, 'registerVerification']); // endpoint for password
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/reset-password', [AuthApiController::class, 'resetPassword']);

    Route::post("/verify-otp", [AuthApiController::class, 'verifyOtp']);
    Route::post("/send-otp", [AuthApiController::class, 'sendOtp']);

    // Authenticated Routes
    Route::group(["middleware" => ['auth:sanctum']], function() {
        // All users
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

        Route::get('/get-tickets', [AuthApiController::class, 'getTickets']);

        // Attendee Endpoints
        // TODO:: I will create a middleware that checks if a use has ability to do the action
        Route::post('/order-confectionary/{event_id}', [OrderApiController::class, 'orderConfectionary']);
        Route::post('/events/feedback', [EventFeedbackApiController::class, 'addFeedback']);
        Route::post('/events/break-log', [EventApiController::class, 'addBreakLog']);
        Route::get('/events/break-log-reasons', [EventApiController::class, 'getBreakLogsReason']);
        Route::post('/rating/{event_id}', [EventFeedbackApiController::class, 'rateEvent']);



        Route::group(["prefix" => "events"], function() {
            Route::get("", [EventApiController::class, 'index']); // getting all events

            Route::post('/create', [EventApiController::class, 'createEvent']); // create events

            Route::get('/{idOrSlug}', [EventApiController::class, 'getEvent']); // getting specific event(s)

            Route::get("/{event_id}/confectionary/{allOrId}", [ConfectionaryApiController::class, "getEventConfectionary"]);

            Route::post('/interested/{event_id}',[EventApiController::class, 'interestedEvent']);

            Route::post('/book-event/{event_id}', [EventApiController::class, 'bookEvent']);

            Route::get("/verify-ticket/{purchase_ticket_id}", [TicketApiController::class, "verifyTicket"]); // verify ticket

            Route::middleware('ticket-owner')->group(function() {
                Route::get("/ticket/{purchase_ticket_id}", [TicketApiController::class, "getPurchasedTickets"]); // Ticket owner middleWare

                Route::post("/ticket/{purchase_ticket_id}/invites", [TicketApiController::class, "sendTicketInvite"]); // Ticket owner middleWare
            });

            // Route::post("/order", [EventApiController::class, ""]);
            // Route::post("/cart", [EventApiController::class, ""]);
        });

        // Manage Event Endpoints By Event Owner
        Route::group(["prefix" => "manage-event", 'middleware' => 'event-owner'], static function() {

            Route::get("/{event_id}/roles", [RolePermissionApiController::class, "getRolesToEvent"]);
            Route::post("/{event_id}/roles", [RolePermissionApiController::class, "addRoleToEvent"]);
            Route::post('/{event_id}/assign-role',[RolePermissionApiController::class, 'assignRole']);

            Route::delete('/{event_id}/delete-role',[RolePermissionApiController::class, 'deleteRole']);

            Route::post('/{event_id}/itinerary', [ItineraryApiController::class, 'addItinerary']);
            Route::get('/{event_id}/itinerary/{allOrId}', [ItineraryApiController::class, 'getItineraries']);
            Route::delete('/{event_id}/itinerary', [ItineraryApiController::class, 'deleteItineraries']);
            Route::put('/{event_id}/itinerary/{id}', [ItineraryApiController::class, 'updateItineraryToDone']);

            Route::get("/{event_id}/confectionary/{allOrId}", [ConfectionaryApiController::class, "getEventConfectionary"]);
            Route::post("/{event_id}/confectionary", [ConfectionaryApiController::class, "addEventConfectionary"]);
            Route::post("/{event_id}/confectionary/{confectionary_id}", [ConfectionaryApiController::class, "updateEventConfectionary"]);

            Route::delete("/{event_id}/confectionary", [ConfectionaryApiController::class, "deleteEventConfectionary"]);
            Route::delete("/{event_id}/confectionary/", [ConfectionaryApiController::class, "deleteEventConfectionary"]);
            Route::delete("/{event_id}/confectionary/{confectionary_id}/images", [ConfectionaryApiController::class, "deleteEventConfectionaryImage"]);
            Route::delete("/{event_id}/confectionary/{confectionary_id}/attachments", [ConfectionaryApiController::class, "deleteConfectionaryAttachment"]);

            Route::post("/add-worker", [EventApiController::class, "addEventWorker"]);
            Route::delete("/delete-workers", [EventApiController::class, "deleteEventWorkers"]);

            Route::post('/ticket/{event_id}', [TicketApiController::class, 'addTickets']);

            Route::get('/feedback/{event_id}', [EventFeedbackApiController::class, 'getEventFeedbacks']);

        });

        // Admin Endpoint
        Route::group(['prefix' => 'admin', ['middleware' => 'admin']], static function () {
            Route::patch("events/featured/{id?}",[AdminController::class, 'featuredEvent']);
        });

    });

    Route::get("/events/ticket/{purchased_ticket_id}/invite/accept-or-reject/{invitation_code}", [TicketApiController::class, "updateTicketInvitation"])->name('events.invitation_url'); // verify ticket

    Route::post('/callback_url/{gateway}/webhook', [PaymentApiController::class, "handleWebHook"]  );
});

