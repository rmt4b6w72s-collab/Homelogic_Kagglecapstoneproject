<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\SuperAdminStatsWidget;

class SuperAdminDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $title = 'Super Admin Dashboard';
    protected static ?string $navigationLabel = 'Super Admin';
    protected static ?int $navigationSort = -999;
    protected static ?string $navigationGroup = 'Super Admin';
    protected static string $routePath = 'super-admin-dashboard';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return Auth::check() && $user->role === 'super_admin';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hidden from navigation, accessed via Dashboard redirect
    }

    public function getWidgets(): array
    {
        return [
            SuperAdminStatsWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
