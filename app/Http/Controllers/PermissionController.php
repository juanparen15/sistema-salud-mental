<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Notifications\Notification;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|super_admin']);
    }

    /**
     * Handle permission changes via AJAX
     */
    public function updatePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'changes' => 'required|array',
            'changes.*.role' => 'required|string|exists:roles,name',
            'changes.*.permission' => 'required|string|exists:permissions,name',
            'changes.*.action' => 'required|string|in:add,remove',
        ]);

        try {
            DB::beginTransaction();

            $changes = $request->input('changes');
            $processedCount = 0;
            $errors = [];

            foreach ($changes as $change) {
                try {
                    $role = Role::where('name', $change['role'])->first();
                    $permission = Permission::where('name', $change['permission'])->first();

                    if (!$role || !$permission) {
                        $errors[] = "Rol o permiso no encontrado: {$change['role']} - {$change['permission']}";
                        continue;
                    }

                    // Verificar si es un permiso protegido
                    if ($this->isProtectedPermission($role->name, $permission->name)) {
                        $errors[] = "Permiso protegido no modificable: {$change['role']} - {$change['permission']}";
                        continue;
                    }

                    if ($change['action'] === 'add') {
                        $role->givePermissionTo($permission);
                    } else {
                        $role->revokePermissionTo($permission);
                    }

                    $processedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error procesando {$change['role']} - {$change['permission']}: " . $e->getMessage();
                }
            }

            DB::commit();

            // Limpiar cache de permisos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'message' => "Se procesaron {$processedCount} cambios correctamente.",
                'processed' => $processedCount,
                'errors' => $errors,
                'total_changes' => count($changes)
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar cambios: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Get current permission matrix
     */
    public function getPermissionMatrix(): JsonResponse
    {
        try {
            $roles = Role::with(['users', 'permissions'])->get();
            $permissions = Permission::all()->groupBy(function ($permission) {
                $parts = explode('_', $permission->name);
                return ucfirst($parts[0]);
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

            return response()->json([
                'success' => true,
                'data' => [
                    'matrix' => $matrix,
                    'roles' => $roles->map(function ($role) {
                        return [
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                            'users_count' => $role->users()->count(),
                            'permissions_count' => $role->permissions()->count(),
                        ];
                    }),
                    'permissions' => $permissions
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la matriz de permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export permission matrix as CSV
     */
    public function exportMatrix()
    {
        try {
            $roles = Role::with('permissions')->get();
            $permissions = Permission::all()->groupBy(function ($permission) {
                $parts = explode('_', $permission->name);
                return ucfirst($parts[0]);
            });

            $filename = 'permission-matrix-' . date('Y-m-d-H-i-s') . '.csv';

            return response()->streamDownload(function () use ($roles, $permissions) {
                $output = fopen('php://output', 'w');

                // Headers
                $headers = ['Permission', 'Category', 'Description'];
                foreach ($roles as $role) {
                    $headers[] = $role->display_name ?? $role->name;
                }
                fputcsv($output, $headers);

                // Data rows
                foreach ($permissions as $category => $perms) {
                    foreach ($perms as $permission) {
                        $row = [
                            $permission->name,
                            $category,
                            $permission->description ?? ''
                        ];

                        foreach ($roles as $role) {
                            $row[] = $role->hasPermissionTo($permission->name) ? 'Yes' : 'No';
                        }

                        fputcsv($output, $row);
                    }
                }

                fclose($output);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\""
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar la matriz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync system permissions
     */
    public function syncSystemPermissions(): JsonResponse
    {
        try {
            $systemPermissions = [
                // Dashboard
                ['name' => 'view_dashboard', 'display' => 'Ver Dashboard', 'desc' => 'Acceso al panel principal'],
                ['name' => 'view_statistics', 'display' => 'Ver Estadísticas', 'desc' => 'Ver métricas del sistema'],
                ['name' => 'view_analytics', 'display' => 'Ver Analíticas', 'desc' => 'Acceso a analíticas avanzadas'],

                // Pacientes
                ['name' => 'view_patients', 'display' => 'Ver Pacientes', 'desc' => 'Consultar información de pacientes'],
                ['name' => 'view_any_patients', 'display' => 'Ver Todos los Pacientes', 'desc' => 'Ver pacientes de todos los usuarios'],
                ['name' => 'create_patients', 'display' => 'Crear Pacientes', 'desc' => 'Registrar nuevos pacientes'],
                ['name' => 'edit_patients', 'display' => 'Editar Pacientes', 'desc' => 'Modificar información de pacientes'],
                ['name' => 'delete_patients', 'display' => 'Eliminar Pacientes', 'desc' => 'Eliminar registros de pacientes'],
                ['name' => 'import_patients', 'display' => 'Importar Pacientes', 'desc' => 'Importar datos de pacientes'],
                ['name' => 'export_patients', 'display' => 'Exportar Pacientes', 'desc' => 'Exportar datos de pacientes'],

                // Seguimientos
                ['name' => 'view_followups', 'display' => 'Ver Seguimientos', 'desc' => 'Consultar seguimientos propios'],
                ['name' => 'view_all_followups', 'display' => 'Ver Todos los Seguimientos', 'desc' => 'Ver seguimientos de todos los usuarios'],
                ['name' => 'create_followups', 'display' => 'Crear Seguimientos', 'desc' => 'Crear nuevos seguimientos'],
                ['name' => 'edit_followups', 'display' => 'Editar Seguimientos', 'desc' => 'Modificar seguimientos propios'],
                ['name' => 'edit_all_followups', 'display' => 'Editar Todos los Seguimientos', 'desc' => 'Modificar cualquier seguimiento'],
                ['name' => 'delete_followups', 'display' => 'Eliminar Seguimientos', 'desc' => 'Eliminar seguimientos'],
                ['name' => 'export_followups', 'display' => 'Exportar Seguimientos', 'desc' => 'Exportar datos de seguimientos'],

                // Reportes
                ['name' => 'view_reports', 'display' => 'Ver Reportes', 'desc' => 'Acceso a reportes básicos'],
                ['name' => 'generate_reports', 'display' => 'Generar Reportes', 'desc' => 'Crear nuevos reportes'],
                ['name' => 'export_reports', 'display' => 'Exportar Reportes', 'desc' => 'Exportar reportes generados'],
                ['name' => 'view_advanced_reports', 'display' => 'Ver Reportes Avanzados', 'desc' => 'Acceso a reportes avanzados'],

                // Administración
                ['name' => 'manage_users', 'display' => 'Gestionar Usuarios', 'desc' => 'Administrar cuentas de usuario'],
                ['name' => 'manage_roles', 'display' => 'Gestionar Roles', 'desc' => 'Administrar roles y permisos'],
                ['name' => 'view_system_logs', 'display' => 'Ver Logs del Sistema', 'desc' => 'Consultar logs de sistema'],
                ['name' => 'manage_settings', 'display' => 'Gestionar Configuración', 'desc' => 'Modificar configuración del sistema'],
                ['name' => 'bulk_actions', 'display' => 'Acciones en Lote', 'desc' => 'Realizar acciones masivas'],
                ['name' => 'manage_followup_types', 'display' => 'Gestionar Tipos de Seguimiento', 'desc' => 'Administrar tipos de seguimiento'],

                // Sistema
                ['name' => 'system_backup', 'display' => 'Backup del Sistema', 'desc' => 'Crear respaldos del sistema'],
                ['name' => 'system_restore', 'display' => 'Restaurar Sistema', 'desc' => 'Restaurar desde respaldos'],
                ['name' => 'view_audit_logs', 'display' => 'Ver Logs de Auditoría', 'desc' => 'Consultar logs de auditoría'],
                ['name' => 'manage_notifications', 'display' => 'Gestionar Notificaciones', 'desc' => 'Administrar notificaciones del sistema'],
            ];

            $created = 0;
            $updated = 0;

            foreach ($systemPermissions as $perm) {
                $permission = Permission::updateOrCreate(
                    ['name' => $perm['name']],
                    [
                        'display_name' => $perm['display'],
                        'description' => $perm['desc'],
                        'guard_name' => 'web'
                    ]
                );

                if ($permission->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            // Limpiar cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada: {$created} creados, {$updated} actualizados",
                'created' => $created,
                'updated' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if permission is protected for a role
     */
    private function isProtectedPermission(string $roleName, string $permissionName): bool
    {
        // Permisos críticos que no se pueden remover del super_admin
        if ($roleName === 'super_admin') {
            $protectedPermissions = [
                'manage_roles',
                'manage_users',
                'system_backup',
                'system_restore',
                'view_audit_logs',
                'manage_settings'
            ];

            return in_array($permissionName, $protectedPermissions);
        }

        // Permisos básicos que no se pueden remover de admin
        if ($roleName === 'admin') {
            $protectedPermissions = [
                'view_dashboard',
                'manage_users'
            ];

            return in_array($permissionName, $protectedPermissions);
        }

        return false;
    }
}
