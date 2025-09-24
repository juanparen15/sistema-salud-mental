<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000003_create_permission_categories_table.php
// ================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('icon')->default('heroicon-o-cog-6-tooth');
            $table->string('color', 7)->default('#6366f1');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insertar categorías por defecto
        DB::table('permission_categories')->insert([
            [
                'name' => 'dashboard',
                'display_name' => 'Dashboard',
                'description' => 'Permisos relacionados con el panel principal',
                'icon' => 'heroicon-o-home',
                'color' => '#3b82f6',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'pacientes',
                'display_name' => 'Pacientes',
                'description' => 'Gestión de pacientes y su información',
                'icon' => 'heroicon-o-users',
                'color' => '#10b981',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'seguimientos',
                'display_name' => 'Seguimientos',
                'description' => 'Gestión de seguimientos y citas',
                'icon' => 'heroicon-o-calendar',
                'color' => '#f59e0b',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'reportes',
                'display_name' => 'Reportes',
                'description' => 'Generación y visualización de reportes',
                'icon' => 'heroicon-o-chart-bar',
                'color' => '#8b5cf6',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'administracion',
                'display_name' => 'Administración',
                'description' => 'Gestión administrativa del sistema',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => '#ef4444',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sistema',
                'display_name' => 'Sistema',
                'description' => 'Configuración y mantenimiento del sistema',
                'icon' => 'heroicon-o-server',
                'color' => '#6b7280',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_categories');
    }
};
