<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Resident;
use App\Models\Branch;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\MaxWidth;

class AppointmentHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Appointment History';
    protected static ?string $title = 'Appointment History';
    protected static ?int $navigationSort = 35;
    protected static ?string $navigationGroup = 'Resident Care';
    protected static string $view = 'filament.pages.appointment-history';

    public ?int $selectedBranchId = null;
    public ?int $selectedResidentId = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermission('view_appointments');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->with(['resident', 'branch', 'appointmentType', 'healthcareProvider', 'createdBy'])
                    ->orderBy('appointment_date', 'desc')
            )
            ->columns([
                TextColumn::make('resident.name')
                    ->label('Resident Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user')
                    ->iconColor('primary'),

                TextColumn::make('appointment_date')
                    ->label('Date Taken')
                    ->date('F j, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('success'),

                TextColumn::make('appointment_time')
                    ->label('Time')
                    ->time('g:i A')
                    ->sortable()
                    ->placeholder('Not specified')
                    ->icon('heroicon-o-clock')
                    ->iconColor('warning'),

                TextColumn::make('appointmentType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($record) => $record->appointmentType?->color_code ?? 'gray')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Location')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in-house' => 'success',
                        'external' => 'info',
                        'telehealth' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in-house' => 'In-House',
                        'external' => 'External',
                        'telehealth' => 'Telehealth',
                        default => ucfirst($state ?? 'N/A'),
                    }),

                TextColumn::make('provider_name')
                    ->label('Provider')
                    ->searchable()
                    ->placeholder('Not specified')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->provider_name),

                TextColumn::make('description')
                    ->label('Other Details')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description)
                    ->placeholder('No details'),

                TextColumn::make('next_appointment_date')
                    ->label('Next Appointment Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('None scheduled')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->iconColor('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'confirmed' => 'info',
                        'scheduled' => 'warning',
                        'cancelled' => 'danger',
                        'rescheduled' => 'gray',
                        'in_progress' => 'primary',
                        'no_show' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                        'rescheduled' => 'Rescheduled',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Filter by Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Branches')
                    ->native(false),

                SelectFilter::make('resident_id')
                    ->label('Filter by Resident')
                    ->relationship('resident', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Residents')
                    ->native(false),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                    ->modalWidth(MaxWidth::FourExtraLarge),
                    
                EditAction::make()
                    ->label('Edit')
                    ->color('warning')
                    ->icon('heroicon-o-pencil'),
            ])
            ->emptyStateHeading('No Appointments Found')
            ->emptyStateDescription('Select a branch and resident to view their appointment history.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->striped()
            ->defaultSort('appointment_date', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s');
    }
}
