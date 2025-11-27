<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use App\Services\LocationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Branches';
    protected static ?string $modelLabel = 'Branch';
    protected static ?string $pluralModelLabel = 'Branches';
    protected static ?string $navigationGroup = 'Administration';
    protected static bool $shouldRegisterNavigation = false; // Handled by CustomNavigationProvider

    public static function shouldRegisterNavigation(): bool
    {
        // Only register if user has permission AND is not a caregiver
        if (!auth()->check()) {
            return false;
        }
        
        $user = auth()->user();
        
        // Caregivers should NEVER see this in navigation
        $roleValue = strtolower(trim($user->role ?? ''));
        $roleValueNormalized = str_replace([' ', '_'], '', $roleValue);
        $isCaregiver = $user->hasRole('caregiver') || 
                       $user->hasRole('care_giver') || 
                       $roleValueNormalized === 'caregiver' ||
                       (stripos($roleValue, 'care') !== false && stripos($roleValue, 'giver') !== false);
        
        if ($isCaregiver) {
            return false;
        }
        
        return $user->hasPermission('view_branches');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_branches');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('create_branches');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('edit_branches');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('delete_branches');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branch Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter branch name'),
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter full address'),
                        Forms\Components\Select::make('facility_id')
                            ->label('Facility')
                            ->relationship('facility', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->placeholder('(425) 555-0123'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->placeholder('branch@serenityafh.com'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable this branch for use'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Location Coordinates')
                    ->description('Coordinates are used for location-based login restrictions. Click "Geocode from Address" to automatically populate coordinates.')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(-90)
                            ->maxValue(90)
                            ->placeholder('e.g., 47.6062')
                            ->helperText('Latitude coordinate (-90 to 90)'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(-180)
                            ->maxValue(180)
                            ->placeholder('e.g., -122.3321')
                            ->helperText('Longitude coordinate (-180 to 180)'),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('geocode')
                                ->label('Geocode from Address')
                                ->icon('heroicon-o-map-pin')
                                ->color('primary')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $address = $get('address');
                                    if (empty($address)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Address Required')
                                            ->body('Please enter an address before geocoding.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        $locationService = app(LocationService::class);
                                        $coordinates = $locationService->geocodeAddress($address);
                                        
                                        if ($coordinates) {
                                            $set('latitude', $coordinates['latitude']);
                                            $set('longitude', $coordinates['longitude']);
                                            \Filament\Notifications\Notification::make()
                                                ->title('Geocoding Successful')
                                                ->body('Coordinates have been populated from the address.')
                                                ->success()
                                                ->send();
                                        } else {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Geocoding Failed')
                                                ->body('Unable to geocode the address. Please enter coordinates manually.')
                                                ->warning()
                                                ->send();
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Geocoding error in BranchResource', [
                                            'error' => $e->getMessage(),
                                            'address' => $address,
                                        ]);
                                        \Filament\Notifications\Notification::make()
                                            ->title('Geocoding Error')
                                            ->body('An error occurred while geocoding. Please try again or enter coordinates manually.')
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('facility.name')
                    ->label('Facility')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('caregivers_count')
                    ->label('Caregivers')
                    ->counts('caregivers')
                    ->sortable(),
                Tables\Columns\TextColumn::make('residents_count')
                    ->label('Residents')
                    ->counts('residents')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All branches')
                    ->trueLabel('Active branches')
                    ->falseLabel('Inactive branches'),
                Tables\Filters\SelectFilter::make('facility_id')
                    ->label('Facility')
                    ->relationship('facility', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
