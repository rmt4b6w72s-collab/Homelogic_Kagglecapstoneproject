<?php

namespace App\Filament\Resources\MedicationDeliveryResource\Pages;

use App\Filament\Resources\MedicationDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedicationDelivery extends EditRecord
{
    protected static string $resource = MedicationDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
