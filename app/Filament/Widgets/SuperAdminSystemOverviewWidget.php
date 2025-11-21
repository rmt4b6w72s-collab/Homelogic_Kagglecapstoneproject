<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Facility;
use App\Models\Branch;
use App\Models\Resident;
use App\Models\User;
use App\Models\Appointment;

class SuperAdminSystemOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalFacilities = Facility::count();
        $totalBranches = Branch::count();
        $totalResidents = Resident::count();
        $totalUsers = User::where('role', '!=', 'super_admin')->count();
        $activeResidents = Resident::where('is_active', true)->count();
        $activeUsers = User::where('role', '!=', 'super_admin')->where('is_active', true)->count();
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $upcomingAppointments = Appointment::whereDate('appointment_date', '>=', today())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();

        return [
            Stat::make('Total Facilities', $totalFacilities)
                ->description('Across all organizations')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary')
                ->chart($this->getFacilityChartData())
                ->url(route('filament.admin.resources.facilities.index')),
            
            Stat::make('Total Branches', $totalBranches)
                ->description('Care locations')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('info')
                ->chart($this->getBranchChartData()),
            
            Stat::make('Total Residents', $totalResidents)
                ->description($activeResidents . ' active')
                ->descriptionIcon('heroicon-o-users')
                ->color('success')
                ->chart($this->getResidentChartData())
                ->url(route('filament.admin.resources.residents.index')),
            
            Stat::make('System Users', $totalUsers)
                ->description($activeUsers . ' active')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('warning')
                ->chart($this->getUserChartData())
                ->url(route('filament.admin.resources.users.index')),
            
            Stat::make('Today\'s Appointments', $todayAppointments)
                ->description($upcomingAppointments . ' upcoming')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('danger')
                ->chart($this->getAppointmentChartData())
                ->url(route('filament.admin.resources.appointments.index')),
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

    private function getResidentChartData(): array
    {
        try {
            return Resident::selectRaw('DATE(created_at) as date, COUNT(*) as count')
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

    private function getAppointmentChartData(): array
    {
        try {
            return Appointment::selectRaw('DATE(appointment_date) as date, COUNT(*) as count')
                ->whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }
}

