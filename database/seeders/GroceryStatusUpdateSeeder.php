<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GroceryStatusUpdate;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;

class GroceryStatusUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::where('is_active', true)->get();
        $staff = User::where('is_active', true)->get();
        
        if ($branches->isEmpty() || $staff->isEmpty()) {
            $this->command->warn('No active branches or staff found. Please run BranchSeeder and UserSeeder first.');
            return;
        }

        $this->command->info('🛒 Creating grocery status updates...');

        $itemsNeeded = [
            'Milk, eggs, bread, fresh vegetables, fruits',
            'Paper towels, toilet paper, cleaning supplies',
            'Fresh produce, dairy products, meat',
            'Canned goods, pasta, rice, cooking oil',
            'Snacks, beverages, condiments',
        ];

        $itemsReceived = [
            'All items received and stored',
            'Partial delivery - waiting for rest',
            'All items received and verified',
            'Items received, quality check completed',
            'Full delivery received and organized',
        ];

        $count = 0;

        foreach ($branches as $branch) {
            // Get current week's Monday
            $currentMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
            
            // Create updates for current week and past 3 weeks
            for ($weekOffset = 0; $weekOffset < 4; $weekOffset++) {
                $weekStart = $currentMonday->copy()->subWeeks($weekOffset);
                
                // Create 1-2 updates per week (to show multiple updates are allowed)
                $numUpdates = rand(1, 2);
                
                for ($i = 0; $i < $numUpdates; $i++) {
                    $status = ['pending', 'in_progress', 'completed', 'needs_attention'][rand(0, 3)];
                    
                    GroceryStatusUpdate::create([
                        'branch_id' => $branch->id,
                        'updated_by' => $staff->random()->id,
                        'week_start_date' => $weekStart->toDateString(),
                        'status' => $status,
                        'items_needed' => $itemsNeeded[array_rand($itemsNeeded)],
                        'items_received' => $status === 'completed' ? $itemsReceived[array_rand($itemsReceived)] : null,
                        'notes' => $weekOffset === 0 ? 'Current week grocery status update' : "Week of {$weekStart->format('M j')} grocery update",
                        'completed_at' => $status === 'completed' ? Carbon::now()->subDays(rand(0, 7)) : null,
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info("✅ Created {$count} grocery status updates");
    }
}
