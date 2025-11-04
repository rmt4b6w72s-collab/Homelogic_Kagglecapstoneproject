<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Models\Appointment;

class UpcomingAppointmentsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Upcoming Appointments';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::with(['resident', 'healthcareProvider'])
                    ->whereDate('appointment_date', '>=', today())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->orderBy('appointment_date')
                    ->orderBy('appointment_time')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('resident.name')
                    ->label('Resident')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'medical' => 'danger',
                        'dental' => 'info',
                        'therapy' => 'success',
                        'specialist' => 'warning',
                        default => 'gray',
                    }),
                
                TextColumn::make('healthcareProvider.name')
                    ->label('Provider')
                    ->searchable(),
                
                TextColumn::make('appointment_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Appointment $record): string => route('filament.admin.resources.appointments.edit', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}








