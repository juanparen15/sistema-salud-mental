<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sistema de Salud Mental - Configuración
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el sistema de salud mental
    |
    */

    // Configuración general del sistema
    'system' => [
        'name' => 'Sistema de Información de Salud Mental',
        'abbreviation' => 'SISAM',
        'version' => '1.0.0',
        'institution' => env('HEALTH_INSTITUTION', 'Secretaría de Salud'),
        'contact_email' => env('HEALTH_CONTACT_EMAIL', 'salud.mental@gobierno.gov.co'),
        'support_phone' => env('HEALTH_SUPPORT_PHONE', '(601) 123-4567'),
    ],

    // Configuración de seguimientos
    'followups' => [
        // Días máximos sin seguimiento antes de alerta
        'max_days_without_followup' => env('MAX_DAYS_WITHOUT_FOLLOWUP', 45),
        
        // Días para editar un seguimiento después de creado
        'edit_window_days' => env('FOLLOWUP_EDIT_WINDOW_DAYS', 7),
        
        // Recordatorios automáticos de citas
        'appointment_reminders' => [
            'enabled' => env('APPOINTMENT_REMINDERS_ENABLED', true),
            'days_before' => [1, 3, 7], // Días antes para enviar recordatorios
        ],
        
        // Estados de ánimo disponibles
        'mood_states' => [
            'Muy Bueno' => ['color' => 'success', 'icon' => 'smile'],
            'Bueno' => ['color' => 'primary', 'icon' => 'smile'],
            'Regular' => ['color' => 'warning', 'icon' => 'neutral'],
            'Malo' => ['color' => 'danger', 'icon' => 'sad'],
            'Muy Malo' => ['color' => 'gray', 'icon' => 'very-sad'],
        ],
    ],

    // Configuración de riesgos
    'risks' => [
        'levels' => [
            'low' => ['label' => 'Bajo', 'color' => 'green', 'priority' => 1],
            'medium' => ['label' => 'Medio', 'color' => 'yellow', 'priority' => 2],
            'high' => ['label' => 'Alto', 'color' => 'orange', 'priority' => 3],
            'critical' => ['label' => 'Crítico', 'color' => 'red', 'priority' => 4],
        ],
        
        // Escalamiento automático de casos críticos
        'auto_escalation' => [
            'enabled' => env('AUTO_ESCALATION_ENABLED', true),
            'critical_roles' => ['admin', 'coordinator'], // Roles a notificar
            'notification_methods' => ['email', 'system'], // Métodos de notificación
        ],

        // Categorías de sustancias
        'substances' => [
            'Alcohol' => ['category' => 'legal', 'risk_multiplier' => 1.2],
            'Marihuana' => ['category' => 'ilegal', 'risk_multiplier' => 1.5],
            'Cocaína' => ['category' => 'ilegal', 'risk_multiplier' => 2.0],
            'Basuco' => ['category' => 'ilegal', 'risk_multiplier' => 2.5],
            'Heroína' => ['category' => 'ilegal', 'risk_multiplier' => 3.0],
            'Medicamentos' => ['category' => 'farmacológica', 'risk_multiplier' => 1.8],
            'Otras' => ['category' => 'desconocida', 'risk_multiplier' => 1.5],
        ],
    ],

    // Configuración de reportes
    'reports' => [
        // Generación automática de reportes
        'auto_generate' => [
            'monthly' => env('AUTO_MONTHLY_REPORTS', true),
            'quarterly' => env('AUTO_QUARTERLY_REPORTS', true),
            'annual' => env('AUTO_ANNUAL_REPORTS', true),
        ],
        
        // Formatos de exportación disponibles
        'export_formats' => ['xlsx', 'csv', 'pdf'],
        
        // Campos sensibles que requieren permisos especiales
        'sensitive_fields' => [
            'suicide_method',
            'substance_type',
            'consumption_details',
            'personal_observations',
        ],

        // Retención de reportes (meses)
        'retention_months' => env('REPORTS_RETENTION_MONTHS', 24),
    ],

    // Configuración de importación/exportación
    'import' => [
        'max_file_size_mb' => env('IMPORT_MAX_SIZE_MB', 10),
        'allowed_extensions' => ['xlsx', 'xls', 'csv'],
        'batch_size' => env('IMPORT_BATCH_SIZE', 100),
        'timeout_seconds' => env('IMPORT_TIMEOUT', 300),
        
        // Mapeo de columnas alternativas
        'column_mappings' => [
            'identification_number' => ['identificacion', 'cedula', 'documento'],
            'first_name' => ['nombres', 'primer_nombre', 'nombre'],
            'last_name' => ['apellidos', 'primer_apellido', 'apellido'],
            'gender' => ['sexo', 'genero'],
            'phone' => ['telefono', 'celular', 'movil'],
            'email' => ['correo', 'email', 'correo_electronico'],
        ],
    ],

    // Configuración de notificaciones
    'notifications' => [
        'channels' => ['mail', 'database', 'broadcast'],
        
        'types' => [
            'critical_risk' => [
                'enabled' => true,
                'channels' => ['mail', 'database'],
                'immediate' => true,
            ],
            'missed_appointment' => [
                'enabled' => true,
                'channels' => ['database'],
                'delay_hours' => 24,
            ],
            'monthly_summary' => [
                'enabled' => true,
                'channels' => ['mail'],
                'schedule' => 'monthly',
            ],
        ],
    ],

    // Configuración de auditoría
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'events' => [
            'patient_created',
            'patient_updated', 
            'patient_deleted',
            'followup_created',
            'followup_updated',
            'critical_risk_detected',
            'data_exported',
            'data_imported',
        ],
        'retention_days' => env('AUDIT_RETENTION_DAYS', 365),
    ],

    // Configuración de dashboard
    'dashboard' => [
        'refresh_interval_seconds' => env('DASHBOARD_REFRESH_SECONDS', 300),
        'widgets' => [
            'total_patients' => true,
            'active_followups' => true,
            'risk_summary' => true,
            'recent_activities' => true,
            'monthly_statistics' => true,
        ],
        
        // Alertas del dashboard
        'alerts' => [
            'high_risk_patients' => [
                'threshold' => 5,
                'color' => 'danger',
            ],
            'overdue_followups' => [
                'threshold' => 10,
                'color' => 'warning',
            ],
        ],
    ],

    // Configuración de respaldo
    'backup' => [
        'enabled' => env('AUTO_BACKUP_ENABLED', true),
        'frequency' => env('BACKUP_FREQUENCY', 'daily'), // daily, weekly, monthly
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'include_uploads' => true,
    ],

    // Integraciones externas
    'integrations' => [
        'sispro' => [
            'enabled' => env('SISPRO_INTEGRATION', false),
            'endpoint' => env('SISPRO_ENDPOINT'),
            'api_key' => env('SISPRO_API_KEY'),
        ],
        'rips' => [
            'enabled' => env('RIPS_INTEGRATION', false),
            'export_format' => 'xml',
        ],
    ],

    // Configuración de municipios (puede expandirse según la región)
    'municipalities' => [
        'Puerto Boyacá', 'Bogotá D.C.', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena',
        'Cúcuta', 'Bucaramanga', 'Pereira', 'Santa Marta', 'Ibagué',
        'Pasto', 'Manizales', 'Neiva', 'Villavicencio', 'Armenia',
        'Montería', 'Sincelejo', 'Popayán', 'Valledupar', 'Quibdó',
        'Florencia', 'Tunja', 'Mocoa', 'Yopal', 'Riohacha',
        'San José del Guaviare', 'Leticia', 'Puerto Carreño', 'Mitú', 'Inírida'
    ],
];