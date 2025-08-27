<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CriticalRiskAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $alertData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $riskFactorsText = implode(', ', $this->alertData['risk_factors']);
        
        return (new MailMessage)
            ->subject('🚨 ALERTA CRÍTICA - Riesgo Alto Detectado')
            ->priority(1) // Prioridad alta
            ->greeting('Alerta Crítica de Salud Mental')
            ->line('Se ha detectado un caso de **RIESGO CRÍTICO** que requiere atención inmediata.')
            ->line('')
            ->line('**Detalles del Paciente:**')
            ->line("• Nombre: {$this->alertData['patient_name']}")
            ->line("• Identificación: {$this->alertData['patient_id']}")
            ->line("• Municipio: {$this->alertData['municipality']}")
            ->line("• Teléfono: " . ($this->alertData['contact_phone'] ?? 'No registrado'))
            ->line('')
            ->line('**Detalles del Seguimiento:**')
            ->line("• Fecha: {$this->alertData['followup_date']}")
            ->line("• Registrado por: {$this->alertData['created_by']}")
            ->line("• Factores de riesgo: {$riskFactorsText}")
            ->line('')
            ->line('⚠️ **ACCIÓN REQUERIDA:** Este caso requiere intervención inmediata. Por favor coordine la atención urgente del paciente.')
            ->action('Ver Detalles del Paciente', url("/admin/patients"))
            ->line('')
            ->line('Esta es una alerta automática del Sistema de Información de Salud Mental.')
            ->salutation('Equipo de Salud Mental');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'critical_risk',
            'title' => 'Riesgo Crítico Detectado',
            'message' => "Paciente {$this->alertData['patient_name']} presenta riesgo crítico",
            'patient_id' => $this->alertData['patient_id'],
            'patient_name' => $this->alertData['patient_name'],
            'municipality' => $this->alertData['municipality'],
            'risk_factors' => $this->alertData['risk_factors'],
            'followup_date' => $this->alertData['followup_date'],
            'created_by' => $this->alertData['created_by'],
            'priority' => 'critical',
            'icon' => 'exclamation-triangle',
            'color' => 'red',
            'action_url' => url("/admin/patients"),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'title' => '🚨 Riesgo Crítico',
            'message' => "{$this->alertData['patient_name']} requiere atención inmediata",
            'type' => 'critical',
            'patient_id' => $this->alertData['patient_id'],
            'sound' => 'alert'
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->alertData;
    }
}