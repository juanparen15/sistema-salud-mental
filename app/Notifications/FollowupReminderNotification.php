<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\MonthlyFollowup;

class FollowupReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $followup;
    
    public function __construct(MonthlyFollowup $followup)
    {
        $this->followup = $followup;
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        $patientName = $this->followup->followupable->patient->full_name ?? 'Paciente';
        $type = $this->getFollowupType();
        
        return (new MailMessage)
            ->subject('Recordatorio de Seguimiento - ' . $patientName)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Este es un recordatorio de que tiene un seguimiento pendiente.')
            ->line('**Paciente:** ' . $patientName)
            ->line('**Tipo:** ' . $type)
            ->line('**Fecha programada:** ' . $this->followup->followup_date->format('d/m/Y'))
            ->action('Ver Seguimiento', url('/admin/monthly-followups/' . $this->followup->id))
            ->line('Por favor, complete el seguimiento lo antes posible.')
            ->salutation('Sistema de Salud Mental');
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'followup_id' => $this->followup->id,
            'patient_name' => $this->followup->followupable->patient->full_name ?? 'Paciente',
            'type' => $this->getFollowupType(),
            'date' => $this->followup->followup_date->format('d/m/Y'),
            'message' => 'Tiene un seguimiento pendiente',
        ];
    }
    
    protected function getFollowupType(): string
    {
        return match($this->followup->followupable_type) {
            'App\Models\MentalDisorder' => 'Trastorno Mental',
            'App\Models\SuicideAttempt' => 'Intento de Suicidio',
            'App\Models\SubstanceConsumption' => 'Consumo SPA',
            default => 'Seguimiento'
        };
    }
}