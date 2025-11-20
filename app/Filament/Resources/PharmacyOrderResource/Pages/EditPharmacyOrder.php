<?php

namespace App\Filament\Resources\PharmacyOrderResource\Pages;

use App\Filament\Resources\PharmacyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPharmacyOrder extends EditRecord
{
    protected static string $resource = PharmacyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
