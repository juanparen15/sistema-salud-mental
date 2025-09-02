<?php

// ================================
// COMANDO PARA PROBAR PERMISOS
// ================================

// app/Console/Commands/TestPermissionsSystem.php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestPermissionsSystem extends Command
{
    protected $signature = 'mental-health:test-permissions 
                            {email? : Email del usuario a probar}';

    protected $description = 'Prueba el sistema de permisos con diferentes roles';

    public function handle(): int
    {
        $email = $this->argument('email');

        if ($email) {
            return $this->testSpecificUser($email);
        }

        return $this->testAllRoles();
    }

    private function testSpecificUser(string $email): int
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado");
            return 1;
        }

        $this->info("🧪 PROBANDO USUARIO: {$user->name} ({$user->email})");
        $this->line("==========================================");

        $roles = $user->roles->pluck('name')->toArray();
        if (empty($roles)) {
            $this->warn('❌ Este usuario NO tiene roles asignados');
            return 1;
        }

        $this->line("🏷️  Roles: " . implode(', ', $roles));

        $this->testUserPermissions($user);
        $this->testResourceAccess($user);

        return 0;
    }

    private function testAllRoles(): int
    {
        $this->info('🧪 PROBANDO SISTEMA DE PERMISOS COMPLETO');
        $this->line('======================================');

        $testUsers = [
            'admin@saludmental.gov.co' => 'admin',
            'coordinador@saludmental.gov.co' => 'coordinator',
            'psicologo@saludmental.gov.co' => 'psychologist',
            'trabajador@saludmental.gov.co' => 'social_worker',
            'auxiliar@saludmental.gov.co' => 'assistant',
        ];

        foreach ($testUsers as $email => $expectedRole) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->warn("❌ Usuario {$email} no encontrado");
                continue;
            }

            $this->info("\n👤 PROBANDO: {$user->name} ({$expectedRole})");
            $this->line(str_repeat('-', 50));

            if (!$user->hasRole($expectedRole)) {
                $this->warn("❌ Usuario no tiene el rol esperado: {$expectedRole}");
                continue;
            }

            $this->testUserPermissions($user);
        }

        return 0;
    }

    private function testUserPermissions(User $user): void
    {
        $role = $user->roles->first()?->name;

        $expectedPermissions = [
            'admin' => [
                'manage_users' => true,
                'view_reports' => true,
                'edit_all_followups' => true,
                'delete_patients' => true,
            ],
            'coordinator' => [
                'manage_users' => false,
                'view_reports' => true,
                'edit_all_followups' => true,
                'delete_patients' => false,
            ],
            'psychologist' => [
                'manage_users' => false,
                'view_reports' => true,
                'edit_all_followups' => false,
                'delete_patients' => false,
            ],
            'social_worker' => [
                'manage_users' => false,
                'view_reports' => true,
                'edit_all_followups' => false,
                'delete_patients' => false,
            ],
            'assistant' => [
                'manage_users' => false,
                'view_reports' => false,
                'edit_all_followups' => false,
                'delete_patients' => false,
            ],
        ];

        if (!isset($expectedPermissions[$role])) {
            $this->warn("❌ Rol desconocido: {$role}");
            return;
        }

        $permissions = $expectedPermissions[$role];
        $allCorrect = true;

        foreach ($permissions as $permission => $should_have) {
            $has = $user->can($permission);

            if ($has === $should_have) {
                $status = $should_have ? '✅ Tiene' : '✅ No tiene';
                $this->line("   {$status}: {$permission}");
            } else {
                $status = $should_have ? '❌ Debería tener' : '❌ No debería tener';
                $this->line("   {$status}: {$permission}");
                $allCorrect = false;
            }
        }

        if ($allCorrect) {
            $this->info('   ✅ Todos los permisos CORRECTOS');
        } else {
            $this->warn('   ❌ Hay permisos INCORRECTOS');
        }
    }

    private function testResourceAccess(User $user): void
    {
        $this->line("\n🔍 Probando acceso a recursos:");

        $resources = [
            'PatientResource' => \App\Filament\Resources\PatientResource::class,
            'MentalDisorderResource' => \App\Filament\Resources\MentalDisorderResource::class,
            'SuicideAttemptResource' => \App\Filament\Resources\SuicideAttemptResource::class,
            'UserResource' => \App\Filament\Resources\UserResource::class,
        ];

        foreach ($resources as $name => $class) {
            if (!class_exists($class)) {
                $this->line("   ⚠️  {$name}: Clase no existe");
                continue;
            }

            // Simular autenticación
            auth()->login($user);

            try {
                $canView = $class::canViewAny();
                $canCreate = method_exists($class, 'canCreate') ? $class::canCreate() : false;
                $shouldShow = method_exists($class, 'shouldRegisterNavigation') ? $class::shouldRegisterNavigation() : true;

                $access = [];
                if ($canView) $access[] = 'Ver';
                if ($canCreate) $access[] = 'Crear';
                if ($shouldShow) $access[] = 'Navegar';

                if (empty($access)) {
                    $this->line("   ❌ {$name}: Sin acceso");
                } else {
                    $this->line("   ✅ {$name}: " . implode(', ', $access));
                }
            } catch (\Exception $e) {
                $this->line("   ❌ {$name}: Error - {$e->getMessage()}");
            }

            auth()->logout();
        }
    }
}
