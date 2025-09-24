<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Limpiar permisos existentes
        $this->command->info('Eliminando permisos existentes...');
        Permission::query()->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();

        // Crear permisos del sistema
        $this->createPermissions();

        // Crear roles básicos
        $this->createRoles();

        // Asignar permisos a roles
        $this->assignPermissionsToRoles();

        // Crear usuario super admin si no existe
        $this->createSuperAdmin();

        $this->command->info('Roles y permisos creados correctamente');
    }

    private function createPermissions(): void
    {
        $permissions = [
            // Dashboard & Analytics
            ['name' => 'dashboard_view', 'display' => 'Ver Dashboard', 'description' => 'Acceso al panel principal del sistema'],
            ['name' => 'dashboard_statistics', 'display' => 'Ver Estadísticas', 'description' => 'Visualizar estadísticas generales del sistema'],
            ['name' => 'dashboard_analytics', 'display' => 'Ver Analíticas', 'description' => 'Acceso a analíticas avanzadas y métricas'],

            // Patients - Pacientes
            ['name' => 'patients_view_own', 'display' => 'Ver Mis Pacientes', 'description' => 'Consultar información de pacientes propios'],
            ['name' => 'patients_view_any', 'display' => 'Ver Todos los Pacientes', 'description' => 'Ver pacientes de todos los usuarios del sistema'],
            ['name' => 'patients_create', 'display' => 'Crear Pacientes', 'description' => 'Registrar nuevos pacientes en el sistema'],
            ['name' => 'patients_edit_own', 'display' => 'Editar Mis Pacientes', 'description' => 'Modificar información de pacientes propios'],
            ['name' => 'patients_edit_any', 'display' => 'Editar Todos los Pacientes', 'description' => 'Modificar información de cualquier paciente'],
            ['name' => 'patients_delete', 'display' => 'Eliminar Pacientes', 'description' => 'Eliminar registros de pacientes del sistema'],
            ['name' => 'patients_import', 'display' => 'Importar Pacientes', 'description' => 'Importar datos masivos de pacientes'],
            ['name' => 'patients_export', 'display' => 'Exportar Pacientes', 'description' => 'Exportar datos de pacientes a archivos'],

            // Followups - Seguimientos
            ['name' => 'followups_view_own', 'display' => 'Ver Mis Seguimientos', 'description' => 'Consultar seguimientos propios'],
            ['name' => 'followups_view_any', 'display' => 'Ver Todos los Seguimientos', 'description' => 'Ver seguimientos de todos los usuarios'],
            ['name' => 'followups_create', 'display' => 'Crear Seguimientos', 'description' => 'Crear nuevos seguimientos de pacientes'],
            ['name' => 'followups_edit_own', 'display' => 'Editar Mis Seguimientos', 'description' => 'Modificar seguimientos propios'],
            ['name' => 'followups_edit_any', 'display' => 'Editar Todos los Seguimientos', 'description' => 'Modificar cualquier seguimiento del sistema'],
            ['name' => 'followups_delete', 'display' => 'Eliminar Seguimientos', 'description' => 'Eliminar registros de seguimientos'],
            ['name' => 'followups_export', 'display' => 'Exportar Seguimientos', 'description' => 'Exportar datos de seguimientos'],

            // Reports - Reportes
            ['name' => 'reports_view', 'display' => 'Ver Reportes', 'description' => 'Acceso a reportes básicos del sistema'],
            ['name' => 'reports_generate', 'display' => 'Generar Reportes', 'description' => 'Crear y generar nuevos reportes'],
            ['name' => 'reports_export', 'display' => 'Exportar Reportes', 'description' => 'Exportar reportes generados'],
            ['name' => 'reports_advanced', 'display' => 'Reportes Avanzados', 'description' => 'Acceso a reportes y analíticas avanzadas'],

            // Users - Usuarios
            ['name' => 'users_view', 'display' => 'Ver Usuarios', 'description' => 'Consultar información de usuarios'],
            ['name' => 'users_create', 'display' => 'Crear Usuarios', 'description' => 'Registrar nuevos usuarios'],
            ['name' => 'users_edit', 'display' => 'Editar Usuarios', 'description' => 'Modificar información de usuarios'],
            ['name' => 'users_delete', 'display' => 'Eliminar Usuarios', 'description' => 'Eliminar cuentas de usuarios'],

            // Roles & Permissions - Roles y Permisos
            ['name' => 'roles_view', 'display' => 'Ver Roles', 'description' => 'Consultar roles del sistema'],
            ['name' => 'roles_create', 'display' => 'Crear Roles', 'description' => 'Crear nuevos roles'],
            ['name' => 'roles_edit', 'display' => 'Editar Roles', 'description' => 'Modificar roles existentes'],
            ['name' => 'roles_delete', 'display' => 'Eliminar Roles', 'description' => 'Eliminar roles del sistema'],
            ['name' => 'permissions_manage', 'display' => 'Gestionar Permisos', 'description' => 'Asignar/revocar permisos a roles'],

            // System - Sistema
            ['name' => 'system_settings', 'display' => 'Configuración del Sistema', 'description' => 'Modificar configuración del sistema'],
            ['name' => 'system_logs', 'display' => 'Logs del Sistema', 'description' => 'Consultar registros de actividad del sistema'],
            ['name' => 'system_audit', 'display' => 'Auditoría del Sistema', 'description' => 'Consultar registros de auditoría del sistema'],
            ['name' => 'system_backup', 'display' => 'Backup del Sistema', 'description' => 'Crear respaldos del sistema y datos'],
            ['name' => 'system_restore', 'display' => 'Restaurar Sistema', 'description' => 'Restaurar sistema desde respaldos'],
            ['name' => 'system_notifications', 'display' => 'Notificaciones del Sistema', 'description' => 'Administrar sistema de notificaciones'],

            // Bulk Operations - Operaciones en Lote
            ['name' => 'bulk_actions', 'display' => 'Acciones en Lote', 'description' => 'Realizar operaciones masivas en el sistema'],

            // Configuration - Configuración
            ['name' => 'config_followup_types', 'display' => 'Tipos de Seguimiento', 'description' => 'Administrar categorías de seguimientos'],
        ];

        $created = 0;
        foreach ($permissions as $permission) {
            $created++;
            Permission::create([
                'name' => $permission['name'],
                'display_name' => $permission['display'],
                'description' => $permission['description'],
                'guard_name' => 'web'
            ]);
        }

        $this->command->info("Permisos creados: {$created}");
    }

    private function createRoles(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrador',
                'description' => 'Acceso total al sistema con permisos administrativos completos',
                'color' => '#dc2626'
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Administrador del sistema con permisos elevados de gestión',
                'color' => '#2563eb'
            ],
            [
                'name' => 'coordinator',
                'display_name' => 'Coordinador',
                'description' => 'Coordinador de área con permisos de supervisión y gestión',
                'color' => '#059669'
            ],
            [
                'name' => 'psychologist',
                'display_name' => 'Psicólogo',
                'description' => 'Profesional de salud mental con acceso a funciones clínicas',
                'color' => '#7c3aed'
            ],
            [
                'name' => 'social_worker',
                'display_name' => 'Trabajador Social',
                'description' => 'Profesional de trabajo social con permisos específicos',
                'color' => '#ea580c'
            ],
            [
                'name' => 'assistant',
                'display_name' => 'Asistente',
                'description' => 'Asistente con permisos limitados de consulta',
                'color' => '#6b7280'
            ]
        ];

        $created = 0;
        foreach ($roles as $roleData) {
            $created++;
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'guard_name' => 'web'
                ]
            );
        }

        $this->command->info("Roles creados/actualizados: {$created}");
    }

    private function assignPermissionsToRoles(): void
    {
        $rolePermissions = [
            'super_admin' => [
                'dashboard_view',
                'dashboard_statistics',
                'dashboard_analytics',
                'patients_view_own',
                'patients_view_any',
                'patients_create',
                'patients_edit_own',
                'patients_edit_any',
                'patients_delete',
                'patients_import',
                'patients_export',
                'followups_view_own',
                'followups_view_any',
                'followups_create',
                'followups_edit_own',
                'followups_edit_any',
                'followups_delete',
                'followups_export',
                'reports_view',
                'reports_generate',
                'reports_export',
                'reports_advanced',
                'users_view',
                'users_create',
                'users_edit',
                'users_delete',
                'roles_view',
                'roles_create',
                'roles_edit',
                'roles_delete',
                'permissions_manage',
                'system_settings',
                'system_logs',
                'system_audit',
                'system_backup',
                'system_restore',
                'system_notifications',
                'bulk_actions',
                'config_followup_types'
            ],
            'admin' => [
                'dashboard_view',
                'dashboard_statistics',
                'dashboard_analytics',
                'patients_view_own',
                'patients_view_any',
                'patients_create',
                'patients_edit_own',
                'patients_edit_any',
                'patients_delete',
                'patients_import',
                'patients_export',
                'followups_view_own',
                'followups_view_any',
                'followups_create',
                'followups_edit_own',
                'followups_edit_any',
                'followups_delete',
                'followups_export',
                'reports_view',
                'reports_generate',
                'reports_export',
                'reports_advanced',
                'users_view',
                'users_create',
                'users_edit',
                'users_delete',
                'system_logs',
                'system_notifications',
                'bulk_actions',
                'config_followup_types'
            ],
            'coordinator' => [
                'dashboard_view',
                'dashboard_statistics',
                'patients_view_own',
                'patients_view_any',
                'patients_create',
                'patients_edit_own',
                'patients_edit_any',
                'patients_export',
                'followups_view_own',
                'followups_view_any',
                'followups_create',
                'followups_edit_own',
                'followups_edit_any',
                'followups_export',
                'reports_view',
                'reports_generate',
                'reports_export',
                'users_view',
                'bulk_actions',
                'config_followup_types'
            ],
            'psychologist' => [
                'dashboard_view',
                'patients_view_own',
                'patients_create',
                'patients_edit_own',
                'patients_export',
                'followups_view_own',
                'followups_create',
                'followups_edit_own',
                'followups_export',
                'reports_view',
                'reports_generate'
            ],
            'social_worker' => [
                'dashboard_view',
                'patients_view_own',
                'patients_create',
                'patients_edit_own',
                'followups_view_own',
                'followups_create',
                'followups_edit_own',
                'reports_view'
            ],
            'assistant' => [
                'dashboard_view',
                'patients_view_own',
                'followups_view_own',
                'reports_view'
            ]
        ];

        $totalAssignments = 0;
        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                // Obtener los IDs de los permisos por nombre
                $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();

                // Limpiar permisos existentes y asignar nuevos
                $role->permissions()->detach();
                $role->permissions()->attach($permissionIds);

                $totalAssignments += count($permissionIds);
                $this->command->info("✓ Permisos asignados a {$role->display_name}: " . count($permissionIds));
            }
        }

        $this->command->info("Total de asignaciones de permisos: {$totalAssignments}");
    }

    private function createSuperAdmin(): void
    {
        $superAdmin = User::where('email', 'admin@sistema.com')->first();

        if (!$superAdmin) {
            $superAdmin = User::create([
                'name' => 'Super Administrador',
                'email' => 'admin@sistema.com',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]);

            $this->command->info('✓ Usuario super admin creado: admin@sistema.com');
            $this->command->warn('🔑 Contraseña temporal: password123');
        } else {
            $this->command->info('✓ Usuario super admin ya existe: admin@sistema.com');
        }

        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
            $this->command->info('✓ Rol super_admin asignado al usuario');
        }

        $this->createExampleUsers();
    }

    private function createExampleUsers(): void
    {
        $exampleUsers = [
            [
                'name' => 'Coordinador Ejemplo',
                'email' => 'coordinador@sistema.com',
                'role' => 'coordinator'
            ],
            [
                'name' => 'Psicólogo Ejemplo',
                'email' => 'psicologo@sistema.com',
                'role' => 'psychologist'
            ],
            [
                'name' => 'Trabajador Social Ejemplo',
                'email' => 'trabajador@sistema.com',
                'role' => 'social_worker'
            ],
        ];

        foreach ($exampleUsers as $userData) {
            $user = User::where('email', $userData['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]);

                $user->assignRole($userData['role']);
                $this->command->info("✓ Usuario ejemplo creado: {$userData['email']} ({$userData['role']})");
            }
        }
    }
}
