<?php

namespace App\Filament\Resources\ResidentDocumentResource\Pages;

use App\Filament\Resources\ResidentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateResidentDocument extends CreateRecord
{
    protected static string $resource = ResidentDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        return $data;
    }
}
