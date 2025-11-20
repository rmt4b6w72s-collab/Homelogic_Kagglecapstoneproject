<?php

namespace App\Filament\Resources\PharmacyInventoryResource\Pages;

use App\Filament\Resources\PharmacyInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPharmacyInventory extends EditRecord
{
    protected static string $resource = PharmacyInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
