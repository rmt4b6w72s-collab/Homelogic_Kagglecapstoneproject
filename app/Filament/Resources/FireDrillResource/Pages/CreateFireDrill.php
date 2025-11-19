<?php

namespace App\Filament\Resources\FireDrillResource\Pages;

use App\Filament\Resources\FireDrillResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateFireDrill extends CreateRecord
{
    protected static string $resource = FireDrillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
