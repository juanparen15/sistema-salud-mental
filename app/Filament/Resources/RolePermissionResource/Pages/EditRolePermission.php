<?php

// ================================
// EDIT ROLE PERMISSION
// ================================

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRolePermission extends EditRecord
{
    protected static string $resource = RolePermissionResource::class;

    public function getTitle(): string
    {
        return "Editar Rol: {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_users')
                ->label('Ver Usuarios con este Rol')
                ->icon('heroicon-o-users')
                ->color('info')
                ->url(fn() => \App\Filament\Resources\UserResource::getUrl('index', [
                    'tableFilters[roles][value]' => $this->getRecord()->name
                ]))
                ->visible(fn() => auth()->user()->can('manage_users')),
                
            Actions\Action::make('duplicate')
                ->label('Duplicar Rol')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Nombre del Nuevo Rol')
                        ->required()
                        ->unique('roles', 'name')
                        ->default(fn() => $this->getRecord()->name . '_copy'),
                ])
                ->action(function (array $data) {
                    $originalRole = $this->getRecord();
                    
                    $newRole = \Spatie\Permission\Models\Role::create([
                        'name' => $data['name'],
                        'display_name' => $originalRole->display_name . ' (Copia)',
                        'description' => $originalRole->description,
                    ]);
                    
                    $newRole->syncPermissions($originalRole->permissions);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Rol duplicado')
                        ->body("Se creó el rol {$data['name']} con los mismos permisos.")
                        ->success()
                        ->send();
                        
                    return redirect(static::getResource()::getUrl('edit', ['record' => $newRole]));
                }),
                
            Actions\DeleteAction::make()
                ->visible(fn() => !in_array($this->getRecord()->name, [
                    'super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant'
                ]))
                ->before(function () {
                    if ($this->getRecord()->users()->count() > 0) {
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        
        // Sincronizar permisos después de guardar
        $this->afterSave(function () use ($permissions) {
            $this->getRecord()->syncPermissions($permissions);
        });
        
        return $data;
    }
}