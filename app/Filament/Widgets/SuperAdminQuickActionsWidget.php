<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SuperAdminQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.super-admin-quick-actions';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 5;
}

