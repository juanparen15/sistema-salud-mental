<?php

// ================================
// COMANDO PARA VERIFICAR Y CORREGIR PERMISOS
// ================================

// app/Console/Commands/FixPermissionsSystem.php
namespace App\Console\Commands;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Console\Command;

class FixPermissionsSystem extends Command
{
    protected $signature = 'mental-health:fix-permissions 
                            {--verify : Solo verificar sin aplicar cambios}
                            {--reset : Resetear completamente el sistema de permisos}';
    
    protected $description = 'Verifica y corrige el sistema de permisos completo';

    public function handle(): int
    {
        $this->info('🔧 INICIANDO VERIFICACIÓN DEL SISTEMA DE PERMISOS...');
        $this->newLine();

        if ($this->option('reset')) {
            return $this->resetPermissions();
        }

        $verify = $this->option('verify');
        $issues = [];

        // 1. Verificar estructura de permisos
        $issues = array_merge($issues, $this->verifyPermissions($verify));

        // 2. Verificar roles
        $issues = array_merge($issues, $this->verifyRoles($verify));

        // 3. Verificar usuarios
        $issues = array_merge($issues, $this->verifyUsers($verify));

        // 4. Verificar cache
        $issues = array_merge($issues, $this->verifyCache($verify));

        // Mostrar resumen
        $this->showSummary($issues);

        return empty($issues) ? 0 : 1;
    }

    private function verifyPermissions(bool $verify): array
    {
        $this->info('📋 Verificando permisos...');
        $issues = [];

        $requiredPermissions = [
            'view_dashboard' => 'Acceso básico al panel',
            'view_statistics' => 'Ver estadísticas',
            'view_analytics' => 'Ver analytics',
            
            // Pacientes
            'view_patients' => 'Ver pacientes',
            'view_any_patients' => 'Ver todos los pacientes',
            'create_patients' => 'Crear pacientes',
            'edit_patients' => 'Editar pacientes',
            'delete_patients' => 'Eliminar pacientes',
            'import_patients' => 'Importar pacientes',
            'export_patients' => 'Exportar pacientes',

            // Seguimientos
            'view_followups' => 'Ver seguimientos propios',
            'view_all_followups' => 'Ver todos los seguimientos',
            'view_any_followups' => 'Ver seguimientos asignados',
            'create_followups' => 'Crear seguimientos',
            'edit_followups' => 'Editar seguimientos propios',
            'edit_all_followups' => 'Editar todos los seguimientos',
            'delete_followups' => 'Eliminar seguimientos',
            'export_followups' => 'Exportar seguimientos',

            // Reportes
            'view_reports' => 'Ver reportes',
            'generate_reports' => 'Generar reportes',
            'export_reports' => 'Exportar reportes',

            // Sistema
            'manage_users' => 'Gestionar usuarios',
            'manage_roles' => 'Gestionar roles',
            'view_system_logs' => 'Ver logs del sistema',
            'manage_settings' => 'Gestionar configuración',
            'bulk_actions' => 'Acciones en lote',
        ];

        foreach ($requiredPermissions as $permission => $description) {
            if (!Permission::where('name', $permission)->exists()) {
                $issues[] = "❌ Falta permiso: {$permission} ({$description})";
                
                if (!$verify) {
                    Permission::create(['name' => $permission]);
                    $this->line("✅ Creado permiso: {$permission}");
                }
            }
        }

        $this->info("   📊 {" . count($requiredPermissions) . "} permisos verificados");
        return $issues;
    }

    private function verifyRoles(bool $verify): array
    {
        $this->info('👥 Verificando roles...');
        $issues = [];

        $roleDefinitions = [
            'super_admin' => [
                'display_name' => 'Super Administrador',
                'permissions' => 'all',
                'color' => '#dc2626'
            ],
            'admin' => [
                'display_name' => 'Administrador',
                'permissions' => [
                    'view_dashboard', 'view_statistics', 'view_analytics',
                    'view_patients', 'view_any_patients', 'create_patients', 'edit_patients', 'delete_patients', 'import_patients', 'export_patients',
                    'view_followups', 'view_all_followups', 'view_any_followups', 'create_followups', 'edit_followups', 'edit_all_followups', 'delete_followups', 'export_followups',
                    'view_reports', 'generate_reports', 'export_reports',
                    'manage_users', 'view_system_logs', 'manage_settings', 'bulk_actions'
                ],
                'color' => '#2563eb'
            ],
            'coordinator' => [
                'display_name' => 'Coordinador',
                'permissions' => [
                    'view_dashboard', 'view_statistics', 'view_analytics',
                    'view_patients', 'view_any_patients', 'create_patients', 'edit_patients', 'import_patients', 'export_patients',
                    'view_followups', 'view_all_followups', 'view_any_followups', 'create_followups', 'edit_followups', 'edit_all_followups', 'delete_followups', 'export_followups',
                    'view_reports', 'generate_reports', 'export_reports', 'bulk_actions'
                ],
                'color' => '#059669'
            ],
            'psychologist' => [
                'display_name' => 'Psicólogo',
                'permissions' => [
                    'view_dashboard', 'view_statistics', 'view_analytics',
                    'view_patients', 'view_any_patients', 'create_patients', 'edit_patients',
                    'view_followups', 'view_any_followups', 'create_followups', 'edit_followups',
                    'view_reports'
                ],
                'color' => '#7c3aed'
            ],
            'social_worker' => [
                'display_name' => 'Trabajador Social',
                'permissions' => [
                    'view_dashboard', 'view_statistics', 'view_analytics',
                    'view_patients', 'view_any_patients', 'create_patients', 'edit_patients',
                    'view_followups', 'view_any_followups', 'create_followups', 'edit_followups',
                    'view_reports'
                ],
                'color' => '#d97706'
            ],
            'assistant' => [
                'display_name' => 'Auxiliar',
                'permissions' => [
                    'view_dashboard',
                    'view_patients', 'view_any_patients', 'create_patients',
                    'view_followups', 'view_any_followups', 'create_followups'
                ],
                'color' => '#6b7280'
            ]
        ];

        foreach ($roleDefinitions as $roleName => $definition) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $issues[] = "❌ Falta rol: {$roleName}";
                
                if (!$verify) {
                    $role = Role::create([
                        'name' => $roleName,
                        'display_name' => $definition['display_name'],
                    ]);
                    $this->line("✅ Creado rol: {$roleName}");
                }
            }

            // Verificar permisos del rol
            if ($role && !$verify) {
                if ($definition['permissions'] === 'all') {
                    $role->syncPermissions(Permission::all());
                } else {
                    $role->syncPermissions($definition['permissions']);
                }
                $this->line("   🔧 Permisos sincronizados para: {$roleName}");
            }
        }

        $this->info("   📊 " . count($roleDefinitions) . " roles verificados");
        return $issues;
    }

    private function verifyUsers(bool $verify): array
    {
        $this->info('👤 Verificando usuarios...');
        $issues = [];

        $defaultUsers = [
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@saludmental.gov.co',
                'role' => 'admin'
            ],
            [
                'name' => 'Coordinador Salud Mental',
                'email' => 'coordinador@saludmental.gov.co',
                'role' => 'coordinator'
            ],
            [
                'name' => 'Dr. Juan Pérez',
                'email' => 'psicologo@saludmental.gov.co',
                'role' => 'psychologist'
            ],
            [
                'name' => 'Ana María López',
                'email' => 'trabajador@saludmental.gov.co',
                'role' => 'social_worker'
            ],
            [
                'name' => 'Carlos Rodríguez',
                'email' => 'auxiliar@saludmental.gov.co',
                'role' => 'assistant'
            ]
        ];

        foreach ($defaultUsers as $userData) {
            $user = User::where('email', $userData['email'])->first();
            
            if (!$user) {
                $issues[] = "❌ Falta usuario por defecto: {$userData['email']}";
            } elseif (!$user->hasRole($userData['role'])) {
                $issues[] = "❌ Usuario {$userData['email']} no tiene rol {$userData['role']}";
                
                if (!$verify) {
                    $user->assignRole($userData['role']);
                    $this->line("✅ Asignado rol {$userData['role']} a {$userData['email']}");
                }
            }
        }

        // Verificar usuarios sin roles
        $usersWithoutRoles = User::doesntHave('roles')->count();
        if ($usersWithoutRoles > 0) {
            $issues[] = "⚠️  {$usersWithoutRoles} usuarios sin roles asignados";
        }

        $this->info("   📊 " . User::count() . " usuarios en el sistema");
        return $issues;
    }

    private function verifyCache(bool $verify): array
    {
        $this->info('🗄️  Verificando cache...');
        $issues = [];

        if (!$verify) {
            // Limpiar cache de permisos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $this->line("✅ Cache de permisos limpiado");
        }

        return $issues;
    }

    private function resetPermissions(): int
    {
        $this->warn('⚠️  RESETEO COMPLETO DEL SISTEMA DE PERMISOS');
        
        if (!$this->confirm('Esto eliminará TODOS los permisos y roles existentes. ¿Continuar?')) {
            $this->info('Operación cancelada.');
            return 1;
        }

        // Eliminar todos los permisos y roles
        Permission::query()->delete();
        Role::query()->delete();
        
        $this->info('🗑️  Sistema limpiado');

        // Ejecutar el seeder
        $this->call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        
        $this->info('✅ Sistema de permisos completamente resetado');
        return 0;
    }

    private function showSummary(array $issues): void
    {
        $this->newLine();
        $this->info('📈 RESUMEN DE VERIFICACIÓN:');
        $this->line('========================');

        if (empty($issues)) {
            $this->info('✅ ¡Sistema de permisos CORRECTO!');
            $this->line('   Todos los componentes están funcionando correctamente.');
        } else {
            $this->warn("❌ Se encontraron {" . count($issues) . "} problemas:");
            foreach ($issues as $issue) {
                $this->line("   {$issue}");
            }
            
            if ($this->option('verify')) {
                $this->newLine();
                $this->info('💡 Para corregir automáticamente, ejecuta:');
                $this->line('   php artisan mental-health:fix-permissions');
            }
        }

        $this->newLine();
        $this->showSystemStatus();
    }

    private function showSystemStatus(): void
    {
        $this->info('📊 ESTADO ACTUAL DEL SISTEMA:');
        $this->line('============================');
        
        $totalUsers = User::count();
        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        
        $this->line("👥 Usuarios: {$totalUsers}");
        $this->line("🏷️  Roles: {$totalRoles}");
        $this->line("🔐 Permisos: {$totalPermissions}");
        
        $this->newLine();
        $this->info('👥 DISTRIBUCIÓN POR ROLES:');
        foreach (Role::withCount('users')->get() as $role) {
            $this->line("   • {$role->name}: {$role->users_count} usuarios");
        }

        $this->newLine();
        $this->info('🧪 Para probar el sistema:');
        $this->line('   php artisan mental-health:test-permissions');
        $this->line('   php artisan serve');
        $this->line('   Acceder a: http://127.0.0.1:8000/admin');
    }
}