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

        $expectedApiKey = config('services.my_api.api_key');
        $expectedOrigin = config('services.my_api.origin');

        if ($apiKey !== $expectedApiKey || $origin !== $expectedOrigin) {
            return response()->json([
                'message' => 'Forbidden.'
            ], 403);
        }

        return $next($request);
    }
}
