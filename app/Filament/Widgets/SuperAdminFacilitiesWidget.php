<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Builder;

class SuperAdminFacilitiesWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Facilities Overview';
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Facility::withoutGlobalScopes()
                    ->withCount([
                        'branches',
                        'users',
                        'users as active_users_count' => function (Builder $query) {
                            $query->where('is_active', true);
                        },
                    ])
                    ->with(['branches' => function ($query) {
                        $query->withoutGlobalScopes()->withCount('residents');
                    }])
                    ->latest()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Facility Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-office')
                    ->iconColor('primary'),
                
                TextColumn::make('branches_count')
                    ->label('Branches')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                
                TextColumn::make('total_residents')
                    ->label('Residents')
                    ->getStateUsing(function (Facility $record) {
                        return $record->branches->sum(function ($branch) {
                            return $branch->residents_count ?? 0;
                        });
                    })
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                TextColumn::make('users_count')
                    ->label('Total Users')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                
                TextColumn::make('active_users_count')
                    ->label('Active Users')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Facility $record): string => route('filament.admin.resources.facilities.edit', $record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Facilities')
            ->emptyStateDescription('Facilities will appear here once registered.')
            ->emptyStateIcon('heroicon-o-building-office');
    }
}

