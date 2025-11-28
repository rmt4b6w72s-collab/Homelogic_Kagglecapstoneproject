<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use App\Models\IncidentAttachment;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ViewIncident extends ViewRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('mark_resolved')
                ->label('Mark Resolved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function (array $data) {
                    $this->record->markAsResolved(auth()->user(), $data['notes'] ?? null);
                    $this->refreshFormData([
                        'status',
                        'resolved_by',
                        'resolved_at',
                        'follow_up',
                    ]);
                })
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Resolution Notes')
                        ->rows(3)
                        ->placeholder('Optional notes about the resolution...'),
                ])
                ->requiresConfirmation()
                ->visible(fn (): bool => !$this->record->isResolved() && !$this->record->isClosed()),
            
            Actions\Action::make('mark_closed')
                ->label('Mark Closed')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->action(function (array $data) {
                    $this->record->markAsClosed(auth()->user(), $data['notes'] ?? null);
                    $this->refreshFormData([
                        'status',
                        'resolved_by',
                        'resolved_at',
                        'follow_up',
                    ]);
                })
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Closing Notes')
                        ->rows(3)
                        ->placeholder('Optional notes about closing this incident...'),
                ])
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->isResolved() && !$this->record->isClosed()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextEntry::make('incident_number')
                            ->label('Incident Number')
                            ->copyable()
                            ->copyMessage('Incident number copied')
                            ->copyMessageDuration(1500)
                            ->icon('heroicon-o-hashtag'),
                        
                        TextEntry::make('resident.name')
                            ->label('Resident')
                            ->formatStateUsing(fn ($record) => $record->resident ? trim($record->resident->first_name . ' ' . $record->resident->last_name) : 'N/A')
                            ->icon('heroicon-o-user'),
                        
                        TextEntry::make('branch.name')
                            ->label('Branch')
                            ->icon('heroicon-o-building-office'),
                        
                        TextEntry::make('incident_type')
                            ->label('Incident Type')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-tag'),
                        
                        TextEntry::make('incident_date')
                            ->label('Incident Date & Time')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar'),
                        
                        TextEntry::make('location')
                            ->label('Location')
                            ->placeholder('Not specified')
                            ->icon('heroicon-o-map-pin'),
                    ])
                    ->columns(3),
                
                Section::make('Classification')
                    ->schema([
                        TextEntry::make('severity')
                            ->label('Severity')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                'low' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        
                        TextEntry::make('priority')
                            ->label('Priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                'low' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        
                        TextEntry::make('status')
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
                            }),
                    ])
                    ->columns(3),
                
                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ]),
                
                Section::make('Response & Actions')
                    ->schema([
                        TextEntry::make('action_taken')
                            ->label('Action Taken')
                            ->columnSpanFull()
                            ->placeholder('No actions documented'),
                        
                        TextEntry::make('witnesses')
                            ->label('Witnesses')
                            ->columnSpanFull()
                            ->placeholder('No witnesses documented')
                            ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : null),
                        
                        TextEntry::make('assignedTo.name')
                            ->label('Assigned To')
                            ->placeholder('Unassigned')
                            ->icon('heroicon-o-user-circle'),
                        
                        TextEntry::make('reportedBy.name')
                            ->label('Reported By')
                            ->icon('heroicon-o-user-circle'),
                    ])
                    ->columns(2),
                
                Section::make('Follow-up & Resolution')
                    ->schema([
                        TextEntry::make('follow_up')
                            ->label('Follow-up Actions')
                            ->columnSpanFull()
                            ->placeholder('No follow-up actions documented')
                            ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : null),
                        
                        TextEntry::make('resolvedBy.name')
                            ->label('Resolved By')
                            ->placeholder('Not resolved yet')
                            ->icon('heroicon-o-check-circle')
                            ->visible(fn ($record) => $record->isResolved() || $record->isClosed()),
                        
                        TextEntry::make('resolved_at')
                            ->label('Resolved At')
                            ->dateTime('M j, Y g:i A')
                            ->placeholder('Not resolved yet')
                            ->icon('heroicon-o-clock')
                            ->visible(fn ($record) => $record->isResolved() || $record->isClosed()),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record->isResolved() && !$record->isClosed()),
                
                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock'),
                        
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                
                Section::make('Attachments')
                    ->schema([
                        TextEntry::make('attachments_count')
                            ->label('Number of Attachments')
                            ->getStateUsing(fn ($record) => $record->attachments()->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}

