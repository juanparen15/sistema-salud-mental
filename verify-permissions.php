<?php

// verify-permissions.php - Script de verificación independiente
echo "🔍 VERIFICACIÓN FINAL DEL SISTEMA DE PERMISOS\n";
echo "=============================================\n\n";

require_once 'vendor/autoload.php';

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

try {
    // Verificar conexión a BD
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✅ Conexión a base de datos: OK\n";
    
    // Verificar roles
    $roles = Role::count();
    echo "✅ Roles creados: {$roles}\n";
    
    // Verificar permisos
    $permissions = Permission::count();
    echo "✅ Permisos creados: {$permissions}\n";
    
    // Verificar usuarios
    $users = User::count();
    echo "✅ Usuarios creados: {$users}\n";
    
    // Verificar usuarios por defecto
    $defaultEmails = [
        'admin@saludmental.gov.co',
        'coordinador@saludmental.gov.co',
        'psicologo@saludmental.gov.co',
        'trabajador@saludmental.gov.co',
        'auxiliar@saludmental.gov.co'
    ];
    
    echo "\n👥 USUARIOS POR DEFECTO:\n";
    foreach ($defaultEmails as $email) {
        $user = User::where('email', $email)->first();
        if ($user) {
            $role = $user->roles->first()?->name ?? 'sin rol';
            echo "   ✅ {$email} - Rol: {$role}\n";
        } else {
            echo "   ❌ {$email} - NO ENCONTRADO\n";
        }
    }
    
    // Verificar recursos
    echo "\n🔧 VERIFICACIÓN DE RECURSOS:\n";
    $resources = [
        'PatientResource' => \App\Filament\Resources\PatientResource::class,
        'MentalDisorderResource' => \App\Filament\Resources\MentalDisorderResource::class,
        'SuicideAttemptResource' => \App\Filament\Resources\SuicideAttemptResource::class,
        'SubstanceConsumptionResource' => \App\Filament\Resources\SubstanceConsumptionResource::class,
        'MonthlyFollowupResource' => \App\Filament\Resources\MonthlyFollowupResource::class,
        'UserResource' => \App\Filament\Resources\UserResource::class,
        'RolePermissionResource' => \App\Filament\Resources\RolePermissionResource::class,
    ];
    
    foreach ($resources as $name => $class) {
        if (class_exists($class)) {
            echo "   ✅ {$name}: Disponible\n";
        } else {
            echo "   ❌ {$name}: NO ENCONTRADO\n";
        }
    }
    
    echo "\n🎉 VERIFICACIÓN COMPLETADA - Sistema listo para usar!\n";
    echo "🌐 Inicia el servidor: php artisan serve\n";
    echo "🔗 Accede a: http://127.0.0.1:8000/admin\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "💡 Ejecuta: php artisan mental-health:fix-permissions\n";
    exit(1);
}
?>