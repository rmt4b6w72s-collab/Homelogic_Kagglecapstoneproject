<?php

namespace App\Http\Controllers\Api;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacilityPermissionController extends BaseApiController
{
    /**
     * Get all permissions data for a facility
     */
    public function show(Request $request, int $facilityId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized.', 401);
            }

            $facility = Facility::findOrFail($facilityId);

            // Check access: super admin OR facility admin of this facility
            $isSuperAdmin = $user->role === 'super_admin' || $user->hasRole('super_admin');
            $isFacilityAdmin = ($user->role === 'administrator' || $user->role === 'admin' || $user->hasRole('administrator') || $user->hasRole('admin'))
                && $user->facility_id === $facilityId;

            if (!$isSuperAdmin && !$isFacilityAdmin) {
                return $this->error('Unauthorized. Super admin or facility admin access required.', 403);
            }

            // Get enabled modules
            $enabledModules = $facility->modules()
                ->where('is_enabled', true)
                ->pluck('module')
                ->toArray();

            // Get all modules with their status
            $allModules = [];
            foreach (Modules::all() as $moduleKey => $moduleName) {
                $allModules[] = [
                    'key' => $moduleKey,
                    'name' => $moduleName,
                    'enabled' => in_array($moduleKey, $enabledModules),
                ];
            }

            // Get role permissions for administrator and caregiver
            $administratorRole = Role::where('name', 'administrator')->orWhere('name', 'admin')->first();
            $caregiverRole = Role::where('name', 'caregiver')->first();

            $rolePermissions = [];
            
            if ($administratorRole) {
                try {
                    $rolePermissions['administrator'] = $this->getRolePermissionsData($facility, $administratorRole);
                } catch (\Exception $e) {
                    \Log::error('Error getting administrator permissions: ' . $e->getMessage());
                    // Continue without administrator permissions
                }
            }
            
            if ($caregiverRole) {
                try {
                    $rolePermissions['caregiver'] = $this->getRolePermissionsData($facility, $caregiverRole);
                } catch (\Exception $e) {
                    \Log::error('Error getting caregiver permissions: ' . $e->getMessage());
                    // Continue without caregiver permissions
                }
            }

            return $this->success([
                'facility' => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                ],
                'modules' => $allModules,
                'role_permissions' => $rolePermissions,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in FacilityPermissionController@show: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return $this->error('Failed to load permissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update facility module access
     */
    public function updateModules(Request $request, int $facilityId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized.', 401);
        }

        $facility = Facility::findOrFail($facilityId);

        // Only super admins can update modules
        $isSuperAdmin = $user->role === 'super_admin' || $user->hasRole('super_admin');

        if (!$isSuperAdmin) {
            return $this->error('Unauthorized. Only super administrators can update module access.', 403);
        }

        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'string|in:' . implode(',', array_keys(Modules::all())),
        ]);

        // Update modules
        $allModules = array_keys(Modules::all());
        foreach ($allModules as $module) {
            if (in_array($module, $validated['modules'])) {
                $facility->enableModule($module);
            } else {
                $facility->disableModule($module);
            }
        }

        return $this->success([
            'message' => 'Modules updated successfully',
            'modules' => $validated['modules'],
        ]);
    }

    /**
     * Get role permissions for a facility
     */
    public function getRolePermissions(Request $request, int $facilityId, int $roleId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized.', 401);
        }

        $facility = Facility::findOrFail($facilityId);
        $role = Role::findOrFail($roleId);

        // Check access: super admin OR facility admin of this facility
        $isSuperAdmin = $user->role === 'super_admin' || $user->hasRole('super_admin');
        $isFacilityAdmin = ($user->role === 'administrator' || $user->role === 'admin' || $user->hasRole('administrator') || $user->hasRole('admin'))
            && $user->facility_id === $facilityId;

        if (!$isSuperAdmin && !$isFacilityAdmin) {
            return $this->error('Unauthorized. Super admin or facility admin access required.', 403);
        }

        // Facility admin can only access administrator and caregiver roles
        if ($isFacilityAdmin && !$isSuperAdmin) {
            $roleName = strtolower($role->name);
            if ($roleName !== 'administrator' && $roleName !== 'admin' && $roleName !== 'caregiver') {
                return $this->error('Unauthorized. Facility admin can only manage administrator and caregiver permissions.', 403);
            }
        }

        $data = $this->getRolePermissionsData($facility, $role);

        return $this->success($data);
    }

    /**
     * Update role permissions for a facility
     */
    public function updateRolePermissions(Request $request, int $facilityId, int $roleId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthorized.', 401);
        }

        $facility = Facility::findOrFail($facilityId);
        $role = Role::findOrFail($roleId);

        // Check access: super admin OR facility admin of this facility
        $isSuperAdmin = $user->role === 'super_admin' || $user->hasRole('super_admin');
        $isFacilityAdmin = ($user->role === 'administrator' || $user->role === 'admin' || $user->hasRole('administrator') || $user->hasRole('admin'))
            && $user->facility_id === $facilityId;

        if (!$isSuperAdmin && !$isFacilityAdmin) {
            return $this->error('Unauthorized. Super admin or facility admin access required.', 403);
        }

        // Facility admin can only update administrator and caregiver roles
        if ($isFacilityAdmin && !$isSuperAdmin) {
            $roleName = strtolower($role->name);
            if ($roleName !== 'administrator' && $roleName !== 'admin' && $roleName !== 'caregiver') {
                return $this->error('Unauthorized. Facility admin can only manage administrator and caregiver permissions.', 403);
            }
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Sync role permissions
        $facility->syncRolePermissions($roleId, $validated['permissions']);

        // Return updated permissions data
        $data = $this->getRolePermissionsData($facility, $role);

        return $this->success([
            'message' => 'Role permissions updated successfully',
            'role_permissions' => $data,
        ]);
    }

    /**
     * Get role permissions data structure
     */
    private function getRolePermissionsData(Facility $facility, Role $role): array
    {
        // Get global role permissions
        $globalPermissions = $role->permissions()->pluck('permissions.name')->toArray();

        // Get facility-specific overrides
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

        // Get all permissions grouped by category
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get();
        
        if ($allPermissions->isEmpty()) {
            return [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
                'global_permissions' => [],
                'permissions_by_group' => [],
            ];
        }
        
        $permissionsByGroup = $allPermissions->groupBy('group');

        // Build permissions data
        $permissionsData = [];
        foreach ($permissionsByGroup as $group => $permissions) {
            $groupPermissions = [];
            foreach ($permissions as $permission) {
                $hasGlobal = in_array($permission->name, $globalPermissions);
                $hasOverride = $facilityOverrides->has($permission->name);
                
                if ($hasOverride) {
                    $isAllowed = $facilityOverrides[$permission->name]->is_allowed;
                } else {
                    $isAllowed = $hasGlobal;
                }

                $groupPermissions[] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'is_allowed' => $isAllowed,
                    'is_global' => $hasGlobal,
                    'has_override' => $hasOverride,
                ];
            }

            $permissionsData[] = [
                'group' => $group ?: 'Other',
                'permissions' => $groupPermissions,
            ];
        }

        return [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'global_permissions' => $globalPermissions,
            'permissions_by_group' => $permissionsData,
        ];
    }
}
