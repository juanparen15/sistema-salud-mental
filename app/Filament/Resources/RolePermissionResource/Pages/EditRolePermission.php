<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRolePermission extends EditRecord
{
    protected static string $resource = RolePermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn($record) => auth()->user()->can('roles_delete') &&
                    !in_array($record->name, ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant']))
                ->requiresConfirmation()
                ->modalDescription('¿Seguro? Los usuarios con este rol perderán sus permisos.')
                ->before(function ($record) {
                    if ($record->users()->count() > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este rol tiene usuarios asignados.')
                            ->danger()
                            ->send();
                        return false;
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Rol actualizado correctamente';
    }

    public function getTitle(): string
    {
        return 'Editar Rol: ' . $this->getRecord()->display_name;
    }
}
