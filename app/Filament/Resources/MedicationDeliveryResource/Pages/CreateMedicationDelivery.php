<?php

namespace App\Filament\Resources\MedicationDeliveryResource\Pages;

use App\Filament\Resources\MedicationDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMedicationDelivery extends CreateRecord
{
    protected static string $resource = MedicationDeliveryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['received_by'] = Auth::id();
        return $data;
    }
}
