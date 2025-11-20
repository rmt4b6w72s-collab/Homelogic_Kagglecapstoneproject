<?php

namespace App\Filament\Resources\PharmacyOrderResource\Pages;

use App\Filament\Resources\PharmacyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPharmacyOrder extends ViewRecord
{
    protected static string $resource = PharmacyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
