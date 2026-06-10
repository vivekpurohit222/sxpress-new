<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Allows SuperAdmin and Admin roles only.
     *
     * NOTE: Prefer using route-level `role:SuperAdmin|Admin` middleware instead.
     * This middleware is kept for backward compatibility with any legacy references.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'Admin'])) {
            abort(403, 'Access denied. Admin or SuperAdmin role required.');
        }

        return $next($request);
    }
}
