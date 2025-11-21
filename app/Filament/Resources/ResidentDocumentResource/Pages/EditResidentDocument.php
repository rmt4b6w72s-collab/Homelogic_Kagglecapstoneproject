<?php

namespace App\Filament\Resources\ResidentDocumentResource\Pages;

use App\Filament\Resources\ResidentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidentDocument extends EditRecord
{
    protected static string $resource = ResidentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
