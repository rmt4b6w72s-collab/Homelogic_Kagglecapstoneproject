<?php

namespace App\Filament\Resources\MedicationDeliveryResource\Pages;

use App\Filament\Resources\MedicationDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicationDeliveries extends ListRecords
{
    protected static string $resource = MedicationDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
