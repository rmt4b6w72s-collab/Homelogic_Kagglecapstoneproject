<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if super admin already exists
        $superAdmin = User::where('email', 'superadmin@evergreen.com')->first();

        if (!$superAdmin) {
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@evergreen.com',
                'password' => Hash::make('password'), // Change this in production!
                'role' => 'super_admin',
                'is_active' => true,
                'facility_id' => null, // Super admins don't belong to a facility
            ]);

            $this->command->info('Super admin user created successfully!');
            $this->command->warn('Default password: password - Please change this immediately!');
        } else {
            $this->command->info('Super admin user already exists.');
        }
    }
}
