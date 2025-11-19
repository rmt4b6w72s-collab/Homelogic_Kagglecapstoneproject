<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FireDrill;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;

class FireDrillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::where('is_active', true)->get();
        $admin = User::whereIn('role', ['admin', 'administrator', 'super_admin'])->first();
        
        if ($branches->isEmpty() || !$admin) {
            $this->command->warn('No active branches or admin user found. Please run BranchSeeder and AdminUserSeeder first.');
            return;
        }

        $this->command->info('🔥 Creating fire drills...');

        foreach ($branches as $branch) {
            // Create a fire drill scheduled for next week (1 day before alert)
            $nextWeek = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY);
            FireDrill::create([
                'branch_id' => $branch->id,
                'scheduled_date' => $nextWeek->toDateString(),
                'scheduled_time' => '10:00:00',
                'status' => 'scheduled',
                'notes' => 'Monthly fire drill - ensure all residents and staff are aware.',
                'created_by' => $admin->id,
            ]);

            // Create a fire drill scheduled for tomorrow (1 day before alert)
            $tomorrow = Carbon::now()->addDay();
            FireDrill::create([
                'branch_id' => $branch->id,
                'scheduled_date' => $tomorrow->toDateString(),
                'scheduled_time' => '14:00:00',
                'status' => 'scheduled',
                'notes' => 'Quarterly fire drill - full evacuation procedure.',
                'created_by' => $admin->id,
            ]);

            // Create a completed fire drill from last month
            $lastMonth = Carbon::now()->subMonth()->startOfWeek(Carbon::MONDAY);
            FireDrill::create([
                'branch_id' => $branch->id,
                'scheduled_date' => $lastMonth->toDateString(),
                'scheduled_time' => '10:00:00',
                'status' => 'completed',
                'notes' => 'Fire drill completed successfully. All residents evacuated within 5 minutes.',
                'completed_at' => $lastMonth->copy()->setTime(10, 15),
                'created_by' => $admin->id,
            ]);
        }

        $this->command->info('✅ Created ' . ($branches->count() * 3) . ' fire drills');
    }
}
