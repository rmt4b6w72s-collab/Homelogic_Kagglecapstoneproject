<?php

namespace App\Filament\Resources\PharmacySupplierResource\Pages;

use App\Filament\Resources\PharmacySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePharmacySupplier extends CreateRecord
{
    protected static string $resource = PharmacySupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }
}
