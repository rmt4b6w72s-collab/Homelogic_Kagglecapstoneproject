<?php

namespace App\Filament\Resources\PharmacyOrderResource\Pages;

use App\Filament\Resources\PharmacyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPharmacyOrders extends ListRecords
{
    protected static string $resource = PharmacyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
