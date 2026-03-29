<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyXenditWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $headerToken = $request->header('x-callback-token');
        $storedToken = config('services.xendit.webhook_token');

        if (!$headerToken || $headerToken !== $storedToken) {
            return response()->json(['message' => 'Unauthorized Webhook'], 403);
        }

        return $next($request);
    }
}
