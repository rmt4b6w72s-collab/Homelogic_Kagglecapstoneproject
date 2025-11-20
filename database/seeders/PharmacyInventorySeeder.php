<?php

namespace Database\Seeders;

use App\Models\PharmacyInventory;
use App\Models\PharmacyStockLot;
use App\Models\Branch;
use App\Models\Drug;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PharmacyInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::withoutGlobalScopes()->get();
        $drugs = Drug::all();
        $admin = User::withoutGlobalScopes()->where('role', 'super_admin')
            ->orWhere('role', 'administrator')
            ->first() ?? User::withoutGlobalScopes()->first();

        if ($branches->isEmpty() || $drugs->isEmpty()) {
            $this->command->warn('⚠️  No branches or drugs found. Please run BranchSeeder and DrugSeeder first.');
            return;
        }

        $this->command->info('📦 Creating pharmacy inventory...');

        foreach ($branches as $branch) {
            foreach ($drugs->random(min(5, $drugs->count())) as $drug) {
                // Determine quantities and costs based on drug
                $baseQuantity = rand(50, 500);
                $minimumLevel = max(10, (int)($baseQuantity * 0.2));
                $maximumLevel = (int)($baseQuantity * 2);
                $unitCost = $this->getUnitCost($drug->name);

                // Create inventory record
                $inventory = PharmacyInventory::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'drug_id' => $drug->id,
                    ],
                    [
                        'quantity' => $baseQuantity,
                        'minimum_stock_level' => $minimumLevel,
                        'maximum_stock_level' => $maximumLevel,
                        'unit_cost' => $unitCost,
                        'location' => $this->getRandomLocation(),
                        'last_received_date' => Carbon::now()->subDays(rand(1, 30)),
                        'last_dispensed_date' => Carbon::now()->subDays(rand(1, 7)),
                        'requires_refrigeration' => in_array(strtolower($drug->name), ['insulin', 'vaccine']),
                        'is_controlled_substance' => in_array(strtolower($drug->name), ['warfarin', 'oxycodone', 'morphine']),
                        'storage_notes' => $this->getStorageNotes($drug),
                    ]
                );

                // Create stock lots for this inventory
                $this->createStockLots($inventory, $admin);
            }
        }

        $this->command->info('✅ Pharmacy inventory created!');
    }

    private function getUnitCost(string $drugName): float
    {
        // Mock unit costs based on drug type
        $costs = [
            'Paracetamol' => 0.15,
            'Aspirin' => 0.10,
            'Ibuprofen' => 0.20,
            'Metformin' => 0.30,
            'Lisinopril' => 0.50,
            'Atorvastatin' => 0.75,
        ];

        foreach ($costs as $name => $cost) {
            if (stripos($drugName, $name) !== false) {
                return $cost + (rand(-10, 10) / 100); // Add some variance
            }
        }

        return round(rand(10, 100) / 100, 2); // Default cost between $0.10 and $1.00
    }

    private function getRandomLocation(): string
    {
        $locations = [
            'Room A, Shelf 1',
            'Room A, Shelf 2',
            'Room B, Shelf 1',
            'Room B, Shelf 2',
            'Refrigerator, Shelf 1',
            'Refrigerator, Shelf 2',
            'Cabinet 1, Top Shelf',
            'Cabinet 1, Middle Shelf',
            'Cabinet 2, Top Shelf',
            'Locked Cabinet, Controlled Substances',
        ];

        return $locations[array_rand($locations)];
    }

    private function getStorageNotes($drug): ?string
    {
        $notes = [
            'Store in original container',
            'Keep away from light',
            'Store below 25°C',
            'Do not freeze',
            'Keep tightly closed',
            'Store in refrigerator (2-8°C)',
            'Protected storage required',
        ];

        return $notes[array_rand($notes)];
    }

    private function createStockLots($inventory, $admin): void
    {
        $quantityToDistribute = $inventory->quantity;
        $numLots = min(3, (int)($quantityToDistribute / 100) + 1);

        for ($i = 0; $i < $numLots && $quantityToDistribute > 0; $i++) {
            $lotQuantity = min(rand(50, 200), $quantityToDistribute);
            $quantityToDistribute -= $lotQuantity;

            $expirationDate = Carbon::now()->addMonths(rand(6, 24));
            $receivedDate = Carbon::now()->subDays(rand(1, 90));

            PharmacyStockLot::create([
                'pharmacy_inventory_id' => $inventory->id,
                'branch_id' => $inventory->branch_id,
                'drug_id' => $inventory->drug_id,
                'lot_number' => 'LOT-' . strtoupper(substr(md5(rand()), 0, 8)),
                'manufacture_date' => $receivedDate->copy()->subMonths(rand(1, 12)),
                'expiration_date' => $expirationDate,
                'quantity' => $lotQuantity,
                'remaining_quantity' => $lotQuantity,
                'unit_cost' => $inventory->unit_cost,
                'received_date' => $receivedDate,
                'received_by' => $admin->id,
                'supplier_id' => \App\Models\PharmacySupplier::inRandomOrder()->first()?->id,
                'notes' => rand(0, 1) ? 'Received in good condition' : null,
            ]);
        }
    }
}

