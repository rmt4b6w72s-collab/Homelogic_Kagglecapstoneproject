<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Filament\Resources\FacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFacility extends CreateRecord
{
    protected static string $resource = FacilityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove enabled_modules from data as it's not a database field
        unset($data['enabled_modules']);
        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync modules after facility is created
        $enabledModules = $this->form->getState()['enabled_modules'] ?? [];
        $allModules = array_keys(\App\Constants\Modules::all());
        
        foreach ($allModules as $module) {
            if (in_array($module, $enabledModules)) {
                $this->record->enableModule($module);
            } else {
                $this->record->disableModule($module);
            }
        }
    }

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();
        
        // Add confirmation to the create/save button
        foreach ($actions as $action) {
            if ($action instanceof Actions\CreateAction || $action->getName() === 'create') {
                $action->requiresConfirmation()
                    ->modalHeading('Create Facility')
                    ->modalDescription('Are you sure you want to create this facility?')
                    ->modalSubmitActionLabel('Yes, Create');
                break;
            }
        }
        
        return $actions;
    }
}
