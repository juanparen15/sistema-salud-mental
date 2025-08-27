<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\MonthlyFollowup;
use App\Observers\MonthlyFollowupObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el servicio de notificaciones como singleton
        $this->app->singleton(\App\Services\NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar el observer para MonthlyFollowup
        MonthlyFollowup::observe(MonthlyFollowupObserver::class);
    }
}