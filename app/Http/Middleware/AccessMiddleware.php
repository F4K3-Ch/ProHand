<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AccessToken;

class AccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }

        $expectedApiKey = config('services.my_api.api_key');

        if (!hash_equals($expectedApiKey, $apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.'
            ], 403);
        }

        return $next($request);
    }
}