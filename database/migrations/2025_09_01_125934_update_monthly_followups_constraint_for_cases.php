<?php

// php artisan make:migration update_monthly_followups_constraint_for_cases

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_followups', function (Blueprint $table) {
            // Eliminar la restricción única anterior si existe
            try {
                $table->dropUnique('unique_followup');
            } catch (\Exception $e) {
                // Si no existe, continuar
            }
            
            // ✅ NUEVA RESTRICCIÓN: Un seguimiento por caso específico por mes
            // Esto permite que un paciente tenga múltiples seguimientos (uno por cada tipo de caso)
            $table->unique([
                'followupable_id',    // ID del caso específico (trastorno, suicidio, SPA)
                'followupable_type',  // Tipo de caso (MentalDisorder, SuicideAttempt, etc.)
                'year',               // Año
                'month'               // Mes
            ], 'unique_followup_per_case_month');
            
            // Índices adicionales para mejorar performance
            $table->index(['followupable_type', 'status'], 'idx_type_status');
            $table->index(['year', 'month', 'status'], 'idx_period_status');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_followups', function (Blueprint $table) {
            // Eliminar nueva restricción
            $table->dropUnique('unique_followup_per_case_month');
            $table->dropIndex('idx_type_status');
            $table->dropIndex('idx_period_status');
            
            // Restaurar restricción anterior (opcional)
            // $table->unique(['followupable_id', 'followupable_type', 'year', 'month'], 'unique_followup');
        });
    }
};