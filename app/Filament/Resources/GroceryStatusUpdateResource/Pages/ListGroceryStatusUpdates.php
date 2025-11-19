<?php

namespace App\Filament\Resources\GroceryStatusUpdateResource\Pages;

use App\Filament\Resources\GroceryStatusUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroceryStatusUpdates extends ListRecords
{
    protected static string $resource = GroceryStatusUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
