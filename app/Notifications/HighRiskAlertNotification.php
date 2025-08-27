<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Patient;

class HighRiskAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $patient;
    protected $riskType;
    protected $details;
    
    public function __construct(Patient $patient, string $riskType, array $details = [])
    {
        $this->patient = $patient;
        $this->riskType = $riskType;
        $this->details = $details;
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->error()
            ->subject('⚠️ ALERTA DE ALTO RIESGO - ' . $this->patient->full_name)
            ->greeting('¡ATENCIÓN URGENTE!')
            ->line('Se ha detectado una situación de alto riesgo que requiere atención inmediata.')
            ->line('**Paciente:** ' . $this->patient->full_name)
            ->line('**Documento:** ' . $this->patient->document_number)
            ->line('**Tipo de Riesgo:** ' . $this->getRiskTypeLabel());
        
        foreach ($this->details as $key => $value) {
            $message->line('**' . ucfirst($key) . ':** ' . $value);
        }
        
        return $message
            ->action('Ver Paciente', url('/admin/patients/' . $this->patient->id))
            ->line('Por favor, tome acción inmediata sobre este caso.')
            ->salutation('Sistema de Alerta Temprana');
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->full_name,
            'document' => $this->patient->document_number,
            'risk_type' => $this->riskType,
            'details' => $this->details,
            'message' => 'Alerta de alto riesgo detectada',
            'severity' => 'high',
        ];
    }
    
    protected function getRiskTypeLabel(): string
    {
        return match($this->riskType) {
            'multiple_attempts' => 'Múltiples Intentos de Suicidio',
            'recent_attempt' => 'Intento de Suicidio Reciente',
            'high_consumption' => 'Consumo de Alto Riesgo',
            'psychotic_episode' => 'Episodio Psicótico',
            'treatment_abandonment' => 'Abandono de Tratamiento',
            default => 'Riesgo No Especificado'
        };
    }
}