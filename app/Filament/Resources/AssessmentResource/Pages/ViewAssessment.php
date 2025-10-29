<?php

namespace App\Filament\Resources\AssessmentResource\Pages;

use App\Filament\Resources\AssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ViewAssessment extends ViewRecord
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('complete')
                ->label('Complete Assessment')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->url(fn (): string => '/admin/assessment-form?assessment=' . $this->record->id)
                ->visible(fn (): bool => $this->record->status !== 'approved'),
            Actions\EditAction::make()
                ->label('Edit Assessment')
                ->color('primary'),
            Actions\DeleteAction::make()
                ->label('Delete Assessment')
                ->color('danger'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Assessment Information')
                    ->schema([
                        Split::make([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('resident.name')
                                        ->label('Resident')
                                        ->size('lg')
                                        ->weight('bold'),

                                    TextEntry::make('branch.name')
                                        ->label('Branch')
                                        ->size('lg'),

                                    TextEntry::make('assessor.name')
                                        ->label('Assessor')
                                        ->size('lg'),

                                    TextEntry::make('assessment_type')
                                        ->label('Assessment Type')
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'initial' => 'Initial Assessment',
                                            'periodic' => 'Periodic Assessment',
                                            'focused' => 'Focused Assessment',
                                            'discharge' => 'Discharge Assessment',
                                            default => ucfirst($state),
                                        })
                                        ->size('lg'),

                                    TextEntry::make('assessment_date')
                                        ->label('Assessment Date')
                                        ->date('F j, Y')
                                        ->size('lg'),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'draft' => 'gray',
                                            'submitted' => 'warning',
                                            'reviewed' => 'info',
                                            'approved' => 'success',
                                            'archived' => 'danger',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                                ]),
                        ]),
                    ]),

                Section::make('Assessment Content')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Assessment Notes')
                            ->columnSpanFull()
                            ->markdown(),

                        TextEntry::make('scores')
                            ->label('Section Scores')
                            ->columnSpanFull()
                            ->markdown(),

                        TextEntry::make('recommendations')
                            ->label('Care Plan Recommendations')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),

                Section::make('Timeline')
                    ->schema([
                        Split::make([
                            TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime('F j, Y g:i A')
                                ->icon('heroicon-o-calendar'),

                            TextEntry::make('completed_at')
                                ->label('Completed')
                                ->dateTime('F j, Y g:i A')
                                ->icon('heroicon-o-check-circle')
                                ->placeholder('Not completed'),

                            TextEntry::make('reviewed_at')
                                ->label('Reviewed')
                                ->dateTime('F j, Y g:i A')
                                ->icon('heroicon-o-eye')
                                ->placeholder('Not reviewed'),

                            TextEntry::make('approved_at')
                                ->label('Approved')
                                ->dateTime('F j, Y g:i A')
                                ->icon('heroicon-o-check-badge')
                                ->placeholder('Not approved'),
                        ]),
                    ]),

                Section::make('Progress')
                    ->schema([
                        TextEntry::make('completion_percentage')
                            ->label('Completion Percentage')
                            ->formatStateUsing(fn ($record): string => $record->completion_percentage . '%')
                            ->color(fn ($record): string => match (true) {
                                $record->completion_percentage >= 100 => 'success',
                                $record->completion_percentage >= 75 => 'warning',
                                default => 'danger',
                            })
                            ->icon('heroicon-o-chart-bar'),

                        IconEntry::make('is_completed')
                            ->label('Is Completed')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(2),
            ]);
    }
}
