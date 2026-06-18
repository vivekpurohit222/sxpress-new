<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CheckPermission middleware.
 *
 * Usage in routes: ->middleware('permission:gr')
 *
 * - super_admin → always allowed
 * - branch_manager → allowed if branch_permissions has the permission for their branch
 * - agent → allowed if user_permissions has the permission for them
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->can_access($permission)) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
