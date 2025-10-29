<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\Resident;
use App\Models\User;
use Carbon\Carbon;

class AssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::all();
        $users = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['administrator', 'super_admin', 'caregiver']);
        })->get();

        if ($residents->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No residents or users found. Please run ResidentSeeder and UserSeeder first.');
            return;
        }

        $assessmentTypes = ['initial', 'periodic', 'focused', 'discharge'];
        $statuses = ['draft', 'submitted', 'reviewed', 'approved', 'archived'];

        foreach ($residents as $resident) {
            // Create 3-8 assessments per resident
            $assessmentCount = rand(3, 8);
            
            for ($i = 0; $i < $assessmentCount; $i++) {
                $createdAt = Carbon::now()->subDays(rand(1, 365));
                $assessor = $users->random();
                
                $assessment = Assessment::create([
                    'resident_id' => $resident->id,
                    'branch_id' => $resident->branch_id,
                    'assessor_id' => $assessor->id,
                    'assessment_type' => $assessmentTypes[array_rand($assessmentTypes)],
                    'assessment_date' => $createdAt->format('Y-m-d'),
                    'status' => $statuses[array_rand($statuses)],
                    'notes' => $this->generateAssessmentNotes(),
                    'scores' => $this->generateScores(),
                    'recommendations' => $this->generateRecommendations(),
                    'completed_at' => $createdAt->addDays(rand(1, 7)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Create assessment sections for this assessment
                $this->createAssessmentSections($assessment, $createdAt);
            }
        }

        $this->command->info('AssessmentSeeder completed successfully!');
    }

    private function generateAssessmentNotes(): string
    {
        $notes = [
            'Resident shows good overall health with minor concerns about mobility.',
            'Assessment completed successfully. Resident is cooperative and engaged.',
            'Some cognitive decline noted. Recommend regular monitoring.',
            'Physical health is stable. Continue current care plan.',
            'Social engagement has improved significantly since last assessment.',
            'Nutrition status is good. Resident maintains healthy eating habits.',
            'Medication compliance is excellent. No issues reported.',
            'Safety concerns addressed. Additional support measures implemented.',
            'Resident expresses satisfaction with current care arrangements.',
            'Family involvement has been positive and supportive.',
        ];

        return $notes[array_rand($notes)];
    }

    private function generateScores(): array
    {
        return [
            'physical_health' => rand(50, 100),
            'mental_health' => rand(50, 100),
            'social_wellbeing' => rand(50, 100),
            'cognitive_function' => rand(50, 100),
            'mobility' => rand(40, 100),
            'nutrition' => rand(50, 100),
            'medication_compliance' => rand(60, 100),
            'safety' => rand(70, 100),
            'overall_score' => rand(60, 100)
        ];
    }

    private function generateRecommendations(): array
    {
        $recommendations = [
            'Continue current care plan with monthly reviews.',
            'Increase physical activity and mobility exercises.',
            'Schedule follow-up assessment in 3 months.',
            'Monitor medication effectiveness and side effects.',
            'Enhance social activities and community engagement.',
            'Review nutrition plan and dietary requirements.',
            'Implement additional safety measures.',
            'Coordinate with healthcare providers for specialized care.',
            'Family education and support sessions recommended.',
            'Regular cognitive assessments and memory exercises.',
        ];

        return [
            $recommendations[array_rand($recommendations)],
            $recommendations[array_rand($recommendations)]
        ];
    }

    /**
     * Create assessment sections for an assessment.
     */
    private function createAssessmentSections(Assessment $assessment, Carbon $createdAt): void
    {
        $sectionTypes = [
            'demographic',
            'medical_history',
            'functional',
            'cognitive',
            'behavioral',
            'nutritional',
            'environmental',
            'risk'
        ];

        // Create 4-6 sections per assessment
        $sectionCount = rand(4, 6);
        $selectedSections = array_slice($sectionTypes, 0, $sectionCount);

        foreach ($selectedSections as $sectionType) {
            AssessmentSection::create([
                'assessment_id' => $assessment->id,
                'section_type' => $sectionType,
                'score' => rand(50, 100),
                'notes' => "Assessment notes for {$sectionType} section.",
                'is_completed' => rand(0, 1) === 1,
                'completed_at' => $assessment->is_completed ? $createdAt->copy() : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
