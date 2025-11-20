<?php

namespace App\Filament\Resources\FacilityRegistrationResource\Pages;

use App\Filament\Resources\FacilityRegistrationResource;
use App\Models\Facility;
use App\Models\FacilityRegistration;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApproveFacilityRegistration extends EditRecord
{
    protected static string $resource = FacilityRegistrationResource::class;

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        if ($this->record->status !== 'pending') {
            Notification::make()
                ->title('This registration has already been processed')
                ->warning()
                ->send();
            
            $this->redirect(static::getResource()::getUrl('index'));
            return;
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            'facility_name' => $this->record->facility_name,
            'subdomain' => $this->record->requested_subdomain ?? Str::slug($this->record->facility_name),
            'address' => $this->record->address,
            'phone' => $this->record->phone,
            'email' => $this->record->email,
            'owner_name' => $this->record->contact_name,
            'owner_email' => $this->record->email,
            'owner_role' => 'administrator',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Facility Information')
                    ->schema([
                        Forms\Components\TextInput::make('facility_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subdomain')
                            ->required()
                            ->maxLength(255)
                            ->unique('facilities', 'subdomain', ignoreRecord: true)
                            ->helperText('Subdomain for facility-specific URL'),
                        Forms\Components\Textarea::make('address')
                            ->rows(3),
                        Forms\Components\TextInput::make('phone')
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->email(),
                    ]),
                Forms\Components\Section::make('Initial Branch Setup')
                    ->schema([
                        Forms\Components\TextInput::make('branch_name')
                            ->default('Main Branch')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('branch_address')
                            ->rows(2),
                    ]),
                Forms\Components\Section::make('Facility Owner Account')
                    ->schema([
                        Forms\Components\TextInput::make('owner_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('owner_email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('owner_role')
                            ->options([
                                'administrator' => 'Administrator',
                                'manager' => 'Manager',
                                'clinical_supervisor' => 'Clinical Supervisor',
                            ])
                            ->required()
                            ->default('administrator'),
                        Forms\Components\TextInput::make('owner_password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->helperText('Password for the facility owner account'),
                    ]),
            ])
            ->statePath('data');
    }

    public function approve(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            // Create facility
            $facility = Facility::create([
                'name' => $data['facility_name'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'subdomain' => $data['subdomain'],
                'registration_status' => 'approved',
                'is_active' => true,
            ]);

            // Create initial branch
            $branch = $facility->branches()->create([
                'name' => $data['branch_name'] ?? 'Main Branch',
                'address' => $data['branch_address'] ?? $data['address'] ?? null,
                'is_active' => true,
            ]);

            // Create facility owner account
            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
                'role' => $data['owner_role'],
                'facility_id' => $facility->id,
                'assigned_branch_id' => $branch->id,
                'is_active' => true,
            ]);

            // Update facility with owner reference
            $facility->update([
                'registered_by_user_id' => $owner->id,
            ]);

            // Update registration status
            $this->record->update([
                'status' => 'approved',
                'approved_by_user_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Facility approved and set up successfully')
                ->body("Facility '{$facility->name}' has been created with owner account.")
                ->success()
                ->send();
        });

        $this->redirect(static::getResource()::getUrl('index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve & Create Facility')
                ->action('approve')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Facility Registration')
                ->modalDescription('This will create the facility, initial branch, and owner account. Continue?'),
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}

