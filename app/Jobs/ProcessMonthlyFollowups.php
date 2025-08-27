<?php

namespace App\Jobs;

use App\Models\MonthlyFollowup;
use App\Models\User;
use App\Notifications\FollowupReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ProcessMonthlyFollowups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(): void
    {
        // Buscar seguimientos pendientes para hoy
        $todayFollowups = MonthlyFollowup::where('status', 'pending')
            ->whereDate('followup_date', Carbon::today())
            ->get();
        
        foreach ($todayFollowups as $followup) {
            // Notificar a todos los usuarios con rol de profesional o coordinador
            $users = User::role(['professional', 'coordinator'])->get();
            
            foreach ($users as $user) {
                $user->notify(new FollowupReminderNotification($followup));
            }
        }
        
        // Buscar seguimientos vencidos
        $overdueFollowups = MonthlyFollowup::where('status', 'pending')
            ->whereDate('followup_date', '<', Carbon::today())
            ->get();
        
        foreach ($overdueFollowups as $followup) {
            // Marcar como vencido y notificar
            $followup->update(['status' => 'overdue']);
            
            // Notificar a coordinadores sobre seguimientos vencidos
            $coordinators = User::role('coordinator')->get();
            foreach ($coordinators as $coordinator) {
                $coordinator->notify(new FollowupReminderNotification($followup));
            }
        }
    }
}