<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string ...$roles The roles allowed to access the route
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated and has a permitted role
        if ($request->user() && in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        // Redirect if unauthorized
        // You can change this to abort(403) if you prefer a standard error page
        return redirect()->route('login')->withErrors(['error' => 'Unauthorized access.']);
    }
}