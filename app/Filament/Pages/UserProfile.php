<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;

class UserProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $title = 'My Profile';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 1000;
    protected static bool $shouldRegisterNavigation = true;
    protected static string $view = 'filament.pages.user-profile';

    public ?array $data = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'middle_names' => $user->middle_names,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'marital_status' => $user->marital_status,
            'sex' => $user->sex,
            'position' => $user->position,
            'credentials' => $user->credentials,
            'credential_details' => $user->credential_details,
            'date_employed' => $user->date_employed,
            'supervisor_name' => $user->supervisor_name,
            'provider_name' => $user->provider_name,
            'assigned_branch_id' => $user->assigned_branch_id,
            'notes' => $user->notes,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Update your personal details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Full name is automatically generated from first, middle, and last names'),
                        
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->maxLength(255)
                            ->required(),
                        
                        TextInput::make('middle_names')
                            ->label('Middle Name(s)')
                            ->maxLength(255),
                        
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(255)
                            ->required(),
                        
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255)
                            ->required()
                            ->unique(ignoreRecord: true),
                        
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('e.g., +1 (555) 123-4567'),
                        
                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->maxDate(now()->subYears(16))
                            ->helperText('Must be at least 16 years old'),
                        
                        Select::make('marital_status')
                            ->label('Marital Status')
                            ->options(\App\Models\User::getMaritalStatusOptions())
                            ->searchable()
                            ->preload(),
                        
                        Select::make('sex')
                            ->label('Sex')
                            ->options(\App\Models\User::getSexOptions())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make('Employment Information')
                    ->description('Work-related details')
                    ->schema([
                        Select::make('position')
                            ->label('Position')
                            ->options(\App\Models\User::getPositionOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        TextInput::make('credentials')
                            ->label('Credentials')
                            ->maxLength(255)
                            ->placeholder('e.g., RN, LPN, CNA'),
                        
                        TextInput::make('credential_details')
                            ->label('Credential Details')
                            ->maxLength(255)
                            ->placeholder('e.g., License number, expiration date'),
                        
                        DatePicker::make('date_employed')
                            ->label('Date Employed')
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->maxDate(now())
                            ->helperText('Employment start date'),
                        
                        TextInput::make('supervisor_name')
                            ->label('Supervisor Name')
                            ->maxLength(255),
                        
                        TextInput::make('provider_name')
                            ->label('Provider Name')
                            ->maxLength(255),
                        
                        Select::make('assigned_branch_id')
                            ->label('Assigned Branch')
                            ->relationship('assignedBranch', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Branch assignment is managed by administrators'),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->description('Miscellaneous details and notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->placeholder('Any additional information...')
                            ->helperText('Internal notes about your profile'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data')
            ->model(Auth::user());
    }

    public function save(): void
    {
        $user = Auth::user();
        $data = $this->form->getState();
        
        $user->update($data);
        
        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Change Password')
                    ->description('Update your account password')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->revealable(),
                        
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->confirmed()
                            ->revealable(),
                        
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->required()
                            ->revealable(),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    public function changePassword(): void
    {
        $data = $this->passwordForm->getState();
        $user = Auth::user();
        
        if (!Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Current password is incorrect')
                ->danger()
                ->send();
            return;
        }
        
        $user->update([
            'password' => Hash::make($data['new_password'])
        ]);
        
        Notification::make()
            ->title('Password changed successfully')
            ->success()
            ->send();
        
        $this->reset('passwordData');
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

