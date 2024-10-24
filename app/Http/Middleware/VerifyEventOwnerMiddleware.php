<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEventOwnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event_id = $request->event_id;
        $event = Event::find($event_id);
        if($event->user_id !== $request->user()->id) {
            return response()->json([
                "message" => "Unauthorized"
            ]);
        }

        return $next($request);
    }
}
