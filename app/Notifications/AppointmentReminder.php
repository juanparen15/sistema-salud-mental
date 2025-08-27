<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $reminderData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $reminderData)
    {
        $this->reminderData = $reminderData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysText = $this->reminderData['days_ahead'] === 1 ? 'mañana' : 
                   "en {$this->reminderData['days_ahead']} días";
        
        return (new MailMessage)
            ->subject("📅 Recordatorio de Cita - {$this->reminderData['patient_name']}")
            ->greeting('Recordatorio de Cita Programada')
            ->line("Este es un recordatorio de que tiene una cita programada {$daysText}.")
            ->line('')
            ->line('**Detalles de la Cita:**')
            ->line("• Paciente: {$this->reminderData['patient_name']}")
            ->line("• Fecha y hora: {$this->reminderData['appointment_date']}")
            ->line("• Teléfono paciente: " . ($this->reminderData['patient_phone'] ?? 'No registrado'))
            ->line("• Responsable: {$this->reminderData['responsible_user']}")
            ->line('')
            ->line('Por favor confirme la asistencia del paciente y prepare los materiales necesarios para la consulta.')
            ->action('Ver Detalles', url('/admin/monthly-followups'))
            ->line('Recuerde contactar al paciente para confirmar su asistencia.')
            ->salutation('Sistema de Salud Mental');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'appointment_reminder',
            'title' => 'Recordatorio de Cita',
            'message' => "Cita con {$this->reminderData['patient_name']} en {$this->reminderData['days_ahead']} día(s)",
            'patient_name' => $this->reminderData['patient_name'],
            'appointment_date' => $this->reminderData['appointment_date'],
            'days_ahead' => $this->reminderData['days_ahead'],
            'priority' => 'normal',
            'icon' => 'calendar',
            'color' => 'blue',
            'action_url' => url('/admin/monthly-followups'),
        ];
    }
}