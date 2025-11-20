<?php

namespace App\Filament\Resources\PharmacyOrderResource\Pages;

use App\Filament\Resources\PharmacyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePharmacyOrder extends CreateRecord
{
    protected static string $resource = PharmacyOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['ordered_by'] = auth()->id();
        $data['order_date'] = $data['order_date'] ?? now();
        
        return $data;
    }
}
