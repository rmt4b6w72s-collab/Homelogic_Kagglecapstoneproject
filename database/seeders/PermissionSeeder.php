<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Panel Access
            ['name' => 'view_admin_panel', 'group' => 'Panel Access', 'description' => 'Access to admin panel'],
            ['name' => 'view_dashboard', 'group' => 'Panel Access', 'description' => 'Access to dashboard'],
            
            // Staff Management
            ['name' => 'view_users', 'group' => 'Staff Management', 'description' => 'View staff members'],
            ['name' => 'create_users', 'group' => 'Staff Management', 'description' => 'Create new staff members'],
            ['name' => 'edit_users', 'group' => 'Staff Management', 'description' => 'Edit staff member information'],
            ['name' => 'delete_users', 'group' => 'Staff Management', 'description' => 'Delete staff members'],
            ['name' => 'assign_roles_users', 'group' => 'Staff Management', 'description' => 'Assign roles to users'],
            ['name' => 'view_own_profile', 'group' => 'Staff Management', 'description' => 'View own profile'],
            ['name' => 'edit_own_profile', 'group' => 'Staff Management', 'description' => 'Edit own profile'],
            
            // Role Management
            ['name' => 'view_roles', 'group' => 'Role Management', 'description' => 'View roles'],
            ['name' => 'create_roles', 'group' => 'Role Management', 'description' => 'Create new roles'],
            ['name' => 'edit_roles', 'group' => 'Role Management', 'description' => 'Edit roles'],
            ['name' => 'delete_roles', 'group' => 'Role Management', 'description' => 'Assign permissions to roles'],
            ['name' => 'assign_permissions_roles', 'group' => 'Role Management', 'description' => 'Assign permissions to roles'],
            
            // Resident Management
            ['name' => 'view_residents', 'group' => 'Resident Management', 'description' => 'View residents'],
            ['name' => 'create_residents', 'group' => 'Resident Management', 'description' => 'Create new residents'],
            ['name' => 'edit_residents', 'group' => 'Resident Management', 'description' => 'Edit resident information'],
            ['name' => 'delete_residents', 'group' => 'Resident Management', 'description' => 'Delete residents'],
            ['name' => 'view_resident_medications', 'group' => 'Resident Management', 'description' => 'View resident medications'],
            ['name' => 'manage_resident_medications', 'group' => 'Resident Management', 'description' => 'Manage resident medications'],
            ['name' => 'view_resident_documents', 'group' => 'Resident Management', 'description' => 'View resident documents'],
            ['name' => 'upload_resident_documents', 'group' => 'Resident Management', 'description' => 'Upload resident documents'],
            
            // Medication Management
            ['name' => 'view_medications', 'group' => 'Medication Management', 'description' => 'View medications'],
            ['name' => 'create_medications', 'group' => 'Medication Management', 'description' => 'Create medication records'],
            ['name' => 'edit_medications', 'group' => 'Medication Management', 'description' => 'Edit medication records'],
            ['name' => 'delete_medications', 'group' => 'Medication Management', 'description' => 'Delete medication records'],
            ['name' => 'administer_medications', 'group' => 'Medication Management', 'description' => 'Record medication administration'],
            ['name' => 'view_medication_history', 'group' => 'Medication Management', 'description' => 'View medication administration history'],
            
            // Leave Management
            ['name' => 'view_leave_requests', 'group' => 'Leave Management', 'description' => 'View leave requests'],
            ['name' => 'create_leave_requests', 'group' => 'Leave Management', 'description' => 'Submit leave requests'],
            ['name' => 'edit_leave_requests', 'group' => 'Leave Management', 'description' => 'Edit leave requests'],
            ['name' => 'delete_leave_requests', 'group' => 'Leave Management', 'description' => 'Delete leave requests'],
            ['name' => 'approve_leave_requests', 'group' => 'Leave Management', 'description' => 'Approve/decline leave requests'],
            ['name' => 'view_own_leave_requests', 'group' => 'Leave Management', 'description' => 'View own leave requests'],
            ['name' => 'edit_own_leave_requests', 'group' => 'Leave Management', 'description' => 'Edit own leave requests'],
            
            // Assignment Management
            ['name' => 'view_assignments', 'group' => 'Assignment Management', 'description' => 'View resident assignments'],
            ['name' => 'create_assignments', 'group' => 'Assignment Management', 'description' => 'Create resident assignments'],
            ['name' => 'edit_assignments', 'group' => 'Assignment Management', 'description' => 'Edit resident assignments'],
            ['name' => 'delete_assignments', 'group' => 'Assignment Management', 'description' => 'Delete resident assignments'],
            ['name' => 'view_resident_manager', 'group' => 'Assignment Management', 'description' => 'Access resident manager'],
            
            // Facility Management
            ['name' => 'view_facilities', 'group' => 'Facility Management', 'description' => 'View facilities'],
            ['name' => 'create_facilities', 'group' => 'Facility Management', 'description' => 'Create facilities'],
            ['name' => 'edit_facilities', 'group' => 'Facility Management', 'description' => 'Edit facilities'],
            ['name' => 'delete_facilities', 'group' => 'Facility Management', 'description' => 'Delete facilities'],
            ['name' => 'view_branches', 'group' => 'Facility Management', 'description' => 'View branches'],
            ['name' => 'create_branches', 'group' => 'Facility Management', 'description' => 'Create branches'],
            ['name' => 'edit_branches', 'group' => 'Facility Management', 'description' => 'Edit branches'],
            ['name' => 'delete_branches', 'group' => 'Facility Management', 'description' => 'Delete branches'],
            
            // Reports & Analytics
            ['name' => 'view_reports', 'group' => 'Reports & Analytics', 'description' => 'View reports'],
            ['name' => 'export_reports', 'group' => 'Reports & Analytics', 'description' => 'Export reports'],
            ['name' => 'view_staff_reports', 'group' => 'Reports & Analytics', 'description' => 'View staff reports'],
            ['name' => 'view_resident_reports', 'group' => 'Reports & Analytics', 'description' => 'View resident reports'],
            ['name' => 'view_medication_reports', 'group' => 'Reports & Analytics', 'description' => 'View medication reports'],
            ['name' => 'view_leave_reports', 'group' => 'Reports & Analytics', 'description' => 'View leave reports'],
            
            // System Administration
            ['name' => 'view_system_logs', 'group' => 'System Administration', 'description' => 'View system logs'],
            ['name' => 'export_system_logs', 'group' => 'System Administration', 'description' => 'Export system logs'],
            ['name' => 'view_audit_trail', 'group' => 'System Administration', 'description' => 'View audit trail'],
            ['name' => 'manage_system_settings', 'group' => 'System Administration', 'description' => 'Manage system settings'],
            ['name' => 'backup_database', 'group' => 'System Administration', 'description' => 'Backup database'],
            ['name' => 'restore_database', 'group' => 'System Administration', 'description' => 'Restore database'],
            
            // Notifications
            ['name' => 'view_notifications', 'group' => 'Notifications', 'description' => 'View notifications'],
            ['name' => 'manage_notifications', 'group' => 'Notifications', 'description' => 'Manage notification settings'],
            ['name' => 'send_notifications', 'group' => 'Notifications', 'description' => 'Send notifications to staff'],
            
            // Billing & Expenses
            ['name' => 'view_expenses', 'group' => 'Billing & Expenses', 'description' => 'View expenses'],
            ['name' => 'create_expenses', 'group' => 'Billing & Expenses', 'description' => 'Create expenses'],
            ['name' => 'edit_expenses', 'group' => 'Billing & Expenses', 'description' => 'Edit expenses'],
            ['name' => 'delete_expenses', 'group' => 'Billing & Expenses', 'description' => 'Delete expenses'],
            ['name' => 'view_expense_categories', 'group' => 'Billing & Expenses', 'description' => 'View expense categories'],
            ['name' => 'create_expense_categories', 'group' => 'Billing & Expenses', 'description' => 'Create expense categories'],
            ['name' => 'edit_expense_categories', 'group' => 'Billing & Expenses', 'description' => 'Edit expense categories'],
            ['name' => 'delete_expense_categories', 'group' => 'Billing & Expenses', 'description' => 'Delete expense categories'],
            ['name' => 'view_billing_invoices', 'group' => 'Billing & Expenses', 'description' => 'View billing invoices'],
            ['name' => 'create_billing_invoices', 'group' => 'Billing & Expenses', 'description' => 'Create billing invoices'],
            ['name' => 'edit_billing_invoices', 'group' => 'Billing & Expenses', 'description' => 'Edit billing invoices'],
            ['name' => 'delete_billing_invoices', 'group' => 'Billing & Expenses', 'description' => 'Delete billing invoices'],
            ['name' => 'approve_expenses', 'group' => 'Billing & Expenses', 'description' => 'Approve expenses'],
            ['name' => 'view_expense_reports', 'group' => 'Billing & Expenses', 'description' => 'View expense reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ],
                [
                    'group' => $permission['group'],
                    'description' => $permission['description'],
                ]
            );
        }

        $this->command->info('Permissions created successfully.');
    }
}
