<?php

// Quick script to fix admin roles
// Run via: php artisan tinker
// Then: require 'fix-admin-roles.php';

use App\Models\User;
use App\Models\Role;

echo "🔧 Fixing admin user roles...\n\n";

// Get or create administrator role
$adminRole = Role::firstOrCreate(
    ['name' => 'administrator'],
    ['guard_name' => 'web']
);

// Get all permissions and assign to administrator role
$permissions = \App\Models\Permission::all();
if ($permissions->count() > 0) {
    $adminRole->permissions()->sync($permissions->pluck('id'));
    echo "✅ Assigned all {$permissions->count()} permissions to administrator role\n";
}

// Fix all users with role='admin' or role='administrator'
$adminUsers = User::whereIn('role', ['admin', 'administrator'])->get();

echo "\n📋 Found {$adminUsers->count()} admin user(s):\n";

foreach ($adminUsers as $user) {
    echo "\n  👤 {$user->email} (role field: {$user->role})\n";
    
    // Check if user has the administrator role model
    if ($user->hasRole('administrator')) {
        echo "    ✅ Already has 'administrator' role model\n";
    } else {
        // Assign the administrator role
        $user->assignRole('administrator');
        echo "    ✅ Assigned 'administrator' role model\n";
    }
    
    // Show current roles
    $roles = $user->roles->pluck('name')->toArray();
    echo "    📋 Current roles: " . (empty($roles) ? 'None' : implode(', ', $roles)) . "\n";
    
    // Show permissions via roles
    $permissionCount = $user->roles()->withCount('permissions')->get()->sum('permissions_count');
    echo "    🔑 Permissions via roles: {$permissionCount}\n";
}

echo "\n✅ Done! All admin users should now have the administrator role with all permissions.\n";
echo "💡 Remember to log out and log back in for changes to take effect!\n";

