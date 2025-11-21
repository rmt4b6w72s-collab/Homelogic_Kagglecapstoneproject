<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentDocumentResource\Pages;
use App\Filament\Resources\ResidentDocumentResource\RelationManagers;
use App\Models\ResidentDocument;
use App\Models\Resident;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;

class ResidentDocumentResource extends Resource
{
    protected static ?string $model = ResidentDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationLabel = 'Resident Documents';
    protected static ?string $modelLabel = 'Resident Document';
    protected static ?string $pluralModelLabel = 'Resident Documents';
    protected static ?string $navigationGroup = 'Resident Care';
    protected static bool $shouldRegisterNavigation = false; // Handled via relation manager

    public static function canViewAny(): bool
    {
        return auth()->user()->hasPermission('view_resident_documents');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission('upload_resident_documents');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermission('upload_resident_documents');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermission('upload_resident_documents');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Document Information')
                    ->schema([
                        Select::make('resident_id')
                            ->label('Resident')
                            ->options(
                                Resident::where('is_active', true)
                                    ->whereNotNull('name')
                                    ->pluck('name', 'id')
                                    ->filter()
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $resident = Resident::find($state);
                                    if ($resident && $resident->branch_id) {
                                        // Could set branch context if needed
                                    }
                                }
                            }),

                        Select::make('appointment_id')
                            ->label('Related Appointment (Optional)')
                            ->options(function (Forms\Get $get) {
                                $residentId = $get('resident_id');
                                if (!$residentId) {
                                    return [];
                                }
                                return Appointment::where('resident_id', $residentId)
                                    ->orderBy('appointment_date', 'desc')
                                    ->get()
                                    ->mapWithKeys(function ($appointment) {
                                        $date = $appointment->appointment_date?->format('M j, Y');
                                        $type = $appointment->appointmentType?->name ?? 'Appointment';
                                        return [$appointment->id => "{$date} - {$type}"];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Forms\Get $get) => !empty($get('resident_id'))),

                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Medical Report, Insurance Card, etc.'),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'insurance' => 'Insurance',
                                'medical' => 'Medical',
                                'legal' => 'Legal',
                                'admission' => 'Admission',
                                'appointment' => 'Appointment',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->searchable(),

                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->disk('public')
                            ->directory('resident-documents')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file): string {
                                    $originalName = $file->getClientOriginalName();
                                    return time() . '_' . $originalName;
                                }
                            )
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $file = \Illuminate\Http\UploadedFile::createFromBase($state);
                                    $set('file_name', $file->getClientOriginalName());
                                    $set('file_size', $file->getSize());
                                    $set('mime_type', $file->getMimeType());
                                }
                            }),

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
                            ->columnSpanFull()
                            ->placeholder('Additional notes about this document...'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resident.name')
                    ->label('Resident')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('document_name')
                    ->label('Document Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'insurance' => 'primary',
                            'medical' => 'success',
                            'legal' => 'warning',
                            'admission' => 'info',
                            'appointment' => 'danger',
                            'other' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'insurance' => 'Insurance',
                            'medical' => 'Medical',
                            'legal' => 'Legal',
                            'admission' => 'Admission',
                            'appointment' => 'Appointment',
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

                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        $units = ['B', 'KB', 'MB', 'GB'];
                        $size = $state;
                        $unit = 0;
                        while ($size >= 1024 && $unit < count($units) - 1) {
                            $size /= 1024;
                            $unit++;
                        }
                        return round($size, 2) . ' ' . $units[$unit];
                    }),

                TextColumn::make('appointment.appointment_date')
                    ->label('Related Appointment')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options([
                        'insurance' => 'Insurance',
                        'medical' => 'Medical',
                        'legal' => 'Legal',
                        'admission' => 'Admission',
                        'appointment' => 'Appointment',
                        'other' => 'Other',
                    ]),

                SelectFilter::make('resident_id')
                    ->label('Resident')
                    ->relationship('resident', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->color('info'),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (ResidentDocument $record): string => asset('storage/' . $record->file_path))
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
            'index' => Pages\ListResidentDocuments::route('/'),
            'create' => Pages\CreateResidentDocument::route('/create'),
            'edit' => Pages\EditResidentDocument::route('/{record}/edit'),
        ];
    }
}
