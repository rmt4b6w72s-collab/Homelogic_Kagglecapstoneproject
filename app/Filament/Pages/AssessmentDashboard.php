<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AssessmentDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Assessments';
    protected static ?string $title = 'Assessments';
    protected static ?int $navigationSort = 40;
    protected static ?string $navigationGroup = null;
    protected static string $view = 'filament.pages.assessments';
    protected static string $routePath = 'assessments';

    public static function canAccess(): bool
    {
        return Auth::check() && (
            Auth::user()->hasRole('administrator') || 
            Auth::user()->hasRole('super_admin')
        );
    }

    public function mount(): void
    {
        // Redirect to Assessments list page by default
        $this->redirect(route('filament.admin.resources.assessments.index'));
    }

    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('start_assessment')
                ->label('Start Assessment')
                ->icon('heroicon-o-plus')
                ->url(route('filament.admin.pages.assessment-page'))
                ->color('primary')
                ->size('lg'),
            
            \Filament\Actions\Action::make('complete_assessment')
                ->label('Complete Assessment')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(route('filament.admin.pages.assessment-form'))
                ->color('success')
                ->size('lg'),
        ];
    }
}
