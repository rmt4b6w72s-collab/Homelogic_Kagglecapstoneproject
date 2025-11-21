<?php

namespace App\Filament\Resources\ResidentDocumentResource\Pages;

use App\Filament\Resources\ResidentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResidentDocuments extends ListRecords
{
    protected static string $resource = ResidentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
