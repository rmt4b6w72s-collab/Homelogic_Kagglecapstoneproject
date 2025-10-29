<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        // Automatically assign administrator role if user role is 'admin' or 'administrator'
        $user = $this->record;
        $role = $user->role ?? null;
        
        if (in_array($role, ['admin', 'administrator'])) {
            $adminRole = Role::where('name', 'administrator')->first();
            if ($adminRole) {
                if (!$user->hasRole('administrator')) {
                    $user->assignRole('administrator');
                }
            }
        } else {
            // Remove administrator role if role is changed to something else
            if ($user->hasRole('administrator')) {
                $user->roles()->where('name', 'administrator')->detach();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
