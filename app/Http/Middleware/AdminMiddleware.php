<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Bootstrap case: on a brand-new install with a single user, allow
        // access so the first admin can configure roles. Use count() rather
        // than User::all()->count() to avoid loading every row.
        if (User::count() === 1) {
            return $next($request);
        }

        // can() returns false for an unauthenticated user or a missing
        // permission, so it never throws PermissionDoesNotExist (unlike
        // hasPermissionTo()). The 'assign role' permission is the seeded
        // gate for role/permission/user administration.
        if (! Auth::user()?->can('assign role')) {
            abort(401);
        }

        return $next($request);
    }
}