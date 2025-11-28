<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // First, create basic permissions
        $this->createBasicPermissions();
        
        // Define role permissions
        $rolePermissions = [
            'admin' => [
                // Admin access - full management
                'view_admin_panel',
                'view_dashboard',
                'view_users', 'create_users', 'edit_users', 'delete_users',
                'view_own_profile', 'edit_own_profile',
                'view_residents', 'create_residents', 'edit_residents', 'delete_residents',
                'view_medications', 'create_medications', 'edit_medications', 'delete_medications',
                'view_appointments', 'create_appointments', 'edit_appointments', 'delete_appointments',
                'view_vital_signs', 'create_vital_signs', 'edit_vital_signs', 'delete_vital_signs',
                'view_incidents', 'create_incidents', 'edit_incidents', 'delete_incidents',
                'view_reports', 'export_reports',
            ],
            'caregiver' => [
                // Caregiver access - limited to assigned residents
                'view_admin_panel',
                'view_dashboard',
                'view_own_profile', 'edit_own_profile',
                'view_residents',
                'view_medications',
                'view_appointments', 'create_appointments',
                'view_vital_signs', 'create_vital_signs',
                'view_incidents', 'create_incidents',
            ],
            'family_member' => [
                // Very limited access
                'view_own_profile', 'edit_own_profile',
                'view_residents',
                'view_resident_medications',
                'view_notifications',
            ]
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $role = Role::create([
                    'name' => $roleName,
                    'guard_name' => 'web'
                ]);
                $this->command->info("Created role: {$roleName}");
            }

            // Clear existing permissions
            $role->permissions()->detach();

            // Assign specific permissions
            foreach ($permissionNames as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    $role->givePermissionTo($permission);
                } else {
                    $this->command->warn("Permission not found: {$permissionName}");
                }
            }
            $this->command->info("Assigned " . count($permissionNames) . " permissions to {$roleName}");
        }

        $this->command->info('Role permissions setup completed!');
        
        // Show summary
        $this->command->line('');
        $this->command->line('📋 Permission Summary:');
        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            $count = $role->permissions()->count();
            $this->command->line("  • {$roleName}: {$count} permissions");
        }
    }

    private function createBasicPermissions(): void
    {
        $permissions = [
            'view_admin_panel',
            'view_dashboard',
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_own_profile', 'edit_own_profile',
            'view_residents', 'create_residents', 'edit_residents', 'delete_residents',
            'view_medications', 'create_medications', 'edit_medications', 'delete_medications',
            'view_appointments', 'create_appointments', 'edit_appointments', 'delete_appointments',
            'view_vital_signs', 'create_vital_signs', 'edit_vital_signs', 'delete_vital_signs',
            'view_reports', 'export_reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info("Created " . count($permissions) . " basic permissions");
    }
}
