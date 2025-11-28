<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use App\Models\IncidentAttachment;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();
        
        // Add confirmation to the create/save button
        foreach ($actions as $action) {
            if ($action instanceof Actions\CreateAction || $action->getName() === 'create') {
                $action->requiresConfirmation()
                    ->modalHeading('Create Incident')
                    ->modalDescription('Are you sure you want to create this incident?')
                    ->modalSubmitActionLabel('Yes, Create');
                break;
            }
        }
        
        return $actions;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $incident = $this->record;

        // Handle attachment uploads if any
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachmentData) {
                if (isset($attachmentData['file_path']) && $attachmentData['file_path']) {
                    $file = $attachmentData['file_path'];
                    
                    // Handle TemporaryUploadedFile
                    if ($file instanceof TemporaryUploadedFile) {
                        $storedPath = $file->store('incident-attachments', 'public');
                        $fileName = $file->getClientOriginalName();
                        $fileSize = $file->getSize();
                        $mimeType = $file->getMimeType();
                    } else {
                        // Handle already stored file path
                        $storedPath = $file;
                        $fileName = $attachmentData['file_name'] ?? basename($file);
                        $fileSize = Storage::disk('public')->size($file);
                        $mimeType = Storage::disk('public')->mimeType($file);
                    }

                    IncidentAttachment::create([
                        'incident_id' => $incident->id,
                        'file_path' => $storedPath,
                        'file_name' => $fileName,
                        'file_type' => $attachmentData['file_type'] ?? 'photo',
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'uploaded_by' => auth()->id(),
                        'description' => $attachmentData['description'] ?? null,
                    ]);
                }
            }
        }
    }
}
