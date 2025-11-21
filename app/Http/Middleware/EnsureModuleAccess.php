<?php

namespace App\Http\Middleware;

use App\Constants\Modules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $module  The module name to check access for
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Super admins bypass module restrictions
        if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if user has access to the module
        if (!$user->hasModuleAccess($module)) {
            return response()->json([
                'message' => 'This module is not available for your facility.',
                'module' => Modules::getDisplayName($module),
            ], 403);
        }

        return $next($request);
    }
}
