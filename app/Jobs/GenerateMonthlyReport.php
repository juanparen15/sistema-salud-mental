<?php

namespace App\Jobs;

use App\Exports\MentalHealthExport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $month;
    protected $year;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($month = null, $year = null, $userId = null)
    {
        $this->month = $month ?? Carbon::now()->month;
        $this->year = $year ?? Carbon::now()->year;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $filename = "reporte_mensual_{$this->year}_{$this->month}.xlsx";
        
        // Generar el reporte
        Excel::store(
            new MentalHealthExport($this->month, $this->year),
            "reports/{$filename}",
            'public'
        );

        // Si hay un usuario específico, enviar notificación
        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                // Aquí puedes agregar lógica para enviar email o notificación
                // Mail::to($user->email)->send(new MonthlyReportGenerated($filename));
            }
        }

        Log::info("Reporte mensual generado: {$filename}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Error generando reporte mensual: " . $exception->getMessage());
    }
}