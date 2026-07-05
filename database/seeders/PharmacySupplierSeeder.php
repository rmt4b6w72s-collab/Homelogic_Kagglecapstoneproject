<?php

namespace Database\Seeders;

use App\Models\PharmacySupplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PharmacySupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'super_admin')
            ->orWhere('role', 'administrator')
            ->first();

        $suppliers = [
            [
                'name' => 'ABC Pharmaceuticals',
                'contact_person' => 'John Smith',
                'phone' => '(555) 123-4567',
                'email' => 'orders@abcpharma.com',
                'address' => '123 Main Street, Seattle, WA 98101',
                'is_active' => true,
                'notes' => 'Preferred supplier for general medications. License PH-12345. Fax (555) 123-4568. 5% discount, net 30.',
            ],
            [
                'name' => 'MedSupply Co.',
                'contact_person' => 'Sarah Johnson',
                'phone' => '(555) 234-5678',
                'email' => 'contact@medsupply.com',
                'address' => '456 Oak Avenue, Portland, OR 97201',
                'is_active' => true,
                'notes' => 'Specializes in controlled substances. License PH-23456. Fax (555) 234-5679. 7.5% discount, net 45.',
            ],
            [
                'name' => 'Quality Medical Supply',
                'contact_person' => 'Michael Brown',
                'phone' => '(555) 345-6789',
                'email' => 'info@qualitymed.com',
                'address' => '789 Pine Road, Vancouver, WA 98660',
                'is_active' => true,
                'notes' => 'Bulk orders available with discounts. License PH-34567. Fax (555) 345-6790. 3% discount, net 30.',
            ],
            [
                'name' => 'Express Pharmacy Services',
                'contact_person' => 'Emily Davis',
                'phone' => '(555) 456-7890',
                'email' => 'orders@expresspharm.com',
                'address' => '321 Elm Street, Spokane, WA 99201',
                'is_active' => true,
                'notes' => 'Fast delivery, emergency orders available. License PH-45678. Fax (555) 456-7891. 10% discount, net 15.',
            ],
            [
                'name' => 'HealthCare Distributors',
                'contact_person' => 'Robert Wilson',
                'phone' => '(555) 567-8901',
                'email' => 'sales@healthcare-dist.com',
                'address' => '654 Maple Drive, Tacoma, WA 98401',
                'is_active' => false,
                'notes' => 'Temporarily inactive - contact before ordering. License PH-56789. Fax (555) 567-8902. 5% discount, net 30.',
            ],
        ];

        foreach ($suppliers as $supplier) {
            PharmacySupplier::create([
                ...$supplier,
                'created_by' => $admin->id ?? 1,
            ]);
        }
    }
}
