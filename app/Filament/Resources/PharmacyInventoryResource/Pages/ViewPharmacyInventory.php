<?php

namespace App\Filament\Resources\PharmacyInventoryResource\Pages;

use App\Filament\Resources\PharmacyInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPharmacyInventory extends ViewRecord
{
    protected static string $resource = PharmacyInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
