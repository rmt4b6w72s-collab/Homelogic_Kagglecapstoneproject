<?php

namespace App\Http\Middleware;

use App\Models\Facility;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetFacilityContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $facility = null;

        // Super admins don't have facility context restrictions
        if ($user && $user->role === 'super_admin') {
            // Check if accessing via subdomain
            $subdomain = $this->extractSubdomain($request);
            if ($subdomain) {
                $facility = Facility::where('subdomain', $subdomain)->first();
            }
        } elseif ($user) {
            // Try to get facility from subdomain first
            $subdomain = $this->extractSubdomain($request);
            if ($subdomain) {
                $facility = Facility::where('subdomain', $subdomain)->first();
                
                // Verify user belongs to this facility
                if ($facility && $user->facility_id !== $facility->id) {
                    abort(403, 'You do not have access to this facility.');
                }
            } else {
                // Use user's facility_id for path-based routing
                $facility = $user->facility;
            }
        }

        // Set facility in request and app container
        if ($facility) {
            $request->merge(['facility' => $facility]);
            app()->instance('facility', $facility);
        }

        return $next($request);
    }

    /**
     * Extract subdomain from request
     */
    private function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // If we have more than 2 parts, the first is likely the subdomain
        // e.g., evergreen.yourapp.com -> evergreen
        if (count($parts) > 2) {
            return $parts[0];
        }

        return null;
    }
}
