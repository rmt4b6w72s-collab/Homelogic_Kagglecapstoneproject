<?php

namespace App\Filament\Resources\PharmacyInventoryResource\Pages;

use App\Filament\Resources\PharmacyInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPharmacyInventories extends ListRecords
{
    protected static string $resource = PharmacyInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
