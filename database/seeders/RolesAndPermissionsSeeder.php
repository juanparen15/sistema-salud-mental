<?php

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;
// use App\Models\User;
// use Illuminate\Support\Facades\Hash;

// class RolesAndPermissionsSeeder extends Seeder
// {
//     public function run()
//     {
//         // Reset cached roles and permissions
//         app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

//         // Crear permisos
//         $permissions = [
//             // Permisos de Pacientes
//             'view_patients',
//             'view_any_patients', 
//             'create_patients',
//             'edit_patients',
//             'delete_patients',
//             'import_patients',
//             'export_patients',

//             // Permisos de Seguimientos
//             'view_followups',
//             'view_all_followups',
//             'view_any_followups',
//             'create_followups',
//             'edit_followups',
//             'edit_all_followups',
//             'delete_followups',
//             'export_followups',
//             'view_sensitive_info',

//             // Permisos de Reportes
//             'view_reports',
//             'generate_reports',
//             'view_analytics',
//             'export_reports',

//             // Permisos de Sistema
//             'manage_users',
//             'manage_roles',
//             'view_system_logs',
//             'manage_settings',
//         ];

//         foreach ($permissions as $permission) {
//             Permission::create(['name' => $permission]);
//         }

//         // Crear roles y asignar permisos

//         // Rol: Super Admin
//         $superAdmin = Role::create(['name' => 'super_admin']);
//         $superAdmin->givePermissionTo(Permission::all());

//         // Rol: Administrador
//         $admin = Role::create(['name' => 'admin']);
//         $admin->givePermissionTo([
//             'view_patients', 'view_any_patients', 'create_patients', 'edit_patients', 'delete_patients',
//             'import_patients', 'export_patients',
//             'view_followups', 'view_all_followups', 'view_any_followups', 'create_followups', 
//             'edit_followups', 'edit_all_followups', 'delete_followups', 'export_followups', 
//             'view_sensitive_info',
//             'view_reports', 'generate_reports', 'view_analytics', 'export_reports',
//             'manage_users', 'view_system_logs', 'manage_settings'
//         ]);

//         // Rol: Coordinador
//         $coordinator = Role::create(['name' => 'coordinator']);
//         $coordinator->givePermissionTo([
//             'view_patients', 'view_any_patients', 'create_patients', 'edit_patients',
//             'import_patients', 'export_patients',
//             'view_followups', 'view_all_followups', 'view_any_followups', 'create_followups',
//             'edit_followups', 'edit_all_followups', 'delete_followups', 'export_followups',
//             'view_sensitive_info',
//             'view_reports', 'generate_reports', 'view_analytics', 'export_reports'
//         ]);

//         // Rol: Psicólogo
//         $psychologist = Role::create(['name' => 'psychologist']);
//         $psychologist->givePermissionTo([
//             'view_patients', 'view_any_patients', 'create_patients', 'edit_patients',
//             'view_followups', 'view_any_followups', 'create_followups', 'edit_followups',
//             'view_sensitive_info',
//             'view_reports', 'view_analytics'
//         ]);

//         // Rol: Trabajador Social
//         $socialWorker = Role::create(['name' => 'social_worker']);
//         $socialWorker->givePermissionTo([
//             'view_patients', 'view_any_patients', 'create_patients', 'edit_patients',
//             'view_followups', 'view_any_followups', 'create_followups', 'edit_followups',
//             'view_reports', 'view_analytics'
//         ]);

//         // Rol: Auxiliar
//         $assistant = Role::create(['name' => 'assistant']);
//         $assistant->givePermissionTo([
//             'view_patients', 'view_any_patients', 'create_patients',
//             'view_followups', 'view_any_followups', 'create_followups'
//         ]);

//         // Crear usuario administrador por defecto
//         $adminUser = User::create([
//             'name' => 'Administrador Sistema',
//             'email' => 'admin@saludmental.gov.co',
//             'email_verified_at' => now(),
//             'password' => Hash::make('admin123'),
//         ]);
//         $adminUser->assignRole('admin');

//         // Crear usuario coordinador por defecto  
//         $coordinatorUser = User::create([
//             'name' => 'Coordinador Salud Mental',
//             'email' => 'coordinador@saludmental.gov.co', 
//             'email_verified_at' => now(),
//             'password' => Hash::make('coord123'),
//         ]);
//         $coordinatorUser->assignRole('coordinator');

//         // Crear usuario psicólogo por defecto
//         $psychologistUser = User::create([
//             'name' => 'Dr. Juan Pérez',
//             'email' => 'psicologo@saludmental.gov.co',
//             'email_verified_at' => now(), 
//             'password' => Hash::make('psico123'),
//         ]);
//         $psychologistUser->assignRole('psychologist');

//         $this->command->info('Roles y permisos creados exitosamente');
//         $this->command->info('Usuarios por defecto:');
//         $this->command->info('Admin: admin@saludmental.gov.co / admin123');
//         $this->command->info('Coordinador: coordinador@saludmental.gov.co / coord123');  
//         $this->command->info('Psicólogo: psicologo@saludmental.gov.co / psico123');
//     }
// }

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
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
            'view_analytics',
            'export_reports',

            // Permisos de Sistema
            'manage_users',
            'manage_roles',
            'view_system_logs',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles y asignar permisos

        // Rol: Super Admin
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Rol: Administrador
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_patients',
            'view_any_patients',
            'create_patients',
            'edit_patients',
            'delete_patients',
            'import_patients',
            'export_patients',
            'view_followups',
            'view_all_followups',
            'view_any_followups',
            'create_followups',
            'edit_followups',
            'edit_all_followups',
            'delete_followups',
            'export_followups',
            'view_reports',
            'generate_reports',
            'view_analytics',
            'export_reports',
            'manage_users',
            'view_system_logs',
            'manage_settings'
        ]);

        // Rol: Coordinador
        $coordinator = Role::create(['name' => 'coordinator']);
        $coordinator->givePermissionTo([
            'view_patients',
            'view_any_patients',
            'create_patients',
            'edit_patients',
            'import_patients',
            'export_patients',
            'view_followups',
            'view_all_followups',
            'view_any_followups',
            'create_followups',
            'edit_followups',
            'edit_all_followups',
            'delete_followups',
            'export_followups',
            'view_reports',
            'generate_reports',
            'view_analytics',
            'export_reports'
        ]);

        // Rol: Psicólogo
        $psychologist = Role::create(['name' => 'psychologist']);
        $psychologist->givePermissionTo([
            'view_patients',
            'view_any_patients',
            'create_patients',
            'edit_patients',
            'view_followups',
            'view_any_followups',
            'create_followups',
            'edit_followups',
            'view_reports',
            'view_analytics'
        ]);

        // Rol: Trabajador Social
        $socialWorker = Role::create(['name' => 'social_worker']);
        $socialWorker->givePermissionTo([
            'view_patients',
            'view_any_patients',
            'create_patients',
            'edit_patients',
            'view_followups',
            'view_any_followups',
            'create_followups',
            'edit_followups',
            'view_reports',
            'view_analytics'
        ]);

        // Rol: Auxiliar
        $assistant = Role::create(['name' => 'assistant']);
        $assistant->givePermissionTo([
            'view_patients',
            'view_any_patients',
            'create_patients',
            'view_followups',
            'view_any_followups',
            'create_followups'
        ]);

        // Crear usuarios por defecto solo si no existen
        if (!User::where('email', 'admin@saludmental.gov.co')->exists()) {
            $adminUser = User::create([
                'name' => 'Administrador Sistema',
                'email' => 'admin@saludmental.gov.co',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
            ]);
            $adminUser->assignRole('admin');
        }

        if (!User::where('email', 'coordinador@saludmental.gov.co')->exists()) {
            $coordinatorUser = User::create([
                'name' => 'Coordinador Salud Mental',
                'email' => 'coordinador@saludmental.gov.co',
                'email_verified_at' => now(),
                'password' => Hash::make('coord123'),
            ]);
            $coordinatorUser->assignRole('coordinator');
        }

        if (!User::where('email', 'psicologo@saludmental.gov.co')->exists()) {
            $psychologistUser = User::create([
                'name' => 'Dr. Juan Pérez',
                'email' => 'psicologo@saludmental.gov.co',
                'email_verified_at' => now(),
                'password' => Hash::make('psico123'),
            ]);
            $psychologistUser->assignRole('psychologist');
        }

        if (!User::where('email', 'trabajador@saludmental.gov.co')->exists()) {
            $socialWorkerUser = User::create([
                'name' => 'Ana María López',
                'email' => 'trabajador@saludmental.gov.co',
                'email_verified_at' => now(),
                'password' => Hash::make('social123'),
            ]);
            $socialWorkerUser->assignRole('social_worker');
        }

        if (!User::where('email', 'auxiliar@saludmental.gov.co')->exists()) {
            $assistantUser = User::create([
                'name' => 'Carlos Rodríguez',
                'email' => 'auxiliar@saludmental.gov.co',
                'email_verified_at' => now(),
                'password' => Hash::make('aux123'),
            ]);
            $assistantUser->assignRole('assistant');
        }

        $this->command->info('Roles y permisos creados exitosamente');
        $this->command->info('Usuarios por defecto creados:');
        $this->command->info('');
        $this->command->info('👨‍💼 ADMIN:');
        $this->command->info('  Email: admin@saludmental.gov.co');
        $this->command->info('  Contraseña: admin123');
        $this->command->info('  Permisos: Acceso completo al sistema');
        $this->command->info('');
        $this->command->info('👨‍🏫 COORDINADOR:');
        $this->command->info('  Email: coordinador@saludmental.gov.co');
        $this->command->info('  Contraseña: coord123');
        $this->command->info('  Permisos: Gestión completa de pacientes y seguimientos');
        $this->command->info('');
        $this->command->info('👨‍⚕️ PSICÓLOGO:');
        $this->command->info('  Email: psicologo@saludmental.gov.co');
        $this->command->info('  Contraseña: psico123');
        $this->command->info('  Permisos: Seguimientos y evaluaciones');
        $this->command->info('');
        $this->command->info('👨‍💼 TRABAJADOR SOCIAL:');
        $this->command->info('  Email: trabajador@saludmental.gov.co');
        $this->command->info('  Contraseña: social123');
        $this->command->info('  Permisos: Gestión de casos y seguimientos');
        $this->command->info('');
        $this->command->info('👨‍💻 AUXILIAR:');
        $this->command->info('  Email: auxiliar@saludmental.gov.co');
        $this->command->info('  Contraseña: aux123');
        $this->command->info('  Permisos: Registro básico de pacientes');

        // Crear algunos permisos adicionales útiles
        $additionalPermissions = [
            'view_dashboard',
            'view_statistics',
            'manage_followup_types',
            'bulk_actions'
        ];

        foreach ($additionalPermissions as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }

        // Asignar permisos adicionales a roles apropiados
        $admin->givePermissionTo($additionalPermissions);
        $coordinator->givePermissionTo(['view_dashboard', 'view_statistics', 'bulk_actions']);
        $psychologist->givePermissionTo(['view_dashboard', 'view_statistics']);
        $socialWorker->givePermissionTo(['view_dashboard', 'view_statistics']);
        $assistant->givePermissionTo(['view_dashboard']);

        $this->command->info('');
        $this->command->info('✅ Configuración de roles y permisos completada');
    }
}
