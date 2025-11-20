<?php

namespace App\Filament\Resources\PharmacySupplierResource\Pages;

use App\Filament\Resources\PharmacySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPharmacySuppliers extends ListRecords
{
    protected static string $resource = PharmacySupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
