<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;
use App\Models\Facility;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            // Operational
            ['name' => 'Utilities', 'type' => 'operational', 'description' => 'Electricity, water, gas, internet, phone'],
            ['name' => 'Supplies', 'type' => 'operational', 'description' => 'Office supplies, cleaning supplies, general supplies'],
            ['name' => 'Maintenance', 'type' => 'operational', 'description' => 'Building maintenance, repairs, equipment maintenance'],
            ['name' => 'Insurance', 'type' => 'operational', 'description' => 'Property insurance, liability insurance'],
            ['name' => 'Rent/Lease', 'type' => 'operational', 'description' => 'Facility rent or lease payments'],
            
            // Staff
            ['name' => 'Payroll', 'type' => 'staff', 'description' => 'Employee salaries and wages'],
            ['name' => 'Training', 'type' => 'staff', 'description' => 'Staff training and development'],
            ['name' => 'Benefits', 'type' => 'staff', 'description' => 'Employee benefits, health insurance'],
            ['name' => 'Uniforms', 'type' => 'staff', 'description' => 'Staff uniforms and work attire'],
            
            // Resident Billing
            ['name' => 'Room & Board', 'type' => 'resident_billing', 'description' => 'Resident room and board charges'],
            ['name' => 'Care Services', 'type' => 'resident_billing', 'description' => 'Personal care services'],
            ['name' => 'Medications', 'type' => 'resident_billing', 'description' => 'Resident medication charges'],
            ['name' => 'Meals', 'type' => 'resident_billing', 'description' => 'Resident meal charges'],
            ['name' => 'Activities', 'type' => 'resident_billing', 'description' => 'Recreational activities and programs'],
            
            // Vendor
            ['name' => 'Pharmacy', 'type' => 'vendor', 'description' => 'Pharmacy supplier payments'],
            ['name' => 'Grocery', 'type' => 'vendor', 'description' => 'Grocery supplier payments'],
            ['name' => 'Equipment', 'type' => 'vendor', 'description' => 'Medical equipment and supplies'],
            ['name' => 'Laundry', 'type' => 'vendor', 'description' => 'Laundry service payments'],
            ['name' => 'Transportation', 'type' => 'vendor', 'description' => 'Transportation service payments'],
            
            // Other
            ['name' => 'Miscellaneous', 'type' => 'other', 'description' => 'Other expenses'],
        ];

        // Get all facilities or create for null (super admin)
        $facilities = Facility::all();
        
        if ($facilities->isEmpty()) {
            // If no facilities exist, create categories without facility_id (for super admin)
            foreach ($defaultCategories as $category) {
                ExpenseCategory::create($category);
            }
        } else {
            // Create categories for each facility
            foreach ($facilities as $facility) {
                foreach ($defaultCategories as $category) {
                    ExpenseCategory::create([
                        'facility_id' => $facility->id,
                        'name' => $category['name'],
                        'type' => $category['type'],
                        'description' => $category['description'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}

