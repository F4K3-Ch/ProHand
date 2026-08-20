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
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!in_array($user->role, $roles, true)) {
            return redirect()
                ->route($user->role . '.dashboard')
                ->with('error', 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
