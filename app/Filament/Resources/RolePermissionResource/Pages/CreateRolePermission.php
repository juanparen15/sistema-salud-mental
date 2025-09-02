<?php

// ================================
// CREATE ROLE PERMISSION
// ================================

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRolePermission extends CreateRecord
{
    protected static string $resource = RolePermissionResource::class;

    public function getTitle(): string
    {
        return 'Crear Nuevo Rol';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rol creado correctamente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar que el nombre esté en snake_case
        $data['name'] = strtolower(str_replace(' ', '_', $data['name']));
        
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        
        $role = \Spatie\Permission\Models\Role::create($data);
        
        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }
        
        return $role;
    }
}