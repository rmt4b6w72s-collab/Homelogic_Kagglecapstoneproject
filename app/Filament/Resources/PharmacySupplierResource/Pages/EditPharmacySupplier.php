<?php

namespace App\Filament\Resources\PharmacySupplierResource\Pages;

use App\Filament\Resources\PharmacySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPharmacySupplier extends EditRecord
{
    protected static string $resource = PharmacySupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
