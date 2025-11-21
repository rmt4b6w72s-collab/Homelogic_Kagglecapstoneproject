<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Filament\Resources\FacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacility extends EditRecord
{
    protected static string $resource = FacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove enabled_modules from data as it's not a database field
        unset($data['enabled_modules']);
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync modules after facility is updated
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
        
        // Add confirmation to the save button
        foreach ($actions as $action) {
            if ($action instanceof Actions\SaveAction || $action->getName() === 'save') {
                $action->requiresConfirmation()
                    ->modalHeading('Save Facility')
                    ->modalDescription('Are you sure you want to save your changes?')
                    ->modalSubmitActionLabel('Yes, Save');
                break;
            }
        }
        
        return $actions;
    }
}
