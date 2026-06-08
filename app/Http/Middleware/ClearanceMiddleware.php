<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ClearanceMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {        
        // Users who can administer roles bypass all post-level checks.
        // can() is used instead of hasPermissionTo() so an unauthenticated
        // user or a missing permission returns 401 rather than throwing.
        if (Auth::user()?->can('assign role')) {
            return $next($request);
        }

        if ($request->is('posts/create'))//If user is creating a post
         {
            if (!Auth::user()?->can('create post'))
         {
                abort('401');
            } 
         else {
                return $next($request);
            }
        }

        if ($request->is('posts/*/edit')) //If user is editing a post
         {
            if (!Auth::user()?->can('edit post')) {
                abort('401');
            } else {
                return $next($request);
            }
        }

        if ($request->isMethod('Delete')) //If user is deleting a post
         {
            if (!Auth::user()?->can('delete post')) {
                abort('401');
            } 
         else 
         {
                return $next($request);
            }
        }

        return $next($request);
    }
}