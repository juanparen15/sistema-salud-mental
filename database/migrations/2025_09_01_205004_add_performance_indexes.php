<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Índices para búsquedas frecuentes en pacientes
        Schema::table('patients', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'patients_status_created_idx');
            $table->index(['document_type', 'document_number'], 'patients_document_idx');
            $table->index(['gender', 'birth_date'], 'patients_demographics_idx');
        });

        // Índices para seguimientos con filtros comunes
        Schema::table('monthly_followups', function (Blueprint $table) {
            $table->index(['year', 'month', 'status'], 'followups_period_status_idx');
            $table->index(['followupable_type', 'followupable_id', 'followup_date'], 'followups_morphable_date_idx');
            $table->index(['next_followup', 'status'], 'followups_next_status_idx');
        });

        // Índices para casos por tipo
        Schema::table('mental_disorders', function (Blueprint $table) {
            $table->index(['status', 'admission_date'], 'mental_disorders_status_date_idx');
            $table->index(['diagnosis_code', 'admission_type'], 'mental_disorders_diagnosis_idx');
        });

        Schema::table('suicide_attempts', function (Blueprint $table) {
            $table->index(['status', 'event_date'], 'suicide_attempts_status_date_idx');
            $table->index(['attempt_number', 'status'], 'suicide_attempts_number_status_idx');
        });

        Schema::table('substance_consumptions', function (Blueprint $table) {
            $table->index(['status', 'admission_date'], 'substance_consumptions_status_date_idx');
            $table->index(['consumption_level', 'status'], 'substance_consumptions_level_status_idx');
        });

        // Índices para permisos (si no existen)
        // if (Schema::hasTable('model_has_permissions')) {
        //     DB::statement('CREATE INDEX IF NOT EXISTS model_has_permissions_model_id_model_type_index ON model_has_permissions(model_id, model_type)');
        // }

        // if (Schema::hasTable('model_has_roles')) {
        //     DB::statement('CREATE INDEX IF NOT EXISTS model_has_roles_model_id_model_type_index ON model_has_roles(model_id, model_type)');
        // }
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_status_created_idx');
            $table->dropIndex('patients_document_idx');
            $table->dropIndex('patients_demographics_idx');
        });

        Schema::table('monthly_followups', function (Blueprint $table) {
            $table->dropIndex('followups_period_status_idx');
            $table->dropIndex('followups_morphable_date_idx');
            $table->dropIndex('followups_next_status_idx');
        });

        Schema::table('mental_disorders', function (Blueprint $table) {
            $table->dropIndex('mental_disorders_status_date_idx');
            $table->dropIndex('mental_disorders_diagnosis_idx');
        });

        Schema::table('suicide_attempts', function (Blueprint $table) {
            $table->dropIndex('suicide_attempts_status_date_idx');
            $table->dropIndex('suicide_attempts_number_status_idx');
        });

        Schema::table('substance_consumptions', function (Blueprint $table) {
            $table->dropIndex('substance_consumptions_status_date_idx');
            $table->dropIndex('substance_consumptions_level_status_idx');
        });
    }
};