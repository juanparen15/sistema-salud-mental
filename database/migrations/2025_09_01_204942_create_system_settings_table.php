<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['group', 'is_public']);
        });

        // Insertar configuraciones por defecto
        DB::table('system_settings')->insert([
            [
                'key' => 'followup_reminder_days',
                'value' => json_encode(7),
                'type' => 'integer',
                'group' => 'followups',
                'description' => 'Días antes para enviar recordatorio de seguimiento',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'critical_followup_days',
                'value' => json_encode(15),
                'type' => 'integer',
                'group' => 'followups',
                'description' => 'Días para considerar un seguimiento como crítico',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_patients_per_user',
                'value' => json_encode(50),
                'type' => 'integer',
                'group' => 'assignments',
                'description' => 'Máximo número de pacientes por usuario',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_assign_patients',
                'value' => json_encode(false),
                'type' => 'boolean',
                'group' => 'assignments',
                'description' => 'Asignar pacientes automáticamente',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'system_name',
                'value' => json_encode('Sistema de Salud Mental'),
                'type' => 'string',
                'group' => 'general',
                'description' => 'Nombre del sistema',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};