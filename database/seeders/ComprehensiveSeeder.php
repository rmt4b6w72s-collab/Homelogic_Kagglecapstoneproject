<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Facility;
use App\Models\Branch;
use App\Models\Drug;
use App\Models\Resident;
use App\Models\Medication;
use App\Models\VitalSign;
use App\Models\Appointment;
use App\Models\Assignment;
use App\Models\Assessment;
use App\Models\LeaveRequest;
use App\Models\SleepPattern;
use App\Models\SleepRecord;
use App\Models\VitalRange;
use App\Models\MedicationAdministration;
use Carbon\Carbon;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Run comprehensive database seeds for all tables.
     * This creates realistic data for testing and development.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive database seeding...');

        // 1. Ensure basic setup exists
        $this->ensureBasicSetup();
        
        // 2. Create additional users (caregivers, nurses)
        $this->createStaffUsers();
        
        // 3. Create drugs/pharmaceuticals
        $this->createDrugs();
        
        // 4. Create residents
        $this->createResidents();
        
        // 5. Create medications for residents
        $this->createMedications();
        
        // 6. Create vital signs records
        $this->createVitalSigns();
        
        // 7. Create appointments
        $this->createAppointments();
        
        // 8. Create assignments
        $this->createAssignments();
        
        // 9. Create assessments
        $this->createAssessments();
        
        // 10. Create leave requests
        $this->createLeaveRequests();
        
        // 11. Create sleep patterns and records
        $this->createSleepData();
        
        // 12. Create medication administrations
        $this->createMedicationAdministrations();

        $this->command->info('✅ Comprehensive database seeding completed!');
        $this->showSummary();
    }

    private function ensureBasicSetup(): void
    {
        // Run the unified production seeder first to ensure basic setup
        $this->call(UnifiedProductionSeeder::class);
        $this->command->info("✅ Basic setup ensured");
    }

    private function createStaffUsers(): void
    {
        $caregiverRole = Role::where('name', 'caregiver')->first();
        $nurseRole = Role::where('name', 'nurse')->first();
        $branch = Branch::first();

        $staffUsers = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@edmondserenity.com',
                'password' => Hash::make('password'),
                'role' => 'caregiver',
                'assigned_branch_id' => $branch->id,
                'is_active' => true,
                'hire_date' => now()->subMonths(6),
                'phone_number' => '(206) 555-0101',
                'notes' => 'Experienced caregiver with 5 years in senior care',
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael.chen@edmondserenity.com',
                'password' => Hash::make('password'),
                'role' => 'caregiver',
                'assigned_branch_id' => $branch->id,
                'is_active' => true,
                'hire_date' => now()->subMonths(3),
                'phone_number' => '(206) 555-0102',
                'notes' => 'New caregiver, excellent with dementia patients',
            ],
            [
                'name' => 'Emily Rodriguez',
                'email' => 'emily.rodriguez@edmondserenity.com',
                'password' => Hash::make('password'),
                'role' => 'nurse',
                'assigned_branch_id' => $branch->id,
                'is_active' => true,
                'hire_date' => now()->subMonths(12),
                'phone_number' => '(206) 555-0103',
                'notes' => 'Registered Nurse with geriatric specialization',
            ],
            [
                'name' => 'David Thompson',
                'email' => 'david.thompson@edmondserenity.com',
                'password' => Hash::make('password'),
                'role' => 'nurse',
                'assigned_branch_id' => $branch->id,
                'is_active' => true,
                'hire_date' => now()->subMonths(8),
                'phone_number' => '(206) 555-0104',
                'notes' => 'Licensed Practical Nurse, medication specialist',
            ],
        ];

        foreach ($staffUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Assign appropriate role
            if ($userData['role'] === 'caregiver' && $caregiverRole) {
                $user->assignRole('caregiver');
            } elseif ($userData['role'] === 'nurse' && $nurseRole) {
                $user->assignRole('nurse');
            }
        }

        $this->command->info("✅ Created " . count($staffUsers) . " staff users");
    }

    private function createDrugs(): void
    {
        $drugs = [
            [
                'name' => 'Lisinopril',
                'generic_name' => 'Lisinopril',
                'dosage_form' => 'Tablet',
                'strength' => '10mg',
                'indications' => 'High blood pressure, heart failure',
                'side_effects' => 'Dry cough, dizziness, fatigue',
                'contraindications' => 'Pregnancy, angioedema history',
                'interactions' => 'Potassium supplements, NSAIDs',
                'dosage_instructions' => 'Take once daily with or without food',
                'is_active' => true,
            ],
            [
                'name' => 'Metformin',
                'generic_name' => 'Metformin',
                'dosage_form' => 'Tablet',
                'strength' => '500mg',
                'indications' => 'Type 2 diabetes',
                'side_effects' => 'Nausea, diarrhea, metallic taste',
                'contraindications' => 'Kidney disease, liver disease',
                'interactions' => 'Alcohol, contrast dye',
                'dosage_instructions' => 'Take twice daily with meals',
                'is_active' => true,
            ],
            [
                'name' => 'Atorvastatin',
                'generic_name' => 'Atorvastatin',
                'dosage_form' => 'Tablet',
                'strength' => '20mg',
                'indications' => 'High cholesterol, cardiovascular disease',
                'side_effects' => 'Muscle pain, liver problems',
                'contraindications' => 'Active liver disease',
                'interactions' => 'Grapefruit juice, warfarin',
                'dosage_instructions' => 'Take once daily in the evening',
                'is_active' => true,
            ],
            [
                'name' => 'Omeprazole',
                'generic_name' => 'Omeprazole',
                'dosage_form' => 'Capsule',
                'strength' => '20mg',
                'indications' => 'Acid reflux, ulcers',
                'side_effects' => 'Headache, nausea, diarrhea',
                'contraindications' => 'Hypersensitivity to omeprazole',
                'interactions' => 'Warfarin, clopidogrel',
                'dosage_instructions' => 'Take once daily before breakfast',
                'is_active' => true,
            ],
            [
                'name' => 'Donepezil',
                'generic_name' => 'Donepezil',
                'dosage_form' => 'Tablet',
                'strength' => '5mg',
                'indications' => 'Alzheimer\'s disease, dementia',
                'side_effects' => 'Nausea, vomiting, diarrhea',
                'contraindications' => 'Severe liver disease',
                'interactions' => 'Anticholinergics, NSAIDs',
                'dosage_instructions' => 'Take once daily at bedtime',
                'is_active' => true,
            ],
            [
                'name' => 'Warfarin',
                'generic_name' => 'Warfarin',
                'dosage_form' => 'Tablet',
                'strength' => '5mg',
                'indications' => 'Blood clot prevention',
                'side_effects' => 'Bleeding, bruising',
                'contraindications' => 'Active bleeding, pregnancy',
                'interactions' => 'Aspirin, NSAIDs, alcohol',
                'dosage_instructions' => 'Take once daily at the same time',
                'is_active' => true,
            ],
        ];

        foreach ($drugs as $drugData) {
            Drug::firstOrCreate(
                ['name' => $drugData['name'], 'strength' => $drugData['strength']],
                $drugData
            );
        }

        $this->command->info("✅ Created " . count($drugs) . " drugs");
    }

    private function createResidents(): void
    {
        $branch = Branch::first();

        $residents = [
            [
                'name' => 'Margaret Williams',
                'first_name' => 'Margaret',
                'last_name' => 'Williams',
                'date_of_birth' => '1935-03-15',
                'gender' => 'Female',
                'branch_id' => $branch->id,
                'room_number' => '101',
                'admission_date' => now()->subMonths(18),
                'emergency_contact_name' => 'John Williams',
                'emergency_contact_phone' => '(206) 555-1001',
                'medical_conditions' => 'Hypertension, Diabetes Type 2, Arthritis',
                'allergies' => 'Penicillin, Shellfish',
                'medications' => 'Lisinopril 10mg daily, Metformin 500mg twice daily',
                'dietary_restrictions' => 'Low sodium, diabetic diet',
                'mobility_notes' => 'Uses walker for ambulation',
                'behavioral_notes' => 'Mild dementia, occasionally confused',
                'care_plan' => 'Monitor blood pressure and blood sugar daily',
                'diagnosis' => 'Hypertension, Type 2 Diabetes, Osteoarthritis',
                'physician_name' => 'Dr. Smith',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'Robert Anderson',
                'first_name' => 'Robert',
                'last_name' => 'Anderson',
                'date_of_birth' => '1940-07-22',
                'gender' => 'Male',
                'branch_id' => $branch->id,
                'room_number' => '102',
                'admission_date' => now()->subMonths(12),
                'emergency_contact_name' => 'Mary Anderson',
                'emergency_contact_phone' => '(206) 555-1002',
                'medical_conditions' => 'Heart Disease, High Cholesterol, COPD',
                'allergies' => 'Latex',
                'medications' => 'Atorvastatin 20mg daily, Albuterol inhaler as needed',
                'dietary_restrictions' => 'Low cholesterol, low sodium',
                'mobility_notes' => 'Wheelchair bound, needs assistance with transfers',
                'behavioral_notes' => 'Alert and oriented, cooperative',
                'care_plan' => 'Monitor respiratory status, assist with mobility',
                'diagnosis' => 'Coronary Artery Disease, COPD, Hyperlipidemia',
                'physician_name' => 'Dr. Johnson',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'Eleanor Davis',
                'first_name' => 'Eleanor',
                'last_name' => 'Davis',
                'date_of_birth' => '1938-11-08',
                'gender' => 'Female',
                'branch_id' => $branch->id,
                'room_number' => '103',
                'admission_date' => now()->subMonths(8),
                'emergency_contact_name' => 'James Davis',
                'emergency_contact_phone' => '(206) 555-1003',
                'medical_conditions' => 'Alzheimer\'s Disease, Osteoporosis, Depression',
                'allergies' => 'None known',
                'medications' => 'Donepezil 5mg daily, Calcium supplement',
                'dietary_restrictions' => 'Regular diet',
                'mobility_notes' => 'Independent with assistance, uses walker',
                'behavioral_notes' => 'Moderate dementia, sometimes agitated',
                'care_plan' => 'Cognitive stimulation, fall prevention',
                'diagnosis' => 'Alzheimer\'s Disease, Osteoporosis, Major Depression',
                'physician_name' => 'Dr. Wilson',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'Frank Miller',
                'first_name' => 'Frank',
                'last_name' => 'Miller',
                'date_of_birth' => '1942-05-30',
                'gender' => 'Male',
                'branch_id' => $branch->id,
                'room_number' => '104',
                'admission_date' => now()->subMonths(6),
                'emergency_contact_name' => 'Susan Miller',
                'emergency_contact_phone' => '(206) 555-1004',
                'medical_conditions' => 'Atrial Fibrillation, Hypertension, GERD',
                'allergies' => 'Aspirin',
                'medications' => 'Warfarin 5mg daily, Omeprazole 20mg daily',
                'dietary_restrictions' => 'Low sodium, avoid vitamin K rich foods',
                'mobility_notes' => 'Independent ambulation',
                'behavioral_notes' => 'Alert and oriented, independent',
                'care_plan' => 'Monitor INR levels, blood pressure checks',
                'diagnosis' => 'Atrial Fibrillation, Hypertension, GERD',
                'physician_name' => 'Dr. Brown',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'Dorothy Brown',
                'first_name' => 'Dorothy',
                'last_name' => 'Brown',
                'date_of_birth' => '1933-12-12',
                'gender' => 'Female',
                'branch_id' => $branch->id,
                'room_number' => '105',
                'admission_date' => now()->subMonths(24),
                'emergency_contact_name' => 'Michael Brown',
                'emergency_contact_phone' => '(206) 555-1005',
                'medical_conditions' => 'Parkinson\'s Disease, Depression, Osteoarthritis',
                'allergies' => 'None known',
                'medications' => 'Carbidopa-Levodopa, Sertraline 50mg daily',
                'dietary_restrictions' => 'Regular diet',
                'mobility_notes' => 'Uses cane, needs assistance with ADLs',
                'behavioral_notes' => 'Alert and oriented, mild depression',
                'care_plan' => 'Medication management, physical therapy',
                'diagnosis' => 'Parkinson\'s Disease, Major Depression, Osteoarthritis',
                'physician_name' => 'Dr. Taylor',
                'status' => 'active',
                'is_active' => true,
            ],
        ];

        foreach ($residents as $residentData) {
            Resident::firstOrCreate(
                [
                    'first_name' => $residentData['first_name'],
                    'last_name' => $residentData['last_name'],
                    'room_number' => $residentData['room_number']
                ],
                $residentData
            );
        }

        $this->command->info("✅ Created " . count($residents) . " residents");
    }

    private function createMedications(): void
    {
        $residents = Resident::all();
        $drugs = Drug::all();
        $adminUserId = $this->getAdminUserId();

        if ($residents->isEmpty() || $drugs->isEmpty() || !$adminUserId) {
            $this->command->warn("⚠️ No residents or drugs found, skipping medication creation");
            return;
        }

        $medications = [];
        
        foreach ($residents as $resident) {
            // Create 2-4 medications per resident
            $medCount = rand(2, 4);
            $selectedDrugs = $drugs->random($medCount);
            
            foreach ($selectedDrugs as $drug) {
                $medications[] = [
                    'resident_id' => $resident->id,
                    'branch_id' => $resident->branch_id,
                    'drug_id' => $drug->id,
                    'name' => $drug->name,
                    'instructions' => $drug->dosage_instructions,
                    'quantity' => $drug->strength,
                    'diagnosis' => $resident->diagnosis,
                    'prescription_date' => now()->subDays(rand(30, 365)),
                    'start_date' => now()->subDays(rand(30, 365)),
                    'end_date' => null,
                    'notes' => 'Take with food',
                    'time_1' => '08:00:00',
                    'time_2' => '12:00:00',
                    'time_3' => '18:00:00',
                    'time_4' => null,
                    'created_by' => $adminUserId,
                    'is_active' => true,
                ];
            }
        }

        foreach ($medications as $medicationData) {
            Medication::firstOrCreate(
                [
                    'resident_id' => $medicationData['resident_id'],
                    'drug_id' => $medicationData['drug_id'],
                    'name' => $medicationData['name']
                ],
                $medicationData
            );
        }

        $this->command->info("✅ Created " . count($medications) . " medications");
    }

    private function createVitalSigns(): void
    {
        $residents = Resident::all();
        $users = $this->getOperationalUsers();

        if ($residents->isEmpty() || $users->isEmpty()) {
            $this->command->warn("⚠️ No residents or users found, skipping vital signs creation");
            return;
        }

        $vitalSigns = [];
        
        foreach ($residents as $resident) {
            // Create 30 days of vital signs (3 times per day)
            for ($day = 0; $day < 30; $day++) {
                for ($time = 0; $time < 3; $time++) {
                    $recordedAt = now()->subDays($day)->subHours($time * 8);
                    $recordedBy = $users->random();
                    
                    $vitalSigns[] = [
                        'resident_id' => $resident->id,
                        'branch_id' => $resident->branch_id,
                        'taken_by' => $recordedBy->id,
                        'measurement_date' => $recordedAt->toDateString(),
                        'systolic' => rand(100, 160),
                        'diastolic' => rand(60, 90),
                        'temperature' => round(rand(970, 1000) / 10, 1),
                        'pulse' => rand(60, 100),
                        'respiratory_rate' => rand(12, 20),
                        'oxygen_saturation' => rand(92, 100),
                        'weight' => round(rand(120, 200) / 10, 1),
                        'height' => round(rand(60, 72) / 10, 1),
                        'pain_level' => rand(0, 5),
                        'pain_description' => $this->getRandomPainDescription(),
                        'status' => 'completed',
                        'notes' => $this->getRandomVitalNotes(),
                    ];
                }
            }
        }

        foreach ($vitalSigns as $vitalData) {
            VitalSign::create($vitalData);
        }

        $this->command->info("✅ Created " . count($vitalSigns) . " vital signs records");
    }

    private function createAppointments(): void
    {
        $residents = Resident::all();
        $users = $this->getOperationalUsers();

        if ($residents->isEmpty() || $users->isEmpty()) {
            $this->command->warn("⚠️ No residents or users found, skipping appointments creation");
            return;
        }

        $appointmentTypes = [
            'Doctor Visit', 'Physical Therapy', 'Dental Checkup', 'Eye Exam',
            'Lab Work', 'X-Ray', 'Specialist Consultation', 'Medication Review'
        ];

        $appointments = [];
        
        foreach ($residents as $resident) {
            // Create 2-5 appointments per resident
            $apptCount = rand(2, 5);
            
            for ($i = 0; $i < $apptCount; $i++) {
                $appointmentDate = now()->addDays(rand(1, 90));
                $appointmentType = $appointmentTypes[array_rand($appointmentTypes)];
                $appointments[] = [
                    'resident_id' => $resident->id,
                    'branch_id' => $resident->branch_id,
                    'title' => $appointmentType,
                    'description' => 'Regular ' . strtolower($appointmentType) . ' appointment',
                    'appointment_date' => $appointmentDate,
                    'location' => $this->getRandomLocation(),
                    'provider_name' => 'Dr. ' . $this->getRandomLastName(),
                    'provider_phone' => '(206) 555-' . rand(1000, 9999),
                    'notes' => 'Regular checkup',
                    'status' => 'scheduled',
                    'created_by' => $users->random()->id,
                ];
            }
        }

        foreach ($appointments as $appointmentData) {
            Appointment::create($appointmentData);
        }

        $this->command->info("✅ Created " . count($appointments) . " appointments");
    }

    private function createAssignments(): void
    {
        $residents = Resident::all();
        $caregivers = User::where('role', 'caregiver')->get();
        $adminUserId = $this->getAdminUserId();

        if ($residents->isEmpty() || $caregivers->isEmpty() || !$adminUserId) {
            $this->command->warn("⚠️ No residents or caregivers found, skipping assignments creation");
            return;
        }

        $assignments = [];
        
        foreach ($residents as $resident) {
            // Assign 1-2 caregivers per resident
            $caregiverCount = rand(1, 2);
            $assignedCaregivers = $caregivers->random($caregiverCount);
            
            foreach ($assignedCaregivers as $caregiver) {
                $assignments[] = [
                    'resident_id' => $resident->id,
                    'caregiver_id' => $caregiver->id,
                    'branch_id' => $resident->branch_id,
                    'assigned_at' => now()->subDays(rand(1, 30)),
                    'assigned_by' => $adminUserId,
                    'notes' => 'Regular care assignment - ' . $this->getRandomShift(),
                    'is_active' => true,
                ];
            }
        }

        foreach ($assignments as $assignmentData) {
            Assignment::firstOrCreate(
                [
                    'resident_id' => $assignmentData['resident_id'],
                    'caregiver_id' => $assignmentData['caregiver_id'],
                    'assigned_at' => $assignmentData['assigned_at']
                ],
                $assignmentData
            );
        }

        $this->command->info("✅ Created " . count($assignments) . " assignments");
    }

    private function createAssessments(): void
    {
        $residents = Resident::all();
        $users = $this->getOperationalUsers();

        if ($residents->isEmpty() || $users->isEmpty()) {
            $this->command->warn("⚠️ No residents or users found, skipping assessments creation");
            return;
        }

        $assessmentTypes = [
            'Initial Assessment', 'Monthly Review', 'Fall Risk Assessment',
            'Cognitive Assessment', 'Medication Review', 'Care Plan Review'
        ];

        $assessments = [];
        
        foreach ($residents as $resident) {
            // Create 1-3 assessments per resident
            $assessmentCount = rand(1, 3);
            
            for ($i = 0; $i < $assessmentCount; $i++) {
                $assessmentDate = now()->subDays(rand(1, 60));
                $assessments[] = [
                    'resident_id' => $resident->id,
                    'branch_id' => $resident->branch_id,
                    'assessor_id' => $users->random()->id,
                    'assessment_type' => $assessmentTypes[array_rand($assessmentTypes)],
                    'assessment_date' => $assessmentDate->toDateString(),
                    'status' => 'completed',
                    'notes' => $this->getRandomFindings(),
                    'scores' => json_encode(['overall_score' => rand(70, 95), 'mobility' => rand(60, 90), 'cognition' => rand(50, 85)]),
                    'recommendations' => $this->getRandomRecommendations(),
                    'completed_at' => $assessmentDate,
                    'reviewed_at' => $assessmentDate->addDays(1),
                    'approved_at' => $assessmentDate->addDays(2),
                ];
            }
        }

        foreach ($assessments as $assessmentData) {
            Assessment::create($assessmentData);
        }

        $this->command->info("✅ Created " . count($assessments) . " assessments");
    }

    private function createLeaveRequests(): void
    {
        $users = $this->getOperationalUsers();
        $adminUserId = $this->getAdminUserId();

        if ($users->isEmpty() || !$adminUserId) {
            $this->command->warn("⚠️ No users found, skipping leave requests creation");
            return;
        }

        $leaveTypes = ['Sick Leave', 'Vacation', 'Personal Leave', 'Emergency Leave'];
        $statuses = ['pending', 'approved', 'denied'];

        $leaveRequests = [];
        
        foreach ($users as $user) {
            $branchId = $this->resolveBranchIdForUser($user);
            if (! $branchId) {
                continue;
            }

            // Create 1-2 leave requests per user
            $leaveCount = rand(1, 2);
            
            for ($i = 0; $i < $leaveCount; $i++) {
                $startDate = now()->addDays(rand(1, 60));
                $endDate = $startDate->copy()->addDays(rand(1, 7));
                
                $leaveRequests[] = [
                    'staff_id' => $user->id,
                    'branch_id' => $branchId,
                    'leave_type' => $leaveTypes[array_rand($leaveTypes)],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'reason' => $this->getRandomLeaveReason(),
                    'status' => $statuses[array_rand($statuses)],
                    'approved_by' => $adminUserId,
                    'approved_at' => now()->subDays(rand(1, 15)),
                    'approval_notes' => 'Leave request processed',
                ];
            }
        }

        if (empty($leaveRequests)) {
            $this->command->warn("⚠️ No users with valid branch assignment found, skipping leave requests creation");
            return;
        }

        foreach ($leaveRequests as $leaveData) {
            LeaveRequest::create($leaveData);
        }

        $this->command->info("✅ Created " . count($leaveRequests) . " leave requests");
    }

    private function resolveBranchIdForUser(User $user): ?int
    {
        if (! empty($user->assigned_branch_id)) {
            return (int) $user->assigned_branch_id;
        }

        if (! empty($user->facility_id)) {
            return Branch::query()->where('facility_id', $user->facility_id)->value('id');
        }

        return null;
    }

    private function createSleepData(): void
    {
        $residents = Resident::all();

        if ($residents->isEmpty()) {
            $this->command->warn("⚠️ No residents found, skipping sleep data creation");
            return;
        }

        $sleepPatterns = [];
        $sleepRecords = [];
        
        foreach ($residents as $resident) {
            // Create sleep pattern
            $sleepPatterns[] = [
                'resident_id' => $resident->id,
                'branch_id' => $resident->branch_id,
                'date' => now()->subDays(rand(1, 30))->toDateString(),
                'bedtime' => '22:00:00',
                'wake_time' => '07:00:00',
                'total_sleep_hours' => rand(6, 9),
                'sleep_interruptions' => rand(0, 3),
                'notes' => 'Regular sleep pattern - ' . $this->getRandomSleepQuality(),
            ];

            // Create sleep records for the past 7 days
            for ($day = 0; $day < 7; $day++) {
                $sleepDate = now()->subDays($day);
                $sleepRecords[] = [
                    'resident_id' => $resident->id,
                    'branch_id' => $resident->branch_id,
                    'sleep_pattern_id' => null, // Will be set after patterns are created
                    'date' => $sleepDate->toDateString(),
                    'sleep_start' => '22:' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
                    'sleep_end' => '07:' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00',
                    'sleep_duration_minutes' => rand(360, 540), // 6-9 hours in minutes
                    'notes' => 'Sleep was ' . $this->getRandomSleepQuality(),
                ];
            }
        }

        foreach ($sleepPatterns as $patternData) {
            SleepPattern::firstOrCreate(
                [
                    'resident_id' => $patternData['resident_id'],
                    'date' => $patternData['date']
                ],
                $patternData
            );
        }

        foreach ($sleepRecords as $recordData) {
            SleepRecord::firstOrCreate(
                [
                    'resident_id' => $recordData['resident_id'],
                    'date' => $recordData['date']
                ],
                $recordData
            );
        }

        $this->command->info("✅ Created " . count($sleepPatterns) . " sleep patterns and " . count($sleepRecords) . " sleep records");
    }

    private function createMedicationAdministrations(): void
    {
        $medications = Medication::all();
        $users = $this->getOperationalUsers();

        if ($medications->isEmpty() || $users->isEmpty()) {
            $this->command->warn("⚠️ No medications or users found, skipping medication administrations creation");
            return;
        }

        $administrations = [];
        
        foreach ($medications as $medication) {
            // Create administration records for the past 7 days
            for ($day = 0; $day < 7; $day++) {
                $adminDate = now()->subDays($day);
                $adminTime = $adminDate->copy()->setTime(rand(8, 20), rand(0, 59), 0);
                
                $administrations[] = [
                    'medication_id' => $medication->id,
                    'resident_id' => $medication->resident_id,
                    'branch_id' => $medication->branch_id,
                    'administered_by' => $users->random()->id,
                    'administered_at' => $adminTime,
                    'dosage_given' => $medication->quantity,
                    'status' => 'completed',
                    'notes' => 'Medication administered as prescribed',
                ];
            }
        }

        foreach ($administrations as $adminData) {
            MedicationAdministration::create($adminData);
        }

        $this->command->info("✅ Created " . count($administrations) . " medication administrations");
    }

    private function getAdminUserId(): ?int
    {
        $admin = User::query()
            ->whereIn('role', ['admin', 'administrator'])
            ->where('is_active', true)
            ->first();

        if (! $admin) {
            $admin = User::query()->where('is_active', true)->first();
        }

        return $admin?->id;
    }

    private function getOperationalUsers()
    {
        $users = User::query()
            ->whereNotIn('role', ['admin', 'administrator'])
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) {
            $users = User::query()->where('is_active', true)->get();
        }

        return $users;
    }

    // Helper methods for random data generation
    private function getRandomFrequency(): string
    {
        $frequencies = ['Once daily', 'Twice daily', 'Three times daily', 'As needed', 'Every 4 hours'];
        return $frequencies[array_rand($frequencies)];
    }

    private function getRandomRoute(): string
    {
        $routes = ['Oral', 'Topical', 'Injection', 'Inhalation', 'Sublingual'];
        return $routes[array_rand($routes)];
    }

    private function getRandomVitalNotes(): string
    {
        $notes = ['Within normal limits', 'Slightly elevated', 'Stable', 'No concerns', 'Monitor closely'];
        return $notes[array_rand($notes)];
    }

    private function getRandomPainDescription(): string
    {
        $descriptions = ['No pain', 'Mild discomfort', 'Moderate pain', 'Sharp pain', 'Dull ache', 'Burning sensation'];
        return $descriptions[array_rand($descriptions)];
    }

    private function getRandomLastName(): string
    {
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        return $lastNames[array_rand($lastNames)];
    }

    private function getRandomLocation(): string
    {
        $locations = ['Edmond Medical Center', 'Seattle General Hospital', 'UW Medical Center', 'Swedish Hospital', 'Virginia Mason'];
        return $locations[array_rand($locations)];
    }

    private function getRandomShift(): string
    {
        $shifts = ['Day Shift (7AM-3PM)', 'Evening Shift (3PM-11PM)', 'Night Shift (11PM-7AM)'];
        return $shifts[array_rand($shifts)];
    }

    private function getRandomFindings(): string
    {
        $findings = [
            'Resident is stable and doing well',
            'No significant changes noted',
            'Mild improvement in condition',
            'Requires continued monitoring',
            'All vital signs within normal limits'
        ];
        return $findings[array_rand($findings)];
    }

    private function getRandomRecommendations(): string
    {
        $recommendations = [
            'Continue current care plan',
            'Increase monitoring frequency',
            'Consider medication adjustment',
            'Schedule follow-up appointment',
            'Maintain current activity level'
        ];
        return $recommendations[array_rand($recommendations)];
    }

    private function getRandomLeaveReason(): string
    {
        $reasons = [
            'Family emergency',
            'Medical appointment',
            'Personal matters',
            'Vacation time',
            'Sick leave'
        ];
        return $reasons[array_rand($reasons)];
    }

    private function getRandomSleepQuality(): string
    {
        $qualities = ['Excellent', 'Good', 'Fair', 'Poor', 'Very Poor'];
        return $qualities[array_rand($qualities)];
    }

    private function showSummary(): void
    {
        $this->command->line('');
        $this->command->line('📊 Comprehensive Seeding Summary:');
        $this->command->line('  👥 Users: ' . User::count());
        $this->command->line('  🏥 Facilities: ' . Facility::count());
        $this->command->line('  🏢 Branches: ' . Branch::count());
        $this->command->line('  💊 Drugs: ' . Drug::count());
        $this->command->line('  👴 Residents: ' . Resident::count());
        $this->command->line('  💉 Medications: ' . Medication::count());
        $this->command->line('  📊 Vital Signs: ' . VitalSign::count());
        $this->command->line('  📅 Appointments: ' . Appointment::count());
        $this->command->line('  👨‍⚕️ Assignments: ' . Assignment::count());
        $this->command->line('  📋 Assessments: ' . Assessment::count());
        $this->command->line('  🏖️ Leave Requests: ' . LeaveRequest::count());
        $this->command->line('  😴 Sleep Patterns: ' . SleepPattern::count());
        $this->command->line('  📝 Sleep Records: ' . SleepRecord::count());
        $this->command->line('  💊 Medication Administrations: ' . MedicationAdministration::count());
        $this->command->line('');
        $this->command->line('🎉 Your database is now fully populated with realistic data!');
    }
}
