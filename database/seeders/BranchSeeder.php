<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Creating branches...');
        
        // Get or create facilities (use FacilitySeeder data for consistency)
        $evergreenFacility = Facility::firstOrCreate(
            ['name' => 'Evergreen Oasis Care Home'],
            [
                'location' => 'Edmonds, WA',
                'description' => 'Our flagship facility providing compassionate care in a warm, home-like environment.',
                'address' => '123 Main Street, Edmonds, WA 98020',
                'phone' => '(206) 555-0123',
                'email' => 'info@evergreenoasis.com',
                'website' => 'https://evergreenoasis.com',
                'license_number' => 'AFH-2024-001',
                'license_expiry' => now()->addYear(),
                'brochure_url' => '/brochures/evergreen-brochure.pdf',
                'brochure_color' => 'green',
                'is_active' => true,
            ]
        );

        $bothellFacility = Facility::firstOrCreate(
            ['name' => 'Bothell Serenity Corp'],
            [
                'location' => 'Bothell, WA',
                'description' => 'A modern facility offering advanced care services with a focus on rehabilitation.',
                'address' => '456 Bothell Way, Bothell, WA 98011',
                'phone' => '(206) 555-0124',
                'email' => 'info@bothellserenity.com',
                'website' => 'https://bothellserenity.com',
                'license_number' => 'AFH-2024-002',
                'license_expiry' => now()->addYear(),
                'brochure_url' => '/brochures/bothell-brochure.pdf',
                'brochure_color' => 'blue',
                'is_active' => true,
            ]
        );

        // Create branches
        $branches = [
            [
                'name' => 'Main Branch',
                'address' => '123 Main Street, Edmonds, WA 98020',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(425) 555-0101',
                'email' => 'main@evergreenoasis.com',
                'is_active' => true,
            ],
            [
                'name' => 'Canyon Park Serenity AFH',
                'address' => '456 Canyon Park Blvd, Bothell, WA 98011',
                'facility_id' => $bothellFacility->id,
                'phone' => '(425) 555-0102',
                'email' => 'canyonpark@bothellserenity.com',
                'is_active' => true,
            ],
            [
                'name' => 'Serenity Everett AFH',
                'address' => '789 Everett Ave, Everett, WA 98201',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(425) 555-0103',
                'email' => 'everett@evergreenoasis.com',
                'is_active' => true,
            ],
            [
                'name' => 'Bothell Serenity AFH',
                'address' => '321 Bothell Way, Bothell, WA 98012',
                'facility_id' => $bothellFacility->id,
                'phone' => '(425) 555-0104',
                'email' => 'bothell@bothellserenity.com',
                'is_active' => true,
            ],
            [
                'name' => 'Lynnwood Serenity AFH',
                'address' => '654 Lynnwood Dr, Lynnwood, WA 98036',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(425) 555-0105',
                'email' => 'lynnwood@evergreenoasis.com',
                'is_active' => true,
            ],
            [
                'name' => '1st Edmond – Best Care Harbour Pointe – Mukiteo',
                'address' => '987 Harbour Pointe Blvd, Mukilteo, WA 98275',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(425) 555-0106',
                'email' => 'harbourpointe@evergreenoasis.com',
                'is_active' => true,
            ],
            [
                'name' => 'Filbert Rd AFH',
                'address' => '147 Filbert Rd, Edmonds, WA 98026',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(425) 555-0107',
                'email' => 'filbert@evergreenoasis.com',
                'is_active' => true,
            ],
            [
                'name' => 'Maple Grove Care Center',
                'address' => '258 Maple Grove Ave, Seattle, WA 98101',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(206) 555-0108',
                'email' => 'maplegrove@evergreenoasis.com',
                'is_active' => true,
            ],
            [
                'name' => 'Riverside Wellness Home',
                'address' => '369 Riverside Dr, Renton, WA 98055',
                'facility_id' => $bothellFacility->id,
                'phone' => '(425) 555-0109',
                'email' => 'riverside@bothellserenity.com',
                'is_active' => true,
            ],
            [
                'name' => 'Pine Valley Assisted Living',
                'address' => '741 Pine Valley Blvd, Bellevue, WA 98004',
                'facility_id' => $evergreenFacility->id,
                'phone' => '(425) 555-0110',
                'email' => 'pinevalley@evergreenoasis.com',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(
                ['name' => $branchData['name']],
                $branchData
            );
        }
        
        $this->command->info('✅ Created ' . Branch::count() . ' branches');
    }
}