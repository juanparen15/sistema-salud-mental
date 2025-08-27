<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueFollowup extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $overdueData;

    public function __construct(array $overdueData)
    {
        $this->overdueData = $overdueData;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⏰ Seguimiento Vencido - {$this->overdueData['patient_name']}")
            ->greeting('Seguimiento Vencido')
            ->line("El paciente {$this->overdueData['patient_name']} no tiene seguimiento desde hace {$this->overdueData['days_since_last']} días.")
            ->line('')
            ->line('**Detalles:**')
            ->line("• Identificación: {$this->overdueData['patient_id']}")
            ->line("• Municipio: {$this->overdueData['municipality']}")
            ->line("• Último seguimiento: {$this->overdueData['last_followup_date']}")
            ->line('')
            ->line('Es recomendable programar un nuevo seguimiento para evaluar el estado actual del paciente.')
            ->action('Programar Seguimiento', url('/admin/monthly-followups/create'))
            ->salutation('Sistema de Salud Mental');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'overdue_followup',
            'title' => 'Seguimiento Vencido',
            'message' => "{$this->overdueData['patient_name']} sin seguimiento por {$this->overdueData['days_since_last']} días",
            'patient_name' => $this->overdueData['patient_name'],
            'patient_id' => $this->overdueData['patient_id'],
            'days_since_last' => $this->overdueData['days_since_last'],
            'priority' => 'medium',
            'icon' => 'clock',
            'color' => 'orange',
            'action_url' => url('/admin/monthly-followups/create'),
        ];
    }
}

// =============================================

class MonthlySummary extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("📊 Resumen Mensual - {$this->summary['period']}")
            ->greeting("Resumen Mensual de Salud Mental - {$this->summary['period']}")
            ->line('A continuación encuentra el resumen de actividades del mes anterior:')
            ->line('')
            ->line('**Estadísticas Generales:**')
            ->line("• Total de seguimientos: {$this->summary['total_followups']}")
            ->line("• Pacientes nuevos: {$this->summary['new_patients']}")
            ->line('')
            ->line('**Casos de Atención Especial:**')
            ->line("• Casos críticos (intentos): {$this->summary['critical_cases']}")
            ->line("• Casos de alto riesgo: {$this->summary['high_risk_cases']}")
            ->line("• Casos con consumo de sustancias: {$this->summary['substance_cases']}")
            ->line('')
            ->line('Este resumen le ayuda a tener una visión general del estado de la salud mental en su región.')
            ->action('Ver Dashboard Completo', url('/admin'))
            ->salutation('Sistema de Salud Mental');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'monthly_summary',
            'title' => 'Resumen Mensual',
            'message' => "Resumen de actividades para {$this->summary['period']}",
            'period' => $this->summary['period'],
            'total_followups' => $this->summary['total_followups'],
            'critical_cases' => $this->summary['critical_cases'],
            'priority' => 'low',
            'icon' => 'chart-bar',
            'color' => 'green',
            'action_url' => url('/admin'),
        ];
    }
}