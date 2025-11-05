<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Models\VitalSign;
use App\Models\Resident;
use App\Filament\Widgets\SimpleVitalsStatsWidget;
use Carbon\Carbon;

class VitalsHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'Vitals History';
    protected static ?string $title = 'Vitals History';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.vitals-history';

    public ?int $selectedResident = null;

    public function mount(): void
    {
        // Set default resident if provided in URL
        if (request()->has('resident')) {
            $this->selectedResident = request('resident');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                VitalSign::query()
                    ->with(['resident', 'takenBy'])
                    ->when($this->selectedResident, function (Builder $query) {
                        $query->where('resident_id', $this->selectedResident);
                    })
                    ->when(auth()->user()->hasRole('caregiver'), function (Builder $query) {
                        $query->whereHas('resident', function ($q) {
                            $q->where('branch_id', auth()->user()->assigned_branch_id);
                        });
                    })
                    ->orderBy('measurement_date', 'desc')
            )
            ->columns([
                TextColumn::make('resident.name')
                    ->label('Resident')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user')
                    ->iconColor('primary'),
                
                TextColumn::make('measurement_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('gray')
                    ->description(fn ($record) => $record->measurement_date->diffForHumans()),
                
                TextColumn::make('blood_pressure')
                    ->label('Blood Pressure')
                    ->formatStateUsing(fn ($record) => $record->blood_pressure ?? 'Not recorded')
                    ->searchable()
                    ->icon('heroicon-o-heart')
                    ->iconColor('danger')
                    ->badge()
                    ->color(fn ($record) => $record->checkBloodPressureRange() === 'critical' ? 'danger' : 
                                           ($record->checkBloodPressureRange() === 'warning' ? 'warning' : 'success')),
                
                TextColumn::make('temperature')
                    ->label('Temperature')
                    ->formatStateUsing(fn ($record) => $record->formatted_temperature ?? 'Not recorded')
                    ->searchable()
                    ->icon('heroicon-o-fire')
                    ->iconColor('orange')
                    ->badge()
                    ->color(fn ($record) => $record->checkTemperatureRange() === 'critical' ? 'danger' : 
                                           ($record->checkTemperatureRange() === 'warning' ? 'warning' : 'success')),
                
                TextColumn::make('pulse')
                    ->label('Pulse')
                    ->formatStateUsing(fn ($record) => $record->formatted_pulse ?? 'Not recorded')
                    ->searchable()
                    ->icon('heroicon-o-heart')
                    ->iconColor('red')
                    ->badge()
                    ->color(fn ($record) => $record->checkPulseRange() === 'critical' ? 'danger' : 
                                           ($record->checkPulseRange() === 'warning' ? 'warning' : 'success')),
                
                TextColumn::make('oxygen_saturation')
                    ->label('Oxygen')
                    ->formatStateUsing(fn ($record) => $record->formatted_oxygen_saturation ?? 'Not recorded')
                    ->searchable()
                    ->icon('heroicon-o-wind')
                    ->iconColor('blue')
                    ->badge()
                    ->color(fn ($record) => $record->checkOxygenSaturationRange() === 'critical' ? 'danger' : 
                                           ($record->checkOxygenSaturationRange() === 'warning' ? 'warning' : 'success')),
                
                TextColumn::make('pain_level')
                    ->label('Pain Level')
                    ->formatStateUsing(fn ($record) => $record->pain_level ? $record->pain_level . '/10' : 'Not recorded')
                    ->searchable()
                    ->icon('heroicon-o-face-frown')
                    ->iconColor('gray')
                    ->badge()
                    ->color(fn ($record) => $record->pain_level > 7 ? 'danger' : 
                                           ($record->pain_level > 4 ? 'warning' : 'success')),
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'approved',
                        'warning' => 'pending_review',
                        'danger' => 'critical',
                        'gray' => 'declined',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'approved' => 'Approved',
                        'pending_review' => 'Pending Review',
                        'critical' => 'Critical',
                        'declined' => 'Declined',
                        default => ucfirst($state),
                    })
                    ->icons([
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-clock' => 'pending_review',
                        'heroicon-o-exclamation-triangle' => 'critical',
                        'heroicon-o-x-circle' => 'declined',
                    ]),
                
                TextColumn::make('takenBy.name')
                    ->label('Taken By')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary')
                    ->placeholder('Unknown'),
                
                TextColumn::make('notes')
                    ->label('Notes')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('No notes')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('gray'),
            ])
            ->filters([
                SelectFilter::make('resident_id')
                    ->label('Resident')
                    ->options(function () {
                        $query = Resident::query();
                        
                        if (auth()->user()->hasRole('caregiver')) {
                            $query->where('branch_id', auth()->user()->assigned_branch_id);
                        }
                        
                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'approved' => 'Approved',
                        'pending_review' => 'Pending Review',
                        'critical' => 'Critical',
                        'declined' => 'Declined',
                    ]),
                
                SelectFilter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->default(now()->subDays(30)),
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('measurement_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('measurement_date', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('measurement_date', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    protected function getViewData(): array
    {
        $resident = null;
        if ($this->selectedResident) {
            $resident = Resident::find($this->selectedResident);
        }

        return [
            'resident' => $resident,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SimpleVitalsStatsWidget::class,
        ];
    }
}
