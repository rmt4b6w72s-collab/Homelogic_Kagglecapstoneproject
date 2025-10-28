<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Essential production seeders (run first)
            UnifiedProductionSeeder::class,
            
            // Additional data seeders (for development/testing)
            CaregiverSeeder::class,
            ResidentSeeder::class,
            AssessmentSeeder::class,
            AssignmentSeeder::class,
            VitalSignSeeder::class,
            MedicationSeeder::class,
            MedicationAdministrationSeeder::class,
            AppointmentTypeSeeder::class,
            AppointmentSeeder::class,
            SleepPatternSeeder::class,
            SleepRecordSeeder::class,
            SleepHourlyDataSeeder::class,
            BehaviorCategorySeeder::class,
            BehaviorSeeder::class,
            IncidentSeeder::class,
            EmployeeDocumentSeeder::class,
            HealthcareProviderSeeder::class,
            LeaveRequestSeeder::class,
        ]);
    }
}
