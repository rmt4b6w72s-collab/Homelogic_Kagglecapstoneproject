<?php

namespace App\Filament\Resources\FireDrillResource\Pages;

use App\Filament\Resources\FireDrillResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFireDrills extends ListRecords
{
    protected static string $resource = FireDrillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
