<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CheckRole middleware — backward compatible with existing route definitions.
 *
 * Usage: ->middleware('role:SuperAdmin')
 *        ->middleware('role:SuperAdmin|BranchManager')
 *
 * Maps Spatie-style role names to the new role column values:
 *   SuperAdmin → super_admin
 *   BranchManager → branch_manager
 *   Agent → agent
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $allowed = array_map('trim', explode('|', $roles));

        foreach ($allowed as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}
