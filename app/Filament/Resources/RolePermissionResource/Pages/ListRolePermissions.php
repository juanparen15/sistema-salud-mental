<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class ListRolePermissions extends ListRecords
{
    protected static string $resource = RolePermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => auth()->user()->can('roles_create')),
            
            Action::make('sync_permissions')
                ->label('Sincronizar Permisos del Sistema')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sincronizar Permisos')
                ->modalDescription('Esto ejecutará el seeder para actualizar los permisos del sistema.')
                ->action(function () {
                    try {
                        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
                        
                        Notification::make()
                            ->title('Permisos sincronizados')
                            ->body('Los permisos del sistema han sido actualizados correctamente.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al sincronizar')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn() => auth()->user()->can('permissions_manage')),
                
            Action::make('view_all_permissions')
                ->label('Ver Todos los Permisos')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Todos los Permisos del Sistema')
                ->modalContent(function () {
                    $permissions = Permission::all()->groupBy(function ($permission) {
                        $parts = explode('_', $permission->name);
                        $modules = [
                            'dashboard' => 'Dashboard & Analytics',
                            'patients' => 'Pacientes',
                            'followups' => 'Seguimientos',
                            'reports' => 'Reportes',
                            'users' => 'Usuarios',
                            'roles' => 'Roles & Permisos',
                            'permissions' => 'Roles & Permisos',
                            'system' => 'Sistema',
                            'bulk' => 'Operaciones en Lote',
                            'config' => 'Configuración'
                        ];
                        
                        $module = $parts[0];
                        return $modules[$module] ?? ucfirst($module);
                    });

                    $html = '<div class="space-y-4">';
                    
                    foreach ($permissions as $module => $perms) {
                        $html .= '<div>';
                        $html .= '<h3 class="font-semibold text-lg mb-2">' . $module . '</h3>';
                        $html .= '<div class="grid grid-cols-2 gap-2">';
                        
                        foreach ($perms as $permission) {
                            $html .= '<div class="p-2 bg-gray-50 rounded">';
                            $html .= '<div class="font-medium">' . ($permission->display_name ?: $permission->name) . '</div>';
                            $html .= '<div class="text-sm text-gray-600">' . $permission->name . '</div>';
                            if ($permission->description) {
                                $html .= '<div class="text-xs text-gray-500">' . $permission->description . '</div>';
                            }
                            $html .= '</div>';
                        }
                        
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    
                    $html .= '</div>';
                    
                    return view('filament::components.modal.content', ['slot' => $html]);
                })
                ->modalWidth('7xl')
                ->visible(fn() => auth()->user()->can('permissions_manage')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí puedes agregar widgets si necesitas estadísticas
        ];
    }

    public function getTitle(): string
    {
        return 'Gestión de Roles y Permisos';
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [5, 10, 25, 50, 100];
    }
}