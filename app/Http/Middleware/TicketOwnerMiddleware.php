<?php

namespace App\Http\Middleware;

use App\Models\PurchasedTicket;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TicketOwnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $purchasedTicket_id = $request->purchase_ticket_id;
        $purchasedTicket = PurchasedTicket::find($purchasedTicket_id);
        if(!$purchasedTicket || $purchasedTicket->user_id !== $request->user()->id) {
            return response()->json([
                "message" => "Unauthorized"
            ]);
        }

        return $next($request);
    }
}
