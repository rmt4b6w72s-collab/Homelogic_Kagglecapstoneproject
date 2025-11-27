<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\LocationService;
use App\Models\Facility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'provider_code' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Check if email exists in multiple facilities
        $usersWithEmail = \App\Models\User::where('email', $credentials['email'])->get();
        $facilityIds = $usersWithEmail->pluck('facility_id')->filter()->unique()->values();
        $hasMultipleFacilities = $facilityIds->count() > 1;
        
        // If email exists in multiple facilities, provider_code is required
        if ($hasMultipleFacilities) {
            if (!$request->filled('provider_code')) {
                return response()->json([
                    'message' => 'This email is registered in multiple facilities. Please provide a provider code.',
                    'requires_provider_code' => true,
                ], 422);
            }
            
            // Find facility by provider code
            $facility = Facility::whereRaw('LOWER(provider_code) = ?', [strtolower($request->provider_code)])->first();
            
            if (!$facility) {
                return response()->json([
                    'message' => 'Invalid provider code',
                ], 422);
            }
            
            // Find user with this email in the specified facility
            $user = \App\Models\User::where('email', $credentials['email'])
                ->where('facility_id', $facility->id)
                ->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'This email is not registered in the facility with the provided provider code.',
                ], 401);
            }
            
            // Verify password
            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }
            
            // Manually log in the user
            Auth::login($user);
        } else {
            // Single facility or no facility - use normal authentication
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }
        }

        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user?->is_active) {
                // Immediately end the session and block login for inactive accounts
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => 'This account has been deactivated. Please contact an administrator.',
                ], 403);
            }

            // Validate provider code if provided (for single-facility emails, provider_code is optional but validated if provided)
            if ($request->filled('provider_code') && !$hasMultipleFacilities) {
                // Super admins don't have facility_id, so skip provider code validation
                if ($user->role !== 'super_admin') {
                    // Find facility by provider code (case-insensitive)
                    $facility = Facility::whereRaw('LOWER(provider_code) = ?', [strtolower($request->provider_code)])->first();

                    if (!$facility) {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return response()->json([
                            'message' => 'Invalid provider code',
                        ], 422);
                    }

                    // Verify user belongs to this facility
                    if ($user->facility_id !== $facility->id) {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return response()->json([
                            'message' => 'You don\'t belong to this facility',
                        ], 403);
                    }
                }
            }

            // Location-based access control for caregivers
            $locationService = app(LocationService::class);
            $locationCheckResult = $this->validateUserLocation($user, $request, $locationService);
            
            if ($locationCheckResult !== null) {
                // Location check failed
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => $locationCheckResult['message'],
                    'distance' => $locationCheckResult['distance'] ?? null,
                ], 403);
            }

            $token = $user->createToken('api-token')->plainTextToken;
            
            // Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();

            // Log login
            ActivityLogService::login($user, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'user' => $this->transformUser($user),
                'token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Log logout before deleting tokens
        if ($user) {
            ActivityLogService::logout($user, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return response()->json($this->transformUser($user));
        } catch (\Exception $e) {
            \Log::error('Error in user endpoint: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to load user data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Attach application timezone metadata to the user payload.
     */
    protected function transformUser(?\App\Models\User $user): array
    {
        if (!$user) {
            return [];
        }

        // Make sure commonly-used relationships are available in the API payload.
        // This includes the user's assigned branch and its facility so that
        // frontend pages (e.g. profile, housekeeping, medications) can safely
        // reference `user.assigned_branch` without needing extra API calls.
        $user->loadMissing([
            'assignedBranch.facility.modules',
            'facility.modules',
        ]);

        $appTimezone = config('app.timezone', 'UTC');
        $now = Carbon::now($appTimezone);

        $payload = $user->toArray();
        $payload['app_timezone'] = $appTimezone;
        $payload['app_timezone_abbr'] = $now->format('T');
        $payload['app_timezone_offset'] = $now->format('P');
        $payload['app_current_time'] = $now->toIso8601String();
        
        // Include facility branding if available
        $facility = $user->facility ?? ($user->assignedBranch ? $user->assignedBranch->facility : null);
        
        if ($facility) {
            $payload['facility_branding'] = $facility->branding;
            
            // Include enabled modules for this facility
            $enabledModules = $facility->modules()
                ->where('is_enabled', true)
                ->pluck('module')
                ->toArray();
            $payload['enabled_modules'] = $enabledModules;
        } else {
            // Default branding for super admin / HomeLogic360
            $payload['facility_branding'] = [
                'name' => 'HomeLogic360',
                'logo' => asset('images/logonew.png'),
                'primary_color' => '#1E3A5F', // Dark blue from logo
                'secondary_color' => '#86EFAC', // Light green from logo
                'accent_color' => '#FFFFFF', // White from logo
            ];
            
            // Super admins have access to all modules
            if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
                $payload['enabled_modules'] = array_keys(\App\Constants\Modules::all());
            } else {
                $payload['enabled_modules'] = [];
            }
        }

        // Include effective permissions for navigation checks
        // Get all permissions the user effectively has (considering facility overrides)
        $payload['permissions'] = $this->getEffectivePermissions($user);

        return $payload;
    }

    /**
     * Get all effective permissions for a user (considering facility-specific overrides)
     */
    protected function getEffectivePermissions(\App\Models\User $user): array
    {
        // Super admins have all permissions
        if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
            return \App\Models\Permission::pluck('name')->toArray();
        }

        $userRoles = $user->roles()->get();
        
        if ($userRoles->isEmpty()) {
            return [];
        }

        // Get facility for module access checks
        $facility = $user->facility ?? ($user->assignedBranch ? $user->assignedBranch->facility : null);
        
        // Get enabled modules for this facility
        $enabledModules = [];
        if ($facility) {
            $enabledModules = $facility->modules()
                ->where('is_enabled', true)
                ->pluck('module')
                ->toArray();
        }

        // Get facility-specific overrides for user's roles
        $effectivePermissions = [];
        
        foreach ($userRoles as $role) {
            // Get global permissions for this role
            $roleGlobalPermissions = $role->permissions()->pluck('name')->toArray();
            
            // Get facility overrides for this role (if facility exists)
            $facilityOverrides = collect();
            if ($facility) {
                $facilityOverrides = $facility->rolePermissions()
                    ->where('role_id', $role->id)
                    ->with('permission')
                    ->get()
                    ->keyBy(function ($item) {
                        return $item->permission ? $item->permission->name : null;
                    })
                    ->filter(function ($item) {
                        return $item->permission !== null;
                    });
            }

            // Merge: facility overrides take precedence
            foreach ($roleGlobalPermissions as $permissionName) {
                $isAllowed = true;
                
                if ($facilityOverrides->has($permissionName)) {
                    // Facility override exists - use it
                    $isAllowed = $facilityOverrides[$permissionName]->is_allowed;
                }
                
                if ($isAllowed) {
                    // Check if permission requires module access
                    try {
                        $module = \App\Helpers\ModulePermissionMapper::getModuleForPermission($permissionName);
                        
                        if ($module === null) {
                            // Permission doesn't map to a module, allow it
                            $effectivePermissions[] = $permissionName;
                        } elseif ($facility && in_array($module, $enabledModules)) {
                            // Module is enabled, allow permission
                            $effectivePermissions[] = $permissionName;
                        }
                        // If module is disabled, don't add permission
                    } catch (\Exception $e) {
                        // If ModulePermissionMapper fails, allow the permission to prevent breaking the app
                        \Log::warning('ModulePermissionMapper error for permission: ' . $permissionName, [
                            'error' => $e->getMessage(),
                        ]);
                        $effectivePermissions[] = $permissionName;
                    }
                }
                // If explicitly denied, don't add permission
            }
        }

        // Remove duplicates
        return collect($effectivePermissions)->unique()->values()->toArray();
    }

    /**
     * Validate user location for caregivers
     * 
     * @param \App\Models\User $user
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\LocationService $locationService
     * @return array|null Returns error array on failure, null on success
     */
    protected function validateUserLocation($user, Request $request, LocationService $locationService): ?array
    {
        // Check if location checking is enabled globally
        if (!config('location.enabled', true)) {
            return null;
        }

        // Skip location check for non-caregivers, super admins, or users with bypass enabled
        if (!$user->isCaregiver() 
            || $user->role === 'super_admin' 
            || $user->location_check_bypass) {
            return null;
        }

        // Get user's location coordinates (from browser or IP fallback)
        $userLat = $request->input('latitude');
        $userLon = $request->input('longitude');
        $userIp = $request->ip();

        // If browser geolocation not provided, try IP-based geolocation
        if ($userLat === null || $userLon === null) {
            $ipLocation = $locationService->getLocationFromIp($userIp);
            if ($ipLocation) {
                $userLat = $ipLocation['latitude'];
                $userLon = $ipLocation['longitude'];
                Log::info('Using IP-based geolocation for login', [
                    'user_id' => $user->id,
                    'ip' => $userIp,
                ]);
            } else {
                // No location available - allow login but log warning
                Log::warning('Location check skipped - no coordinates available', [
                    'user_id' => $user->id,
                    'ip' => $userIp,
                ]);
                return null;
            }
        }

        // Validate user coordinates
        if (!$locationService->validateCoordinates($userLat, $userLon)) {
            Log::warning('Invalid user coordinates provided', [
                'user_id' => $user->id,
                'latitude' => $userLat,
                'longitude' => $userLon,
            ]);
            return null; // Allow login if coordinates are invalid (graceful degradation)
        }

        // Get assigned branch or facility coordinates
        $branch = $user->assignedBranch;
        $facility = $user->facility ?? ($branch ? $branch->facility : null);

        $targetLat = null;
        $targetLon = null;
        $targetName = null;

        // Prefer branch coordinates, fallback to facility coordinates
        if ($branch && $branch->hasCoordinates()) {
            $targetLat = $branch->latitude;
            $targetLon = $branch->longitude;
            $targetName = $branch->name;
        } elseif ($facility && $facility->hasCoordinates()) {
            $targetLat = $facility->latitude;
            $targetLon = $facility->longitude;
            $targetName = $facility->name;
        }

        // If no coordinates available for branch/facility, allow login but log warning
        if ($targetLat === null || $targetLon === null) {
            Log::warning('Location check skipped - branch/facility has no coordinates', [
                'user_id' => $user->id,
                'branch_id' => $branch?->id,
                'facility_id' => $facility?->id,
            ]);
            return null;
        }

        // Calculate distance
        $distanceKm = $locationService->calculateDistance(
            $userLat,
            $userLon,
            $targetLat,
            $targetLon
        );

        // Check if within allowed distance
        if (!$locationService->isWithinAllowedDistance($distanceKm)) {
            $formattedDistance = $locationService->formatDistance($distanceKm);
            
            Log::warning('Login blocked due to distance', [
                'user_id' => $user->id,
                'distance_km' => $distanceKm,
                'target' => $targetName,
            ]);

            return [
                'message' => "You are too far from your assigned location ({$targetName}). You are {$formattedDistance} away, but must be within " . LocationService::MAX_LOGIN_DISTANCE_KM . "km to log in.",
                'distance' => $distanceKm,
            ];
        }

        // Location check passed
        Log::info('Location check passed', [
            'user_id' => $user->id,
            'distance_km' => $distanceKm,
            'target' => $targetName,
        ]);

        return null;
    }
}

