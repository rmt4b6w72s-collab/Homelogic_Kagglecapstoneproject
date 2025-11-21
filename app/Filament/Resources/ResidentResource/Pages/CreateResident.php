<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\ResidentDocument;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateResident extends CreateRecord
{
    protected static string $resource = ResidentResource::class;

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();
        
        // Add confirmation to the create/save button
        foreach ($actions as $action) {
            if ($action instanceof Actions\CreateAction || $action->getName() === 'create') {
                $action->requiresConfirmation()
                    ->modalHeading('Create Resident')
                    ->modalDescription('Are you sure you want to create this resident?')
                    ->modalSubmitActionLabel('Yes, Create');
                break;
            }
        }
        
        return $actions;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $resident = $this->record;

        // Handle document uploads if any
        if (isset($data['documents']) && is_array($data['documents'])) {
            foreach ($data['documents'] as $documentData) {
                if (isset($documentData['file_path']) && $documentData['file_path']) {
                    $file = $documentData['file_path'];
                    
                    // Handle TemporaryUploadedFile
                    if ($file instanceof TemporaryUploadedFile) {
                        $storedPath = $file->store('resident-documents', 'public');
                        $fileName = $file->getClientOriginalName();
                        $fileSize = $file->getSize();
                        $mimeType = $file->getMimeType();
                    } else {
                        // Handle already stored file path
                        $storedPath = $file;
                        $fileName = $documentData['file_name'] ?? basename($file);
                        $fileSize = Storage::disk('public')->size($file);
                        $mimeType = Storage::disk('public')->mimeType($file);
                    }

                    ResidentDocument::create([
                        'resident_id' => $resident->id,
                        'document_name' => $documentData['document_name'],
                        'document_type' => $documentData['document_type'],
                        'file_path' => $storedPath,
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'uploaded_by' => auth()->id(),
                        'notes' => $documentData['notes'] ?? null,
                    ]);
                }
            }
        }
    }
}
