<?php

namespace App\Filament\Resources\VitalRangeResource\Pages;

use App\Filament\Resources\VitalRangeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVitalRange extends CreateRecord
{
    protected static string $resource = VitalRangeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert empty strings to null for nullable fields
        $nullableFields = ['min_normal', 'max_normal', 'min_warning', 'max_warning', 'min_critical', 'max_critical', 'unit', 'description'];
        
        foreach ($nullableFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Convert numeric strings to floats
        $numericFields = ['min_normal', 'max_normal', 'min_warning', 'max_warning', 'min_critical', 'max_critical'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = (float) $data[$field];
            }
        }

        // Ensure is_active is boolean
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        } else {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
        } catch (\Exception $e) {
            \Log::error('VitalRange creation failed: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
