<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MedicationDelivery;
use App\Models\Branch;
use App\Models\Resident;
use App\Models\Medication;
use App\Models\User;
use Carbon\Carbon;

class MedicationDeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::where('is_active', true)->get();
        $residents = Resident::where('is_active', true)->get();
        $medications = Medication::where('is_active', true)->get();
        $staff = User::where('is_active', true)->get();
        
        if ($branches->isEmpty() || $staff->isEmpty()) {
            $this->command->warn('No active branches or staff found. Please run BranchSeeder and UserSeeder first.');
            return;
        }

        $this->command->info('📦 Creating medication deliveries...');

        $pharmacies = [
            'CVS Pharmacy',
            'Walgreens',
            'Rite Aid',
            'Safeway Pharmacy',
            'Costco Pharmacy',
        ];

        $count = 0;

        foreach ($branches as $branch) {
            $branchResidents = $residents->where('branch_id', $branch->id);
            $branchMedications = $medications->filter(function($med) use ($branchResidents) {
                return $branchResidents->contains('id', $med->resident_id);
            });

            // Create individual medication deliveries
            if ($branchMedications->isNotEmpty()) {
                foreach ($branchMedications->take(5) as $medication) {
                    $resident = $branchResidents->where('id', $medication->resident_id)->first();
                    if (!$resident) continue;

                    MedicationDelivery::create([
                        'branch_id' => $branch->id,
                        'resident_id' => $resident->id,
                        'medication_id' => $medication->id,
                        'received_by' => $staff->random()->id,
                        'received_date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                        'received_time' => sprintf('%02d:00:00', rand(8, 17)),
                        'pharmacy_name' => $pharmacies[array_rand($pharmacies)],
                        'quantity_received' => rand(30, 90) . ' tablets',
                        'delivery_type' => 'individual',
                        'status' => ['received', 'verified', 'stored'][rand(0, 2)],
                        'notes' => 'Delivered as ordered.',
                    ]);
                    $count++;
                }
            }

            // Create batch deliveries
            for ($i = 0; $i < 3; $i++) {
                MedicationDelivery::create([
                    'branch_id' => $branch->id,
                    'resident_id' => null,
                    'medication_id' => null,
                    'received_by' => $staff->random()->id,
                    'received_date' => Carbon::now()->subDays(rand(1, 14))->toDateString(),
                    'received_time' => sprintf('%02d:00:00', rand(9, 16)),
                    'pharmacy_name' => $pharmacies[array_rand($pharmacies)],
                    'quantity_received' => 'Multiple medications for branch',
                    'delivery_type' => 'batch',
                    'status' => ['received', 'verified', 'stored'][rand(0, 2)],
                    'notes' => 'Weekly medication delivery for ' . $branch->name,
                ]);
                $count++;
            }
        }

        $this->command->info("✅ Created {$count} medication deliveries");
    }
}
