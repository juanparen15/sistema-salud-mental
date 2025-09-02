<?php

// ================================
// MIGRACIÓN: AGREGAR CAMPOS DE ASIGNACIÓN
// ================================

// database/migrations/2025_01_XX_add_assignment_fields_to_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos de asignación a pacientes
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario responsable del paciente');
            $table->foreignId('created_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que registró el paciente');
            $table->timestamp('assigned_at')->nullable()
                  ->comment('Fecha de asignación');
            
            $table->index(['assigned_to', 'status']);
            $table->index(['created_by_id', 'created_at']);
        });

        // Agregar campos de auditoría a trastornos mentales
        Schema::table('mental_disorders', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que registró el trastorno');
            $table->foreignId('updated_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Último usuario que modificó el registro');
            
            $table->index(['created_by_id', 'admission_date']);
        });

        // Agregar campos de auditoría a intentos de suicidio
        Schema::table('suicide_attempts', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que registró el intento');
            $table->foreignId('updated_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Último usuario que modificó el registro');
            
            $table->index(['created_by_id', 'event_date']);
        });

        // Agregar campos de auditoría a consumos SPA
        Schema::table('substance_consumptions', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que registró el consumo');
            $table->foreignId('updated_by_id')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Último usuario que modificó el registro');
            
            $table->index(['created_by_id', 'admission_date']);
        });

        // // Mejorar seguimientos mensuales
        // Schema::table('monthly_followups', function (Blueprint $table) {
        //     // Cambiar nombre de columna si existe
        //     if (Schema::hasColumn('monthly_followups', 'performed_by')) {
        //         $table->renameColumn('performed_by', 'performed_by_old');
        //     }
            
        //     $table->foreignId('performed_by')->nullable()
        //           ->constrained('users')
        //           ->onDelete('set null')
        //           ->comment('Usuario que realizó el seguimiento');
        //     $table->foreignId('updated_by_id')->nullable()
        //           ->constrained('users')
        //           ->onDelete('set null')
        //           ->comment('Último usuario que modificó el seguimiento');
            
        //     // Campos adicionales útiles
        //     $table->time('followup_time')->nullable()
        //           ->comment('Hora del seguimiento');
        //     $table->string('contact_method')->nullable()
        //           ->comment('Método de contacto utilizado');
        //     $table->integer('duration_minutes')->nullable()
        //           ->comment('Duración del seguimiento en minutos');
            
        //     $table->index(['performed_by', 'followup_date']);
        //     $table->index(['status', 'next_followup']);
        // });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['assigned_to', 'created_by_id', 'assigned_at']);
        });

        Schema::table('mental_disorders', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn(['created_by_id', 'updated_by_id']);
        });

        Schema::table('suicide_attempts', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn(['created_by_id', 'updated_by_id']);
        });

        Schema::table('substance_consumptions', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn(['created_by_id', 'updated_by_id']);
        });

        Schema::table('monthly_followups', function (Blueprint $table) {
            $table->dropForeign(['performed_by']);
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn([
                'performed_by', 'updated_by_id', 'followup_time', 
                'contact_method', 'duration_minutes'
            ]);
        });
    }
};
