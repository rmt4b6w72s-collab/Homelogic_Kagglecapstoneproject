<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Resident;
use App\Models\Assessment;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class AssessmentPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Create Assessment';
    protected static ?string $title = 'Create Assessment';
    protected static ?string $navigationGroup = 'Assessment Management';
    protected static string $view = 'filament.pages.assessment';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public ?int $selectedBranchId = null;
    public ?int $selectedResidentId = null;
    public ?string $assessmentType = 'initial';

    public function mount(): void
    {
        $this->data = [
            'branch_id' => null,
            'resident_id' => null,
            'assessment_type' => 'initial',
            'assessment_date' => now(),
        ];
        $this->form->fill($this->data);
    }

    public function updatedDataBranchId($value): void
    {
        $this->data['resident_id'] = null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Assessment Details')
                    ->description('Select branch and resident for assessment')
                    ->schema([
                        Select::make('branch_id')
                            ->label('Select Branch')
                            ->options(
                                Branch::where('is_active', true)
                                    ->whereNotNull('name')
                                    ->pluck('name', 'id')
                                    ->filter()
                                    ->toArray()
                            )
                            ->searchable()
                            ->live()
                            ->placeholder('Choose a branch...'),

                        Select::make('resident_id')
                            ->label('Select Resident')
                            ->options(function (callable $get) {
                                $branchId = $get('branch_id');
                                if (!$branchId) {
                                    return [];
                                }
                                return Resident::where('branch_id', $branchId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->pluck('name', 'id')
                                    ->filter()
                                    ->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->disabled(fn (callable $get) => !$get('branch_id'))
                            ->placeholder('Choose a resident...'),

                        Select::make('assessment_type')
                            ->label('Assessment Type')
                            ->options([
                                'initial' => 'Initial Assessment',
                                'periodic' => 'Periodic Assessment',
                                'focused' => 'Focused Assessment',
                                'discharge' => 'Discharge Assessment',
                            ])
                            ->default('initial')
                            ->searchable(),

                        DatePicker::make('assessment_date')
                            ->label('Assessment Date')
                            ->native(false)
                            ->default(now()),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function startAssessmentAction(): Action
    {
        return Action::make('startAssessment')
            ->label('Start Assessment')
            ->color('primary')
            ->size('lg')
            ->action(function () {
                $data = $this->form->getState();

                // Validate required fields
                if (!$data['branch_id'] || !$data['resident_id']) {
                    Notification::make()
                        ->title('Missing Information')
                        ->body('Please select both a branch and a resident.')
                        ->warning()
                        ->send();
                    return;
                }

                // Create new assessment
                $assessment = Assessment::create([
                    'resident_id' => $data['resident_id'],
                    'branch_id' => $data['branch_id'],
                    'assessor_id' => Auth::id(),
                    'assessment_type' => $data['assessment_type'],
                    'assessment_date' => $data['assessment_date'],
                    'status' => 'draft',
                ]);

                // Create assessment sections
                $this->createAssessmentSections($assessment);

                Notification::make()
                    ->title('Assessment Started')
                    ->body('Assessment has been created and is ready for completion.')
                    ->success()
                    ->send();

                // Reset form
                $this->form->fill([
                    'branch_id' => null,
                    'resident_id' => null,
                    'assessment_type' => 'initial',
                    'assessment_date' => now(),
                ]);

                // Redirect to assessment form
                $this->redirect('/admin/assessment-form?assessment=' . $assessment->id);
            });
    }

    protected function createAssessmentSections(Assessment $assessment): void
    {
        $sections = [
            'demographic' => [
                'title' => 'Demographic Information',
                'questions' => [
                    ['text' => 'What is the resident\'s full name?', 'type' => 'text'],
                    ['text' => 'Date of birth?', 'type' => 'date'],
                    ['text' => 'Gender', 'type' => 'radio', 'options' => ['Male', 'Female', 'Other']],
                    ['text' => 'Marital status', 'type' => 'select', 'options' => ['Single', 'Married', 'Divorced', 'Widowed']],
                    ['text' => 'Emergency contact name', 'type' => 'text'],
                    ['text' => 'Emergency contact phone', 'type' => 'text'],
                ]
            ],
            'medical_history' => [
                'title' => 'Medical History',
                'questions' => [
                    ['text' => 'Primary diagnosis', 'type' => 'text'],
                    ['text' => 'Secondary diagnoses', 'type' => 'text'],
                    ['text' => 'Current medications', 'type' => 'text'],
                    ['text' => 'Known allergies', 'type' => 'text'],
                    ['text' => 'Physician name', 'type' => 'text'],
                    ['text' => 'Physician phone', 'type' => 'text'],
                    ['text' => 'Last physical exam date', 'type' => 'date'],
                ]
            ],
            'functional' => [
                'title' => 'Functional Assessment',
                'questions' => [
                    ['text' => 'Can resident walk independently?', 'type' => 'radio', 'options' => ['Yes', 'No', 'With assistance']],
                    ['text' => 'Can resident dress independently?', 'type' => 'radio', 'options' => ['Yes', 'No', 'With assistance']],
                    ['text' => 'Can resident eat independently?', 'type' => 'radio', 'options' => ['Yes', 'No', 'With assistance']],
                    ['text' => 'Can resident bathe independently?', 'type' => 'radio', 'options' => ['Yes', 'No', 'With assistance']],
                    ['text' => 'Can resident use toilet independently?', 'type' => 'radio', 'options' => ['Yes', 'No', 'With assistance']],
                    ['text' => 'Mobility aids used', 'type' => 'checkbox', 'options' => ['Walker', 'Wheelchair', 'Cane', 'None']],
                ]
            ],
            'cognitive' => [
                'title' => 'Cognitive Assessment',
                'questions' => [
                    ['text' => 'Is resident oriented to person?', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Is resident oriented to place?', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Is resident oriented to time?', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Memory assessment', 'type' => 'select', 'options' => ['Excellent', 'Good', 'Fair', 'Poor']],
                    ['text' => 'Communication ability', 'type' => 'select', 'options' => ['Clear speech', 'Some difficulty', 'Significant difficulty', 'Non-verbal']],
                    ['text' => 'Decision-making capacity', 'type' => 'select', 'options' => ['Independent', 'With assistance', 'Limited', 'None']],
                ]
            ],
            'behavioral' => [
                'title' => 'Behavioral Assessment',
                'questions' => [
                    ['text' => 'Does resident have any behavioral concerns?', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Describe any behavioral issues', 'type' => 'text'],
                    ['text' => 'Agitation level', 'type' => 'select', 'options' => ['None', 'Mild', 'Moderate', 'Severe']],
                    ['text' => 'Sleep patterns', 'type' => 'select', 'options' => ['Normal', 'Restless', 'Insomnia', 'Oversleeping']],
                    ['text' => 'Social interaction', 'type' => 'select', 'options' => ['Very social', 'Moderate', 'Withdrawn', 'Isolated']],
                    ['text' => 'Safety concerns', 'type' => 'text'],
                ]
            ],
            'nutritional' => [
                'title' => 'Nutritional Assessment',
                'questions' => [
                    ['text' => 'Can resident feed themselves?', 'type' => 'radio', 'options' => ['Yes', 'No', 'With assistance']],
                    ['text' => 'Dietary restrictions', 'type' => 'text'],
                    ['text' => 'Appetite level', 'type' => 'select', 'options' => ['Excellent', 'Good', 'Fair', 'Poor']],
                    ['text' => 'Weight concerns', 'type' => 'select', 'options' => ['None', 'Weight loss', 'Weight gain', 'Fluctuating']],
                    ['text' => 'Fluid intake adequate', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Special dietary needs', 'type' => 'text'],
                ]
            ],
            'environmental' => [
                'title' => 'Environmental Assessment',
                'questions' => [
                    ['text' => 'Room accessibility', 'type' => 'select', 'options' => ['Fully accessible', 'Mostly accessible', 'Some limitations', 'Major limitations']],
                    ['text' => 'Safety equipment present', 'type' => 'checkbox', 'options' => ['Grab bars', 'Emergency call system', 'Bed rails', 'None needed']],
                    ['text' => 'Lighting adequate', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Temperature comfortable', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Noise level appropriate', 'type' => 'radio', 'options' => ['Yes', 'No']],
                    ['text' => 'Environmental hazards', 'type' => 'text'],
                ]
            ],
            'risk' => [
                'title' => 'Risk Assessment',
                'questions' => [
                    ['text' => 'Fall risk level', 'type' => 'select', 'options' => ['Low', 'Moderate', 'High', 'Very High']],
                    ['text' => 'Wandering risk', 'type' => 'radio', 'options' => ['None', 'Low', 'Moderate', 'High']],
                    ['text' => 'Elopement risk', 'type' => 'radio', 'options' => ['None', 'Low', 'Moderate', 'High']],
                    ['text' => 'Medication errors risk', 'type' => 'radio', 'options' => ['None', 'Low', 'Moderate', 'High']],
                    ['text' => 'Infection risk', 'type' => 'radio', 'options' => ['None', 'Low', 'Moderate', 'High']],
                    ['text' => 'Additional risk factors', 'type' => 'text'],
                ]
            ],
        ];

        foreach ($sections as $type => $sectionData) {
            $section = $assessment->sections()->create([
                'section_type' => $type,
                'is_completed' => false,
            ]);

            // Create questions for this section
            foreach ($sectionData['questions'] as $questionData) {
                $section->questions()->create([
                    'question_text' => $questionData['text'],
                    'response_type' => $questionData['type'],
                    'response_options' => $questionData['options'] ?? null,
                    'response_value' => null,
                    'weight' => 1,
                ]);
            }
        }
    }

    public function getResidents(): Collection
    {
        $branchId = $this->data['branch_id'] ?? null;
        
        if (!$branchId) {
            return Resident::where('id', 0)->get(); // Return empty Eloquent Collection
        }

        return Resident::where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNotNull('name')
            ->with(['branch'])
            ->get();
    }

    public function getBranches(): Collection
    {
        return Branch::where('is_active', true)
            ->with(['residents'])
            ->get();
    }

    public function getRecentAssessments(): Collection
    {
        return Assessment::with(['resident', 'branch', 'assessor'])
            ->where('assessor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }
}
