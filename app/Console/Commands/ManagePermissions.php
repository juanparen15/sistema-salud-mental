<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class ManagePermissions extends Command
{
    protected $signature = 'permissions:manage 
                            {action : Acción a realizar (create-role, assign-permission, list-roles, list-permissions, sync)}
                            {--role= : Nombre del rol}
                            {--permission= : Nombre del permiso}
                            {--user= : Email del usuario}
                            {--display= : Nombre para mostrar}
                            {--description= : Descripción}';

    protected $description = 'Gestionar roles y permisos del sistema';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'create-role':
                $this->createRole();
                break;
            case 'assign-permission':
                $this->assignPermission();
                break;
            case 'list-roles':
                $this->listRoles();
                break;
            case 'list-permissions':
                $this->listPermissions();
                break;
            case 'sync':
                $this->syncPermissions();
                break;
            case 'create-user':
                $this->createUserWithRole();
                break;
            case 'matrix':
                $this->showPermissionMatrix();
                break;
            default:
                $this->error("Acción '{$action}' no reconocida");
                $this->showHelp();
        }
    }

    private function createRole()
    {
        $roleName = $this->option('role') ?: $this->ask('Nombre del rol');
        $displayName = $this->option('display') ?: $this->ask('Nombre para mostrar', ucfirst(str_replace('_', ' ', $roleName)));
        $description = $this->option('description') ?: $this->ask('Descripción del rol');

        try {
            $role = Role::create([
                'name' => $roleName,
                'display_name' => $displayName,
                'description' => $description,
                'guard_name' => 'web'
            ]);

            $this->info("Rol '{$roleName}' creado correctamente");

            // Preguntar si desea asignar permisos
            if ($this->confirm('¿Desea asignar permisos a este rol ahora?')) {
                $this->assignPermissionsToRole($role);
            }

        } catch (\Exception $e) {
            $this->error("Error al crear el rol: {$e->getMessage()}");
        }
    }

    private function assignPermission()
    {
        $roleName = $this->option('role') ?: $this->ask('Nombre del rol');
        $permissionName = $this->option('permission') ?: $this->ask('Nombre del permiso');

        try {
            $role = Role::where('name', $roleName)->first();
            $permission = Permission::where('name', $permissionName)->first();

            if (!$role) {
                $this->error("Rol '{$roleName}' no encontrado");
                return;
            }

            if (!$permission) {
                $this->error("Permiso '{$permissionName}' no encontrado");
                return;
            }

            $role->givePermissionTo($permission);
            $this->info("Permiso '{$permissionName}' asignado al rol '{$roleName}'");

        } catch (\Exception $e) {
            $this->error("Error al asignar permiso: {$e->getMessage()}");
        }
    }

    private function listRoles()
    {
        $roles = Role::with('permissions', 'users')->get();

        $this->info('=== ROLES DEL SISTEMA ===');
        $this->table(
            ['Nombre', 'Nombre Mostrar', 'Usuarios', 'Permisos', 'Descripción'],
            $roles->map(function ($role) {
                return [
                    $role->name,
                    $role->display_name ?: 'N/A',
                    $role->users->count(),
                    $role->permissions->count(),
                    $role->description ?: 'N/A'
                ];
            })
        );
    }

    private function listPermissions()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('_', $permission->name)[0];
        });

        $this->info('=== PERMISOS DEL SISTEMA ===');
        
        foreach ($permissions as $category => $perms) {
            $this->info("\n--- {$category} ---");
            $this->table(
                ['Permiso', 'Nombre Mostrar', 'Descripción'],
                $perms->map(function ($permission) {
                    return [
                        $permission->name,
                        $permission->display_name ?: 'N/A',
                        $permission->description ?: 'N/A'
                    ];
                })
            );
        }
    }

    private function syncPermissions()
    {
        $this->info('Sincronizando permisos del sistema...');
        
        try {
            $this->call('db:seed', ['--class' => 'RolePermissionSeeder']);
            
            // Limpiar cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            $this->info('Permisos sincronizados correctamente');
            
        } catch (\Exception $e) {
            $this->error("Error al sincronizar permisos: {$e->getMessage()}");
        }
    }

    private function createUserWithRole()
    {
        $email = $this->option('user') ?: $this->ask('Email del usuario');
        $name = $this->ask('Nombre del usuario');
        $password = $this->secret('Contraseña del usuario');
        $roleName = $this->option('role') ?: $this->ask('Rol a asignar');

        try {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $this->error("Rol '{$roleName}' no encontrado");
                return;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);

            $user->assignRole($role);
            
            $this->info("Usuario creado y rol '{$roleName}' asignado correctamente");
            
        } catch (\Exception $e) {
            $this->error("Error al crear usuario: {$e->getMessage()}");
        }
    }

    private function showPermissionMatrix()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('_', $permission->name)[0];
        });

        $this->info('=== MATRIZ DE PERMISOS ===');

        foreach ($permissions as $category => $perms) {
            $this->info("\n--- CATEGORÍA: {$category} ---");
            
            $headers = ['Permiso'];
            foreach ($roles as $role) {
                $headers[] = $role->name;
            }

            $rows = [];
            foreach ($perms as $permission) {
                $row = [$permission->name];
                foreach ($roles as $role) {
                    $row[] = $role->hasPermissionTo($permission->name) ? '✓' : '✗';
                }
                $rows[] = $row;
            }

            $this->table($headers, $rows);
        }
    }

    private function assignPermissionsToRole(Role $role)
    {
        $permissions = Permission::pluck('name')->toArray();
        $selectedPermissions = [];

        $this->info("Permisos disponibles:");
        foreach ($permissions as $index => $permission) {
            $this->line("  {$index}) {$permission}");
        }

        while (true) {
            $selection = $this->ask('Ingrese número de permiso (o "done" para terminar)');
            
            if ($selection === 'done') {
                break;
            }

            if (is_numeric($selection) && isset($permissions[$selection])) {
                $selectedPermissions[] = $permissions[$selection];
                $this->info("Agregado: {$permissions[$selection]}");
            } else {
                $this->error("Selección inválida");
            }
        }

        if (!empty($selectedPermissions)) {
            $role->syncPermissions($selectedPermissions);
            $this->info("Asignados " . count($selectedPermissions) . " permisos al rol {$role->name}");
        }
    }

    private function showHelp()
    {
        $this->info('Uso: php artisan permissions:manage {acción} [opciones]');
        $this->info('');
        $this->info('Acciones disponibles:');
        $this->info('  create-role        Crear un nuevo rol');
        $this->info('  assign-permission  Asignar permiso a rol');
        $this->info('  list-roles         Listar todos los roles');
        $this->info('  list-permissions   Listar todos los permisos');
        $this->info('  sync              Sincronizar permisos del sistema');
        $this->info('  create-user       Crear usuario con rol');
        $this->info('  matrix            Mostrar matriz de permisos');
        $this->info('');
        $this->info('Ejemplos:');
        $this->info('  php artisan permissions:manage create-role --role=editor --display="Editor"');
        $this->info('  php artisan permissions:manage assign-permission --role=editor --permission=edit_posts');
        $this->info('  php artisan permissions:manage list-roles');
        $this->info('  php artisan permissions:manage sync');
    }
}