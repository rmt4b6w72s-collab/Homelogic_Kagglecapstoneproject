<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SuperAdminHeroWidget extends Widget
{
    protected static string $view = 'filament.widgets.super-admin-hero';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
}

