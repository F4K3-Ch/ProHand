<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AccessToken;

class AccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        $origin = $request->header('Origin');

        if (empty($apiKey) || empty($origin)) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }

        $access = AccessToken::find(1);

        if (!$access) {
            return response()->json([
                'message' => 'Server error: Access Token not found'
            ], 500);
        }

        if ($apiKey !== $access->token || $origin !== $access->origin) {
            return response()->json([
                'message' => 'Forbidden.'
            ], 403);
        }

        return $next($request);
    }
}
