<?php

namespace App\Filament\Resources\GroceryStatusUpdateResource\Pages;

use App\Filament\Resources\GroceryStatusUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CreateGroceryStatusUpdate extends CreateRecord
{
    protected static string $resource = GroceryStatusUpdateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
        // Ensure week_start_date is Monday
        if (isset($data['week_start_date'])) {
            $date = Carbon::parse($data['week_start_date']);
            $data['week_start_date'] = $date->startOfWeek(Carbon::MONDAY)->toDateString();
        } else {
            // Default to current Monday
            $data['week_start_date'] = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        }
        
        return $data;
    }
}
