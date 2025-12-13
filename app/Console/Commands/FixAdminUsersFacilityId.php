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

        // Get all admin/administrator users without facility_id
        $adminUsers = User::whereIn('role', ['admin', 'administrator'])
            ->whereNull('facility_id')
            ->get();

        if ($adminUsers->isEmpty()) {
            $this->info('✅ No admin users need fixing - all have facility_id');
            return 0;
        }

        $this->info("📋 Found {$adminUsers->count()} admin user(s) without facility_id:");
        $this->line('');

        $this->info("📋 Found {$adminUsers->count()} admin user(s) that need fixing:");
        $this->line('');

        $fixed = 0;
        $failed = 0;

        foreach ($adminUsers as $user) {
            $facilityId = null;
            $method = '';
            
            // Method 1: Try assigned_branch_id
            if ($user->assigned_branch_id) {
                $branch = Branch::find($user->assigned_branch_id);
                if ($branch && $branch->facility_id) {
                    $facilityId = $branch->facility_id;
                    $method = "assigned_branch_id → branch → facility_id";
                }
            }
            
            // Method 2: Try to find from residents (only if created_by column exists)
            if (!$facilityId && Schema::hasColumn('residents', 'created_by')) {
                $resident = \App\Models\Resident::where('created_by', $user->id)
                    ->whereNotNull('facility_id')
                    ->first();
                
                if ($resident && $resident->facility_id) {
                    $facilityId = $resident->facility_id;
                    $method = "residents created by user → facility_id";
                }
            }
            
            // Method 3: Try to find from residents' branches (only if created_by column exists)
            if (!$facilityId && Schema::hasColumn('residents', 'created_by')) {
                $resident = \App\Models\Resident::where('created_by', $user->id)
                    ->whereNotNull('branch_id')
                    ->with('branch')
                    ->first();
                
                if ($resident && $resident->branch && $resident->branch->facility_id) {
                    $facilityId = $resident->branch->facility_id;
                    $method = "residents created by user → branch → facility_id";
                }
            }
            
            // Method 4: Try to find from any facility (if only one exists)
            if (!$facilityId) {
                $facilityCount = \App\Models\Facility::count();
                if ($facilityCount === 1) {
                    $facility = \App\Models\Facility::first();
                    if ($facility) {
                        $facilityId = $facility->id;
                        $method = "only facility in system";
                    }
                }
            }
            
            if ($facilityId) {
                $user->facility_id = $facilityId;
                $user->save();
                
                $this->line("✅ Fixed user: {$user->email} (ID: {$user->id})");
                $this->line("   - Method: {$method}");
                $this->line("   - Facility ID set to: {$facilityId}");
                $this->line('');
                $fixed++;
            } else {
                $this->warn("⚠️  Cannot fix user: {$user->email} (ID: {$user->id})");
                $this->line("   - No assigned_branch_id");
                $this->line("   - No residents created by this user");
                $this->line("   - Multiple facilities exist (cannot auto-assign)");
                $this->line("   - ACTION REQUIRED: Manually assign this user to a branch or facility");
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
