<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRolePermission extends CreateRecord
{
    protected static string $resource = RolePermissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rol creado correctamente';
    }

    public function getTitle(): string
    {
        return 'Crear Nuevo Rol';
    }
}