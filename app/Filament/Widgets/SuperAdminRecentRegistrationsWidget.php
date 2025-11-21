<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use App\Models\FacilityRegistration;

class SuperAdminRecentRegistrationsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Recent Facility Registrations';
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FacilityRegistration::with('approvedBy')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('facility_name')
                    ->label('Facility Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-office')
                    ->iconColor('primary'),
                
                TextColumn::make('contact_name')
                    ->label('Contact Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                
                TextColumn::make('email')
                    ->label('Contact Email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->copyable(),
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                    ])
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (FacilityRegistration $record): string => route('filament.admin.resources.facility-registrations.edit', $record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Registrations')
            ->emptyStateDescription('New facility registration requests will appear here.')
            ->emptyStateIcon('heroicon-o-document-plus');
    }
}

