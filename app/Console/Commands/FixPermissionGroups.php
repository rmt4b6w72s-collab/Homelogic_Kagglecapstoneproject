<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;

class FixPermissionGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:fix-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update permission groups for existing permissions based on PermissionSeeder mapping';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Fixing permission groups...');
        $this->line('');

        // Permission groups mapping from PermissionSeeder
        $permissionGroups = [
            // Panel Access
            'view_admin_panel' => 'Panel Access',
            'view_dashboard' => 'Panel Access',
            
            // Staff Management
            'view_users' => 'Staff Management',
            'create_users' => 'Staff Management',
            'edit_users' => 'Staff Management',
            'delete_users' => 'Staff Management',
            'assign_roles_users' => 'Staff Management',
            'view_own_profile' => 'Staff Management',
            'edit_own_profile' => 'Staff Management',
            
            // Role Management
            'view_roles' => 'Role Management',
            'create_roles' => 'Role Management',
            'edit_roles' => 'Role Management',
            'delete_roles' => 'Role Management',
            'assign_permissions_roles' => 'Role Management',
            
            // Resident Management
            'view_residents' => 'Resident Management',
            'create_residents' => 'Resident Management',
            'edit_residents' => 'Resident Management',
            'delete_residents' => 'Resident Management',
            'view_resident_medications' => 'Resident Management',
            'manage_resident_medications' => 'Resident Management',
            'view_resident_documents' => 'Resident Management',
            'upload_resident_documents' => 'Resident Management',
            
            // Medication Management
            'view_medications' => 'Medication Management',
            'create_medications' => 'Medication Management',
            'edit_medications' => 'Medication Management',
            'delete_medications' => 'Medication Management',
            'administer_medications' => 'Medication Management',
            'view_medication_history' => 'Medication Management',
            
            // Leave Management
            'view_leave_requests' => 'Leave Management',
            'create_leave_requests' => 'Leave Management',
            'edit_leave_requests' => 'Leave Management',
            'delete_leave_requests' => 'Leave Management',
            'approve_leave_requests' => 'Leave Management',
            'view_own_leave_requests' => 'Leave Management',
            'edit_own_leave_requests' => 'Leave Management',
            
            // Assignment Management
            'view_assignments' => 'Assignment Management',
            'create_assignments' => 'Assignment Management',
            'edit_assignments' => 'Assignment Management',
            'delete_assignments' => 'Assignment Management',
            'view_resident_manager' => 'Assignment Management',
            
            // Facility Management
            'view_facilities' => 'Facility Management',
            'create_facilities' => 'Facility Management',
            'edit_facilities' => 'Facility Management',
            'delete_facilities' => 'Facility Management',
            'view_branches' => 'Facility Management',
            'create_branches' => 'Facility Management',
            'edit_branches' => 'Facility Management',
            'delete_branches' => 'Facility Management',
            
            // Reports & Analytics
            'view_reports' => 'Reports & Analytics',
            'export_reports' => 'Reports & Analytics',
            'view_staff_reports' => 'Reports & Analytics',
            'view_resident_reports' => 'Reports & Analytics',
            'view_medication_reports' => 'Reports & Analytics',
            'view_leave_reports' => 'Reports & Analytics',
            
            // System Administration
            'view_system_logs' => 'System Administration',
            'export_system_logs' => 'System Administration',
            'view_audit_trail' => 'System Administration',
            'manage_system_settings' => 'System Administration',
            'backup_database' => 'System Administration',
            'restore_database' => 'System Administration',
            
            // Notifications
            'view_notifications' => 'Notifications',
            'manage_notifications' => 'Notifications',
            'send_notifications' => 'Notifications',
        ];

        $updated = 0;
        $notFound = [];

        foreach ($permissionGroups as $permissionName => $group) {
            $permission = Permission::where('name', $permissionName)->first();
            
            if ($permission) {
                if ($permission->group !== $group) {
                    $permission->group = $group;
                    $permission->save();
                    $updated++;
                    $this->line("  ✅ Updated {$permissionName} → {$group}");
                }
            } else {
                $notFound[] = $permissionName;
            }
        }

        $this->line('');
        $this->info("✅ Updated {$updated} permission groups");

        if (!empty($notFound)) {
            $this->warn("⚠️  The following permissions were not found in database:");
            foreach ($notFound as $name) {
                $this->line("   - {$name}");
            }
        }

        // Check for permissions with NULL groups
        $nullGroupCount = Permission::whereNull('group')->orWhere('group', '')->count();
        if ($nullGroupCount > 0) {
            $this->warn("⚠️  {$nullGroupCount} permissions still have NULL or empty group. These will show as 'Other'.");
        }

        return 0;
    }
}

