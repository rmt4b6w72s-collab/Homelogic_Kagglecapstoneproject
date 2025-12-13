<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Schema;

class FixAdminUsersFacilityId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:admin-users-facility';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix admin users by setting facility_id from assigned_branch_id if missing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Fixing admin users facility_id...');
        $this->line('');

        // Check if facility_id column exists
        if (!Schema::hasColumn('users', 'facility_id')) {
            $this->error('❌ facility_id column does not exist on users table. Please run migrations first.');
            return 1;
        }

        // Get all admin/administrator users without facility_id but with assigned_branch_id
        $adminUsers = User::whereIn('role', ['admin', 'administrator'])
            ->whereNull('facility_id')
            ->whereNotNull('assigned_branch_id')
            ->get();

        if ($adminUsers->isEmpty()) {
            $this->info('✅ No admin users need fixing - all have facility_id or no assigned_branch_id');
            return 0;
        }

        $this->info("📋 Found {$adminUsers->count()} admin user(s) that need fixing:");
        $this->line('');

        $fixed = 0;
        $failed = 0;

        foreach ($adminUsers as $user) {
            $branch = Branch::find($user->assigned_branch_id);
            
            if ($branch && $branch->facility_id) {
                $user->facility_id = $branch->facility_id;
                $user->save();
                
                $this->line("✅ Fixed user: {$user->email} (ID: {$user->id})");
                $this->line("   - Branch: {$branch->name} (ID: {$branch->id})");
                $this->line("   - Facility ID set to: {$branch->facility_id}");
                $this->line('');
                $fixed++;
            } else {
                $this->warn("⚠️  Cannot fix user: {$user->email} (ID: {$user->id})");
                if (!$branch) {
                    $this->line("   - Branch ID {$user->assigned_branch_id} not found");
                } else {
                    $this->line("   - Branch '{$branch->name}' has no facility_id");
                }
                $this->line('');
                $failed++;
            }
        }

        $this->line('');
        $this->info("📊 Summary:");
        $this->info("   ✅ Fixed: {$fixed}");
        if ($failed > 0) {
            $this->warn("   ⚠️  Failed: {$failed}");
        }

        return 0;
    }
}
