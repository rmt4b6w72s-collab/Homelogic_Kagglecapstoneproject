<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Facility;
use App\Models\FacilityRegistration;
use App\Models\User;
use App\Models\Branch;

class SuperAdminStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalFacilities = Facility::count();
        $activeFacilities = Facility::where('is_active', true)->count();
        $pendingRegistrations = FacilityRegistration::where('status', 'pending')->count();
        $totalUsers = User::where('role', '!=', 'super_admin')->count();
        $activeUsers = User::where('role', '!=', 'super_admin')->where('is_active', true)->count();
        $totalBranches = Branch::count();
        $activeBranches = Branch::where('is_active', true)->count();

        return [
            Stat::make('Total Facilities', $totalFacilities)
                ->description($activeFacilities . ' active')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary')
                ->chart($this->getFacilityChartData())
                ->url(route('filament.admin.resources.facilities.index')),
            
            Stat::make('Pending Registrations', $pendingRegistrations)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success')
                ->chart($this->getRegistrationChartData())
                ->url(route('filament.admin.resources.facility-registrations.index', ['tableFilters[status][value]' => 'pending'])),
            
            Stat::make('Total Branches', $totalBranches)
                ->description($activeBranches . ' active')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('primary')
                ->chart($this->getBranchChartData()),
            
            Stat::make('System Users', $totalUsers)
                ->description($activeUsers . ' active')
                ->descriptionIcon('heroicon-o-users')
                ->color('success')
                ->chart($this->getUserChartData())
                ->url(route('filament.admin.resources.users.index')),
        ];
    }

    private function getFacilityChartData(): array
    {
        try {
            return Facility::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    private function getRegistrationChartData(): array
    {
        try {
            return FacilityRegistration::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    private function getBranchChartData(): array
    {
        try {
            return Branch::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    private function getUserChartData(): array
    {
        try {
            return User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('role', '!=', 'super_admin')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }
}
