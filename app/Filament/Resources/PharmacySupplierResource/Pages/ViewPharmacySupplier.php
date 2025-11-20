<?php

namespace App\Filament\Resources\PharmacySupplierResource\Pages;

use App\Filament\Resources\PharmacySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPharmacySupplier extends ViewRecord
{
    protected static string $resource = PharmacySupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
