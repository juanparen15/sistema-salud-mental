<?php

// ================================
// LIST ROLE PERMISSIONS
// ================================

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRolePermissions extends ListRecords
{
    protected static string $resource = RolePermissionResource::class;

    public function getTitle(): string
    {
        return 'Gestión de Roles y Permisos';
    }

    public function getSubheading(): ?string
    {
        $totalRoles = \Spatie\Permission\Models\Role::count();
        $totalPermissions = \Spatie\Permission\Models\Permission::count();
        $totalUsers = \App\Models\User::count();
        
        return "Sistema: {$totalRoles} roles, {$totalPermissions} permisos, {$totalUsers} usuarios";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Rol'),
                
            Actions\Action::make('manage_permissions')
                ->label('Gestionar Permisos')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->url(fn() => static::getResource()::getUrl('permissions')),
                
            Actions\Action::make('sync_permissions')
                ->label('Sincronizar Permisos')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Esto recreará todos los permisos del sistema. ¿Continuar?')
                ->action(function () {
                    $this->syncSystemPermissions();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Permisos sincronizados')
                        ->body('Los permisos del sistema han sido actualizados.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function syncSystemPermissions(): void
    {
        $permissions = [
            // Permisos de Dashboard
            'view_dashboard',
            'view_statistics',
            'view_analytics',

            // Permisos de Pacientes
            'view_patients',
            'view_any_patients',
            'create_patients',
            'edit_patients',
            'delete_patients',
            'import_patients',
            'export_patients',

            // Permisos de Seguimientos
            'view_followups',
            'view_all_followups',
            'view_any_followups',
            'create_followups',
            'edit_followups',
            'edit_all_followups',
            'delete_followups',
            'export_followups',

            // Permisos de Reportes
            'view_reports',
            'generate_reports',
            'export_reports',

            // Permisos de Sistema
            'manage_users',
            'manage_roles',
            'view_system_logs',
            'manage_settings',
            'bulk_actions',
            'manage_followup_types',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Limpiar cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}