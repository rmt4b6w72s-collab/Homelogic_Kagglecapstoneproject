<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeDocumentResource\Pages;
use App\Filament\Resources\EmployeeDocumentResource\RelationManagers;
use App\Models\EmployeeDocument;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeDocumentResource extends Resource
{
    protected static ?string $model = EmployeeDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationLabel = 'Employee Documents';
    protected static ?string $modelLabel = 'Employee Document';
    protected static ?string $pluralModelLabel = 'Employee Documents';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 10;
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
        
        return $user->hasRole('administrator') || $user->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Document Information')
                    ->schema([
                        Select::make('user_id')
                            ->label('Employee')
                            ->options(
                                User::where('is_active', true)
                                    ->whereNotNull('name')
                                    ->pluck('name', 'id')
                                    ->filter()
                            )
                            ->searchable()
                            ->required(),

                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->required()
                            ->maxLength(255),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'contract' => 'Employment Contract',
                                'id' => 'ID Document',
                                'license' => 'Professional License',
                                'certification' => 'Certification',
                                'background_check' => 'Background Check',
                                'medical' => 'Medical Clearance',
                                'training' => 'Training Certificate',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->searchable(),

                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->disk('public')
                            ->directory('employee-documents')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file): string {
                                    $originalName = $file->getClientOriginalName();
                                    return time() . '_' . $originalName;
                                }
                            )
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $file = \Illuminate\Http\UploadedFile::createFromBase($state);
                                    $set('file_name', $file->getClientOriginalName());
                                    $set('file_size', $file->getSize());
                                    $set('mime_type', $file->getMimeType());
                                }
                            }),

                        DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->native(false)
                            ->nullable()
                            ->after('today'),

                        TextInput::make('file_name')
                            ->label('File Name')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('file_size')
                            ->label('File Size (bytes)')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('mime_type')
                            ->label('MIME Type')
                            ->disabled()
                            ->dehydrated(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('document_name')
                    ->label('Document Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'contract' => 'primary',
                            'id' => 'success',
                            'license' => 'warning',
                            'certification' => 'info',
                            'background_check' => 'danger',
                            'medical' => 'success',
                            'training' => 'warning',
                            'other' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'contract' => 'Contract',
                            'id' => 'ID Document',
                            'license' => 'License',
                            'certification' => 'Certification',
                            'background_check' => 'Background Check',
                            'medical' => 'Medical',
                            'training' => 'Training',
                            'other' => 'Other',
                            default => ucfirst($state),
                        };
                    }),

                TextColumn::make('file_name')
                    ->label('File')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return $state;
                    }),

                TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(function ($record): string {
                        if (!$record->expiration_date) return 'gray';
                        return $record->is_expired ? 'danger' : 
                               ($record->days_until_expiration <= 30 ? 'warning' : 'success');
                    }),

                TextColumn::make('days_until_expiration')
                    ->label('Days Until Expiry')
                    ->getStateUsing(function ($record): ?string {
                        if (!$record->expiration_date) return 'N/A';
                        $days = $record->days_until_expiration;
                        if ($days < 0) return 'Expired';
                        return $days . ' days';
                    })
                    ->color(function ($record): string {
                        if (!$record->expiration_date) return 'gray';
                        $days = $record->days_until_expiration;
                        if ($days < 0) return 'danger';
                        if ($days <= 30) return 'warning';
                        return 'success';
                    }),

                IconColumn::make('is_expired')
                    ->label('Expired')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options([
                        'contract' => 'Employment Contract',
                        'id' => 'ID Document',
                        'license' => 'Professional License',
                        'certification' => 'Certification',
                        'background_check' => 'Background Check',
                        'medical' => 'Medical Clearance',
                        'training' => 'Training Certificate',
                        'other' => 'Other',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_expired')
                    ->label('Expired')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->where('expiration_date', '<', now()),
                        false: fn (Builder $query) => $query->where('expiration_date', '>=', now()),
                        blank: fn (Builder $query) => $query,
                    ),

                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->color('info'),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (EmployeeDocument $record): string => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->label('Edit')
                    ->color('warning'),

                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListEmployeeDocuments::route('/'),
            'create' => Pages\CreateEmployeeDocument::route('/create'),
            'edit' => Pages\EditEmployeeDocument::route('/{record}/edit'),
        ];
    }
}
