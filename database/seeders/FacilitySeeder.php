<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Facility;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating facilities...');

        $facilities = [
            [
                'name' => 'Evergreen Oasis Care Home',
                'location' => 'Edmonds, WA',
                'description' => 'Our flagship facility providing compassionate care in a warm, home-like environment. Specializing in memory care and assisted living services.',
                'address' => '123 Main Street, Edmonds, WA 98020',
                'phone' => '(206) 555-0123',
                'email' => 'info@evergreenoasis.com',
                'website' => 'https://evergreenoasis.com',
                'license_number' => 'AFH-2024-001',
                'license_expiry' => now()->addYear(),
                'brochure_url' => '/brochures/evergreen-brochure.pdf',
                'brochure_color' => 'green',
                'is_active' => true,
            ],
            [
                'name' => 'Bothell Serenity Corp',
                'location' => 'Bothell, WA',
                'description' => 'Our modern facility offering comprehensive care services with state-of-the-art amenities and personalized care plans.',
                'address' => '456 Bothell Way, Bothell, WA 98011',
                'phone' => '(206) 555-0124',
                'email' => 'info@bothellserenity.com',
                'website' => 'https://bothellserenity.com',
                'license_number' => 'AFH-2024-002',
                'license_expiry' => now()->addYear(),
                'brochure_url' => '/brochures/bothell-brochure.pdf',
                'brochure_color' => 'blue',
                'is_active' => true,
            ],
        ];

        foreach ($facilities as $facilityData) {
            Facility::firstOrCreate(
                ['name' => $facilityData['name']],
                $facilityData
            );
        }

        $this->command->info('✅ Created ' . Facility::count() . ' facilities');
    }
}