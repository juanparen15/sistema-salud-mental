<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ManagePermissions extends Page implements HasForms
{
    // use InteractsWithForms;

    // protected static string $resource = RolePermissionResource::class;
    // protected static string $view = 'filament.pages.manage-permissions';

    // public function getTitle(): string
    // {
    //     return 'Gestionar Permisos del Sistema';
    // }

    // public function getSubheading(): ?string
    // {
    //     return 'Administra permisos y asignaciones por rol';
    // }

    // protected function getActions(): array
    // {
    //     return [
    //         Action::make('create_permission')
    //             ->label('Crear Permiso')
    //             ->icon('heroicon-o-plus')
    //             ->color('success')
    //             ->form([
    //                 \Filament\Forms\Components\TextInput::make('name')
    //                     ->label('Nombre del Permiso')
    //                     ->required()
    //                     ->unique('permissions', 'name')
    //                     ->helperText('Ej: edit_special_cases')
    //                     ->rule('regex:/^[a-z0-9_]+$/')
    //                     ->validationMessages([
    //                         'regex' => 'Solo letras minúsculas, números y guiones bajos.'
    //                     ])
    //                     ->reactive()
    //                     ->afterStateUpdated(function ($state, callable $set, callable $get) {
    //                         if ($state && !$get('display_name')) {
    //                             $set('display_name', ucfirst(str_replace('_', ' ', $state)));
    //                         }
    //                     }),

    //                 \Filament\Forms\Components\TextInput::make('display_name')
    //                     ->label('Nombre para Mostrar')
    //                     ->required()
    //                     ->helperText('Ej: Editar Casos Especiales'),

    //                 \Filament\Forms\Components\Textarea::make('description')
    //                     ->label('Descripción')
    //                     ->rows(2)
    //                     ->helperText('Descripción detallada del permiso'),
    //             ])
    //             ->action(function (array $data) {
    //                 try {
    //                     Permission::create([
    //                         'name' => $data['name'],
    //                         'display_name' => $data['display_name'],
    //                         'description' => $data['description'] ?? null,
    //                         'guard_name' => 'web',
    //                     ]);

    //                     // Limpiar cache
    //                     app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    //                     Notification::make()
    //                         ->title('Permiso creado')
    //                         ->body("El permiso '{$data['display_name']}' fue creado correctamente.")
    //                         ->success()
    //                         ->send();

    //                     return redirect()->to(request()->url());
    //                 } catch (\Exception $e) {
    //                     Notification::make()
    //                         ->title('Error al crear permiso')
    //                         ->body('Ocurrió un error: ' . $e->getMessage())
    //                         ->danger()
    //                         ->send();
    //                 }
    //             }),

    //         Action::make('sync_system_permissions')
    //             ->label('Sincronizar Permisos')
    //             ->icon('heroicon-o-arrow-path')
    //             ->color('warning')
    //             ->requiresConfirmation()
    //             ->modalDescription('Esto creará/actualizará los permisos básicos del sistema.')
    //             ->action(function () {
    //                 $synced = $this->syncSystemPermissions();

    //                 Notification::make()
    //                     ->title('Permisos sincronizados')
    //                     ->body("Se sincronizaron {$synced} permisos del sistema.")
    //                     ->success()
    //                     ->send();

    //                 return redirect()->to(request()->url());
    //             }),

    //         Action::make('export_matrix')
    //             ->label('Exportar Matriz')
    //             ->icon('heroicon-o-arrow-down-tray')
    //             ->color('info')
    //             ->action(function () {
    //                 return response()->streamDownload(function () {
    //                     echo $this->generatePermissionMatrixCsv();
    //                 }, 'permission-matrix-' . date('Y-m-d-H-i-s') . '.csv');
    //             }),
    //     ];
    // }

    // /**
    //  * Get permission matrix data for the view
    //  */
    // public function getPermissionMatrix(): array
    // {
    //     $roles = Role::with(['users', 'permissions'])->get();
    //     $permissions = Permission::all()->groupBy(function ($permission) {
    //         $parts = explode('_', $permission->name);
    //         return ucfirst($parts[0]);
    //     });

    //     $matrix = [];
    //     foreach ($permissions as $category => $perms) {
    //         $matrix[$category] = [];
    //         foreach ($perms as $permission) {
    //             $matrix[$category][$permission->name] = [];
    //             foreach ($roles as $role) {
    //                 $matrix[$category][$permission->name][$role->name] =
    //                     $role->hasPermissionTo($permission->name);
    //             }
    //         }
    //     }

    //     return [
    //         'matrix' => $matrix,
    //         'roles' => $roles->map(function ($role) {
    //             return [
    //                 'name' => $role->name,
    //                 'display_name' => $role->display_name ?: ucfirst(str_replace('_', ' ', $role->name)),
    //                 'description' => $role->description,
    //                 'users_count' => $role->users_count ?? $role->users()->count(),
    //                 'permissions_count' => $role->permissions_count ?? $role->permissions()->count(),
    //             ];
    //         })->toArray(),
    //         'permissions' => $permissions->map(function ($categoryPerms) {
    //             return $categoryPerms->map(function ($permission) {
    //                 return [
    //                     'name' => $permission->name,
    //                     'display_name' => $permission->display_name ?: ucfirst(str_replace('_', ' ', $permission->name)),
    //                     'description' => $permission->description,
    //                 ];
    //             })->toArray();
    //         })->toArray(),
    //     ];
    // }

    // /**
    //  * Handle AJAX requests for permission changes
    //  */
    // public function handlePermissionChanges(Request $request): JsonResponse
    // {
    //     if (!$request->ajax()) {
    //         abort(404);
    //     }

    //     $request->validate([
    //         'changes' => 'required|array',
    //         'changes.*.role' => 'required|string|exists:roles,name',
    //         'changes.*.permission' => 'required|string|exists:permissions,name',
    //         'changes.*.action' => 'required|string|in:add,remove',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $changes = $request->input('changes', []);
    //         $processedCount = 0;
    //         $errors = [];

    //         foreach ($changes as $change) {
    //             try {
    //                 $role = Role::where('name', $change['role'])->first();
    //                 $permission = Permission::where('name', $change['permission'])->first();

    //                 if (!$role || !$permission) {
    //                     $errors[] = "Rol o permiso no encontrado: {$change['role']} - {$change['permission']}";
    //                     continue;
    //                 }

    //                 // Verificar si es un permiso protegido
    //                 if ($this->isProtectedRolePermission($role->name, $permission->name)) {
    //                     $errors[] = "Permiso protegido no modificable: {$role->display_name} - {$permission->display_name}";
    //                     continue;
    //                 }

    //                 if ($change['action'] === 'add') {
    //                     $role->givePermissionTo($permission);
    //                 } else {
    //                     $role->revokePermissionTo($permission);
    //                 }

    //                 $processedCount++;
    //             } catch (\Exception $e) {
    //                 $errors[] = "Error procesando {$change['role']} - {$change['permission']}: " . $e->getMessage();
    //             }
    //         }

    //         DB::commit();

    //         // Limpiar cache
    //         app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    //         return response()->json([
    //             'success' => true,
    //             'message' => "Se procesaron {$processedCount} cambios correctamente.",
    //             'processed' => $processedCount,
    //             'errors' => $errors,
    //             'total_changes' => count($changes)
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollback();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al aplicar cambios: ' . $e->getMessage(),
    //             'errors' => []
    //         ], 500);
    //     }
    // }

    // /**
    //  * Sync system permissions
    //  */
    // private function syncSystemPermissions(): int
    // {
    //     $systemPermissions = [
    //         // Dashboard
    //         'view_dashboard' => 'Ver Dashboard',
    //         'view_statistics' => 'Ver Estadísticas',
    //         'view_analytics' => 'Ver Analíticas',

    //         // Pacientes
    //         'view_patients' => 'Ver Pacientes',
    //         'view_any_patients' => 'Ver Todos los Pacientes',
    //         'create_patients' => 'Crear Pacientes',
    //         'edit_patients' => 'Editar Pacientes',
    //         'delete_patients' => 'Eliminar Pacientes',
    //         'import_patients' => 'Importar Pacientes',
    //         'export_patients' => 'Exportar Pacientes',

    //         // Seguimientos
    //         'view_followups' => 'Ver Seguimientos',
    //         'view_all_followups' => 'Ver Todos los Seguimientos',
    //         'view_any_followups' => 'Ver Cualquier Seguimiento',
    //         'create_followups' => 'Crear Seguimientos',
    //         'edit_followups' => 'Editar Seguimientos',
    //         'edit_all_followups' => 'Editar Todos los Seguimientos',
    //         'delete_followups' => 'Eliminar Seguimientos',
    //         'export_followups' => 'Exportar Seguimientos',

    //         // Reportes
    //         'view_reports' => 'Ver Reportes',
    //         'generate_reports' => 'Generar Reportes',
    //         'export_reports' => 'Exportar Reportes',
    //         'view_advanced_reports' => 'Ver Reportes Avanzados',

    //         // Sistema
    //         'manage_users' => 'Gestionar Usuarios',
    //         'manage_roles' => 'Gestionar Roles',
    //         'view_system_logs' => 'Ver Logs del Sistema',
    //         'manage_settings' => 'Gestionar Configuración',
    //         'bulk_actions' => 'Acciones en Lote',
    //         'manage_followup_types' => 'Gestionar Tipos de Seguimiento',

    //         // Administración
    //         'system_backup' => 'Backup del Sistema',
    //         'system_restore' => 'Restaurar Sistema',
    //         'manage_notifications' => 'Gestionar Notificaciones',
    //         'view_audit_logs' => 'Ver Logs de Auditoría',
    //     ];

    //     $syncedCount = 0;
    //     foreach ($systemPermissions as $permission => $displayName) {
    //         Permission::updateOrCreate(
    //             ['name' => $permission],
    //             [
    //                 'display_name' => $displayName,
    //                 'guard_name' => 'web',
    //                 'description' => 'Permiso del sistema: ' . $displayName
    //             ]
    //         );
    //         $syncedCount++;
    //     }

    //     // Limpiar cache
    //     app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    //     return $syncedCount;
    // }

    // /**
    //  * Check if a role-permission combination is protected
    //  */
    // private function isProtectedRolePermission(string $roleName, string $permissionName): bool
    // {
    //     // Proteger permisos críticos del super_admin
    //     if ($roleName === 'super_admin') {
    //         $protectedPermissions = [
    //             'manage_roles',
    //             'manage_users',
    //             'system_backup',
    //             'system_restore',
    //             'view_audit_logs'
    //         ];

    //         return in_array($permissionName, $protectedPermissions);
    //     }

    //     return false;
    // }

    // /**
    //  * Generate CSV export of permission matrix
    //  */
    // private function generatePermissionMatrixCsv(): string
    // {
    //     $data = $this->getPermissionMatrix();
    //     $output = '';

    //     // Headers
    //     $headers = ['Permission', 'Display Name', 'Category'];
    //     foreach ($data['roles'] as $role) {
    //         $headers[] = $role['display_name'] ?? $role['name'];
    //     }
    //     $output .= implode(',', $headers) . "\n";

    //     // Data rows
    //     foreach ($data['matrix'] as $category => $permissions) {
    //         foreach ($permissions as $permissionName => $rolePermissions) {
    //             $permission = Permission::where('name', $permissionName)->first();
    //             $displayName = $permission ? ($permission->display_name ?: ucfirst(str_replace('_', ' ', $permissionName))) : $permissionName;

    //             $row = [$permissionName, $displayName, $category];
    //             foreach ($data['roles'] as $role) {
    //                 $row[] = $rolePermissions[$role['name']] ? 'Sí' : 'No';
    //             }
    //             $output .= implode(',', array_map(function ($value) {
    //                 return '"' . str_replace('"', '""', $value) . '"';
    //             }, $row)) . "\n";
    //         }
    //     }

    //     return $output;
    // }

    // /**
    //  * Mount the page
    //  */
    // public function mount(): void
    // {
    //     // Verificar permisos de acceso
    //     if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
    //         abort(403, 'No tienes permisos para acceder a esta página.');
    //     }
    // }

    /**
     * Handle POST requests (para AJAX desde la vista)
     */
    // public function __invoke(Request $request)
    // {
    //     if ($request->isMethod('post') && $request->ajax() && $request->has('changes')) {
    //         return $this->handlePermissionChanges($request);
    //     }

    //     // Para GET requests, mostrar la página normalmente
    //     return $this->render();
    // }
}
