<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Navigation\CustomNavigationProvider;
use App\Models\Facility;
use Illuminate\Support\Facades\Auth;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(fn () => $this->getDynamicColors())
            ->font('Inter')
            ->darkMode()
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\HeroSectionWidget::class,
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\QuickActionsWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigation(CustomNavigationProvider::class)
            // Removed navigationGroups to prevent auto-discovery from creating groups
            ->topNavigation()
            ->brandName(fn () => $this->getDynamicBranding()['name'])
            ->brandLogo(fn () => $this->getDynamicBranding()['logo'])
            ->brandLogoHeight('2.5rem')
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Dashboard' => 'Dashboard',
                'Resident Care' => 'Resident Care',
                'Reports & Analytics' => 'Reports & Analytics',
                'Administration' => 'Administration',
            ])
            ->userMenuItems([]) // Disable default user menu items
            ->renderHook(
                'panels::topbar.end',
                fn (): string => view('filament.components.user-menu'),
            )
            ->renderHook(
                'panels::topbar.start',
                fn (): string => view('filament.components.dynamic-branding')->render() . '
                    <link rel="stylesheet" href="' . asset('css/custom-enhancements.css') . '">
                    <style>
                        /* Hide Filament default user menu component */
                        [data-filament-name="account-widget"],
                        [wire\\:key*="account-widget"],
                        .fi-account-menu,
                        [x-data*="accountMenu"],
                        .fi-topbar-actions > button:last-child,
                        .fi-topbar-actions > div:last-child button {
                            display: none !important;
                        }
                    </style>
                ',
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetFacilityContext::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Get the current facility for branding
     */
    private function getCurrentFacility(): ?Facility
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Super admins always use default branding (HomeLogic360), never facility branding
        if ($user->role === 'super_admin') {
            return null;
        }

        // Try to get from app container (set by middleware)
        if (app()->bound('facility')) {
            return app('facility');
        }

        // Fallback to user's facility
        if ($user->facility_id) {
            return Facility::find($user->facility_id);
        }

        return null;
    }


    /**
     * Get dynamic branding configuration (evaluated per request)
     */
    private function getDynamicBranding(): array
    {
        $facility = $this->getCurrentFacility();
        return $this->getBranding($facility);
    }

    /**
     * Get dynamic colors (evaluated per request)
     */
    private function getDynamicColors(): array
    {
        $branding = $this->getDynamicBranding();
        return [
            'primary' => $branding['primary_color'],
            'success' => $branding['secondary_color'] ?? '#86EFAC', // Light green from logo
            'warning' => $branding['primary_color'], // Use primary for warnings
            'danger' => $branding['primary_color'], // Use primary for danger
            'info' => $branding['primary_color'], // Use primary for info
            'gray' => Color::Slate,
        ];
    }

    /**
     * Get branding configuration
     */
    private function getBranding(?Facility $facility): array
    {
        if (!$facility) {
            // Default branding for super admin / HomeLogic360
            return [
                'name' => 'HomeLogic360',
                'logo' => asset('images/logonew.png'),
                'primary_color' => '#1E3A5F', // Dark blue from logo
                'secondary_color' => '#86EFAC', // Light green from logo
            ];
        }

        // Use the branding accessor which handles logo_url properly
        $branding = $facility->branding;

        return [
            'name' => $branding['name'] ?? $facility->name,
            'logo' => $branding['logo'] ?? asset('images/logonew.png'),
            'primary_color' => $this->parseColor($branding['primary_color'] ?? $facility->primary_color ?? '#667eea'),
            'secondary_color' => $this->parseColor($branding['secondary_color'] ?? $facility->secondary_color ?? '#86EFAC'),
        ];
    }

    /**
     * Parse color string to Color object or hex
     */
    private function parseColor(?string $color): string
    {
        if (!$color) {
            return Color::Sky;
        }

        // If it's a valid hex color, return it
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }

        // Try to match Filament color names
        $colorMap = [
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'emerald' => Color::Emerald,
            'amber' => Color::Amber,
            'red' => Color::Red,
        ];

        $lowerColor = strtolower($color);
        return $colorMap[$lowerColor] ?? Color::Sky;
    }
}
