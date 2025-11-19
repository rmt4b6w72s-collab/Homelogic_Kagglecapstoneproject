<?php

namespace App\Filament\Resources\GroceryStatusUpdateResource\Pages;

use App\Filament\Resources\GroceryStatusUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroceryStatusUpdate extends EditRecord
{
    protected static string $resource = GroceryStatusUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
