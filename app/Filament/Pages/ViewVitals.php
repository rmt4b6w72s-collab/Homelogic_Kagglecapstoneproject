<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use App\Models\VitalSign;

class ViewVitals extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Vitals';
    protected static ?string $navigationGroup = null;
    protected static bool $shouldRegisterNavigation = true;

    protected static string $view = 'filament.pages.view-vitals';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(VitalSign::query()->with('resident'))
            ->columns([
                TextColumn::make('resident.name')
                    ->label('Resident')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('measurement_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('systolic')
                    ->label('BP (Systolic)')
                    ->numeric(),
                TextColumn::make('diastolic')
                    ->label('BP (Diastolic)')
                    ->numeric(),
                TextColumn::make('pulse')
                    ->label('Pulse')
                    ->numeric(),
                TextColumn::make('temperature')
                    ->label('Temp (°F)')
                    ->numeric(),
            ])
            ->defaultSort('measurement_date', 'desc');
    }
}
