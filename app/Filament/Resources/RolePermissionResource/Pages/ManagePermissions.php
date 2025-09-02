<?php
// ================================
// MANAGE PERMISSIONS PAGE
// ================================

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManagePermissions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = RolePermissionResource::class;
    protected static string $view = 'filament.pages.manage-permissions';

    public function getTitle(): string
    {
        return 'Gestionar Permisos del Sistema';
    }

    public function getSubheading(): ?string
    {
        return 'Administra permisos y asignaciones por rol';
    }

    protected function getActions(): array
    {
        return [
            Action::make('create_permission')
                ->label('Crear Permiso')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Nombre del Permiso')
                        ->required()
                        ->unique('permissions', 'name')
                        ->helperText('Ej: edit_special_cases'),
                    \Filament\Forms\Components\TextInput::make('display_name')
                        ->label('Nombre para Mostrar')
                        ->helperText('Ej: Editar Casos Especiales'),
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    Permission::create($data);

                    \Filament\Notifications\Notification::make()
                        ->title('Permiso creado')
                        ->body("El permiso {$data['name']} fue creado correctamente.")
                        ->success()
                        ->send();
                }),

            Action::make('sync_all')
                ->label('Sincronizar Todo')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                    \Filament\Notifications\Notification::make()
                        ->title('Cache actualizado')
                        ->body('El cache de permisos ha sido limpiado.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getPermissionMatrix(): array
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('_', $permission->name)[0];
        });

        $matrix = [];
        foreach ($permissions as $category => $perms) {
            $matrix[$category] = [];
            foreach ($perms as $permission) {
                $matrix[$category][$permission->name] = [];
                foreach ($roles as $role) {
                    $matrix[$category][$permission->name][$role->name] =
                        $role->hasPermissionTo($permission->name);
                }
            }
        }

        return [
            'matrix' => $matrix,
            'roles' => $roles->pluck('name', 'name')->toArray(),
        ];
    }
}
