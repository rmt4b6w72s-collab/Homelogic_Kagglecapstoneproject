<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Facility;
use App\Models\FacilityRegistration;
use App\Models\User;

class SuperAdminStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Facilities', Facility::count())
                ->description('All registered facilities')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary')
                ->url(route('filament.admin.resources.facilities.index')),
            
            Stat::make('Pending Registrations', FacilityRegistration::where('status', 'pending')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.facility-registrations.index', ['tableFilters[status][value]' => 'pending'])),
            
            Stat::make('Active Facilities', Facility::where('is_active', true)->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Total System Users', User::where('role', '!=', 'super_admin')->count())
                ->description('All facility users')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
        ];
    }
}
