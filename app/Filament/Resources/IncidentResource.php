<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Incidents';
    protected static ?string $modelLabel = 'Incident';
    protected static ?string $pluralModelLabel = 'Incidents';
    protected static ?string $navigationGroup = 'Resident Care';
    protected static ?int $navigationSort = 40;
    protected static bool $shouldRegisterNavigation = false; // Handled by CustomNavigationProvider

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Navigation is handled by CustomNavigationProvider
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('view_incidents');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('create_incidents');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasPermission('edit_incidents');
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasPermission('delete_incidents');
    }

    public static function form(Form $form): Form
    {
        $incidentModel = new Incident();
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Essential incident details')
                    ->schema([
                        Forms\Components\Select::make('resident_id')
                            ->label('Resident')
                            ->relationship('resident', 'first_name', fn ($query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                            ->searchable(['first_name', 'last_name'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                if ($state) {
                                    $resident = Resident::find($state);
                                    if ($resident && $resident->branch_id) {
                                        $set('branch_id', $resident->branch_id);
                                    }
                                }
                            }),
                        
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Forms\Get $get) => !empty($get('resident_id')))
                            ->live(),
                        
                        Forms\Components\Select::make('incident_type')
                            ->label('Incident Type')
                            ->options($incidentModel->getIncidentTypeOptions())
                            ->searchable()
                            ->required()
                            ->placeholder('Select incident type'),
                        
                        Forms\Components\DateTimePicker::make('incident_date')
                            ->label('Incident Date & Time')
                            ->required()
                            ->native(false)
                            ->default(now())
                            ->displayFormat('M j, Y g:i A')
                            ->seconds(false),
                        
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->maxLength(255)
                            ->placeholder('e.g., Room 101, Main Hallway, Dining Area')
                            ->helperText('Where did the incident occur?'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Incident Details')
                    ->description('Description and classification')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Provide a detailed description of the incident...')
                            ->helperText('Include what happened, who was involved, and any immediate observations'),
                        
                        Forms\Components\Select::make('severity')
                            ->label('Severity')
                            ->options($incidentModel->getSeverityOptions())
                            ->required()
                            ->default(Incident::SEVERITY_LOW)
                            ->native(false),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options($incidentModel->getPriorityOptions())
                            ->required()
                            ->default(Incident::PRIORITY_MEDIUM)
                            ->native(false),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options($incidentModel->getStatusOptions())
                            ->required()
                            ->default(Incident::STATUS_OPEN)
                            ->native(false)
                            ->disabled(fn ($record) => $record && $record->isClosed()),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Response & Actions')
                    ->description('Immediate actions taken and assignment')
                    ->schema([
                        Forms\Components\Textarea::make('action_taken')
                            ->label('Action Taken')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Describe the immediate actions taken in response to this incident...')
                            ->helperText('What was done immediately after the incident occurred?'),
                        
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedTo', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select staff member to handle this incident')
                            ->helperText('Assign to a staff member for follow-up'),
                        
                        Forms\Components\Textarea::make('witnesses')
                            ->label('Witnesses')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('List any witnesses to the incident (names and roles)...')
                            ->helperText('Include names and roles of anyone who witnessed the incident'),
                        
                        Forms\Components\Hidden::make('reported_by')
                            ->default(Auth::id())
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Follow-up & Resolution')
                    ->description('Follow-up actions and resolution details')
                    ->schema([
                        Forms\Components\Textarea::make('follow_up')
                            ->label('Follow-up Actions')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Describe planned or completed follow-up actions...')
                            ->helperText('Preventive measures, additional care needed, etc.'),
                        
                        Forms\Components\Select::make('resolved_by')
                            ->label('Resolved By')
                            ->relationship('resolvedBy', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->visible(fn ($record) => $record && ($record->isResolved() || $record->isClosed()))
                            ->disabled(fn ($record) => $record && $record->isClosed()),
                        
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Resolved At')
                            ->native(false)
                            ->displayFormat('M j, Y g:i A')
                            ->seconds(false)
                            ->visible(fn ($record) => $record && ($record->isResolved() || $record->isClosed()))
                            ->disabled(fn ($record) => $record && $record->isClosed()),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($operation) => $operation === 'create'),
                
                Forms\Components\Section::make('Attachments')
                    ->description('Photos, documents, or other files related to this incident')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->label('Files')
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('File')
                                    ->disk('public')
                                    ->directory('incident-attachments')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->maxSize(10240) // 10MB
                                    ->required()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            // For TemporaryUploadedFile, extract name
                                            if (is_object($state) && method_exists($state, 'getClientOriginalName')) {
                                                $set('file_name', $state->getClientOriginalName());
                                                $set('file_size', $state->getSize());
                                                $set('mime_type', $state->getMimeType());
                                            } elseif (is_string($state)) {
                                                // Already stored file
                                                $set('file_name', basename($state));
                                            }
                                        }
                                    }),
                                
                                Forms\Components\TextInput::make('file_name')
                                    ->label('File Name')
                                    ->maxLength(255)
                                    ->required(),
                                
                                Forms\Components\Select::make('file_type')
                                    ->label('File Type')
                                    ->options([
                                        'photo' => 'Photo',
                                        'document' => 'Document',
                                        'video' => 'Video',
                                        'other' => 'Other',
                                    ])
                                    ->default('photo'),
                                
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->placeholder('Brief description of this attachment...'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['file_name'] ?? null)
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('Add Attachment')
                            ->reorderable()
                            ->helperText('Upload photos, documents, or other files related to this incident'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $incidentModel = new Incident();
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incident_number')
                    ->label('Incident #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Incident number copied')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('resident.name')
                    ->label('Resident')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->resident ? trim($record->resident->first_name . ' ' . $record->resident->last_name) : 'N/A'),
                
                Tables\Columns\TextColumn::make('incident_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('incident_date')
                    ->label('Date & Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        'on_hold' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                        'on_hold' => 'On Hold',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('reportedBy.name')
                    ->label('Reported By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Unassigned'),
                
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options($incidentModel->getStatusOptions())
                    ->multiple(),
                
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options($incidentModel->getPriorityOptions())
                    ->multiple(),
                
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options($incidentModel->getSeverityOptions())
                    ->multiple(),
                
                SelectFilter::make('incident_type')
                    ->label('Incident Type')
                    ->options($incidentModel->getIncidentTypeOptions())
                    ->multiple()
                    ->searchable(),
                
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedTo', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Filter::make('incident_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('incident_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('incident_date', '<=', $date),
                            );
                    }),
                
                Filter::make('critical')
                    ->label('Critical Incidents')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('severity', 'critical')
                          ->orWhere('priority', 'critical');
                    })),
                
                Filter::make('open')
                    ->label('Open Incidents')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'open')),
                
                Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_to')),
                
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('mark_resolved')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Incident $record, array $data) {
                        $record->markAsResolved(Auth::user(), $data['notes'] ?? null);
                    })
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Resolution Notes')
                            ->rows(3)
                            ->placeholder('Optional notes about the resolution...'),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (Incident $record): bool => !$record->isResolved() && !$record->isClosed()),
                
                Action::make('mark_closed')
                    ->label('Mark Closed')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->action(function (Incident $record, array $data) {
                        $record->markAsClosed(Auth::user(), $data['notes'] ?? null);
                    })
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Closing Notes')
                            ->rows(3)
                            ->placeholder('Optional notes about closing this incident...'),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (Incident $record): bool => $record->isResolved() && !$record->isClosed()),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('incident_date', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'view' => Pages\ViewIncident::route('/{record}'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        
        // If user is a caregiver, show incidents for residents in their assigned branch only
        if (auth()->user()->hasRole('caregiver')) {
            $query->whereHas('resident', function ($q) {
                $q->where('branch_id', auth()->user()->assigned_branch_id);
            });
        }
        
        return $query;
    }
}
