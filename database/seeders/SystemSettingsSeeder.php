<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Configuraciones de seguimientos
            [
                'key' => 'followup_reminder_days',
                'value' => 7,
                'type' => 'integer',
                'group' => 'followups',
                'description' => 'Días antes para enviar recordatorio de seguimiento',
            ],
            [
                'key' => 'critical_followup_days',
                'value' => 15,
                'type' => 'integer',
                'group' => 'followups',
                'description' => 'Días para considerar un seguimiento como crítico',
            ],
            [
                'key' => 'max_followups_per_month',
                'value' => 1,
                'type' => 'integer',
                'group' => 'followups',
                'description' => 'Máximo número de seguimientos por paciente por mes',
            ],

            // Configuraciones de asignaciones
            [
                'key' => 'max_patients_per_psychologist',
                'value' => 30,
                'type' => 'integer',
                'group' => 'assignments',
                'description' => 'Máximo número de pacientes por psicólogo',
            ],
            [
                'key' => 'max_patients_per_social_worker',
                'value' => 40,
                'type' => 'integer',
                'group' => 'assignments',
                'description' => 'Máximo número de pacientes por trabajador social',
            ],
            [
                'key' => 'auto_assign_new_patients',
                'value' => false,
                'type' => 'boolean',
                'group' => 'assignments',
                'description' => 'Asignar automáticamente nuevos pacientes',
            ],

            // Configuraciones de alertas
            [
                'key' => 'send_daily_alerts',
                'value' => true,
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Enviar alertas diarias automáticas',
            ],
            [
                'key' => 'alert_critical_cases_hours',
                'value' => 72,
                'type' => 'integer',
                'group' => 'notifications',
                'description' => 'Horas sin seguimiento para alertar casos críticos',
            ],

            // Configuraciones de sistema
            [
                'key' => 'system_maintenance_mode',
                'value' => false,
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'Modo de mantenimiento del sistema',
            ],
            [
                'key' => 'default_followup_status',
                'value' => 'pending',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Estado por defecto para nuevos seguimientos',
            ],

            // Configuraciones de reportes
            [
                'key' => 'auto_generate_monthly_reports',
                'value' => true,
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Generar reportes mensuales automáticamente',
            ],
            [
                'key' => 'report_retention_months',
                'value' => 24,
                'type' => 'integer',
                'group' => 'reports',
                'description' => 'Meses de retención para reportes generados',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => json_encode($setting['value']),
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                    'is_public' => false,
                ]
            );
        }
    }
}