<?php

namespace App\Filament\Resources\FacilityRegistrationResource\Pages;

use App\Filament\Resources\FacilityRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacilityRegistrations extends ListRecords
{
    protected static string $resource = FacilityRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
