<?php

// ================================
// COMANDO PARA VERIFICAR PERMISOS
// ================================

// app/Console/Commands/CheckPermissions.php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CheckPermissions extends Command
{
    protected $signature = 'mental-health:check-permissions 
                            {user_email? : Email del usuario específico}
                            {--role= : Verificar permisos de un rol específico}';
    
    protected $description = 'Verifica y muestra los permisos del sistema';

    public function handle()
    {
        $userEmail = $this->argument('user_email');
        $roleName = $this->option('role');

        if ($userEmail) {
            $this->checkUserPermissions($userEmail);
        } elseif ($roleName) {
            $this->checkRolePermissions($roleName);
        } else {
            $this->showSystemOverview();
        }

        return 0;
    }

    private function checkUserPermissions(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado.");
            return;
        }

        $this->info("👤 PERMISOS DE {$user->name} ({$user->email})");
        $this->info('================================================');

        $roles = $user->roles;
        if ($roles->isEmpty()) {
            $this->warn('❌ Este usuario no tiene roles asignados.');
        } else {
            $this->line('🏷️ ROLES:');
            foreach ($roles as $role) {
                $this->line("   • {$role->name}");
            }
        }

        $permissions = $user->getAllPermissions();
        if ($permissions->isEmpty()) {
            $this->warn('❌ Este usuario no tiene permisos.');
        } else {
            $this->line("\n🔐 PERMISOS ({$permissions->count()}):");
            foreach ($permissions->groupBy('category') as $category => $perms) {
                $this->line("   📂 {$category}:");
                foreach ($perms as $permission) {
                    $this->line("      ✅ {$permission->name}");
                }
            }
        }
    }

    private function checkRolePermissions(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Rol '{$roleName}' no encontrado.");
            $this->line('Roles disponibles: ' . Role::pluck('name')->implode(', '));
            return;
        }

        $this->info("🏷️ PERMISOS DEL ROL: {$role->name}");
        $this->info('=======================================');

        $permissions = $role->permissions;
        if ($permissions->isEmpty()) {
            $this->warn('❌ Este rol no tiene permisos asignados.');
        } else {
            $this->line("🔐 PERMISOS ({$permissions->count()}):");
            foreach ($permissions as $permission) {
                $this->line("   ✅ {$permission->name}");
            }
        }

        $users = $role->users;
        $this->line("\n👥 USUARIOS CON ESTE ROL ({$users->count()}):");
        foreach ($users as $user) {
            $this->line("   • {$user->name} ({$user->email})");
        }
    }

    private function showSystemOverview(): void
    {
        $this->info('🔐 RESUMEN DEL SISTEMA DE PERMISOS');
        $this->info('=====================================');

        $totalRoles = Role::count();
        $totalPermissions = Permission::count();
        $totalUsers = User::count();

        $this->line("🏷️ Roles definidos: {$totalRoles}");
        $this->line("🔐 Permisos definidos: {$totalPermissions}");
        $this->line("👥 Usuarios totales: {$totalUsers}");

        $this->info("\n📋 ROLES:");
        Role::with('users')->get()->each(function ($role) {
            $userCount = $role->users->count();
            $this->line("   • {$role->name} ({$userCount} usuarios)");
        });

        $this->info("\n📊 CATEGORÍAS DE PERMISOS:");
        Permission::all()
            ->groupBy(function ($permission) {
                return explode('_', $permission->name)[0];
            })
            ->each(function ($permissions, $category) {
                $count = $permissions->count();
                $this->line("   • {$category}: {$count} permisos");
            });
    }
}