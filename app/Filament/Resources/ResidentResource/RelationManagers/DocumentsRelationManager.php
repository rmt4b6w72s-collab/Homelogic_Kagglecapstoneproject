<?php

namespace App\Filament\Resources\ResidentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use App\Models\Appointment;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Document Information')
                    ->schema([
                        Select::make('appointment_id')
                            ->label('Related Appointment (Optional)')
                            ->options(function () {
                                $resident = $this->getOwnerRecord();
                                if (!$resident) {
                                    return [];
                                }
                                return Appointment::where('resident_id', $resident->id)
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
                            ->nullable(),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_name')
            ->columns([
                TextColumn::make('document_name')
                    ->label('Document Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn ($record): string => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
