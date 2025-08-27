<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientApiController;
use App\Http\Controllers\Api\MentalDisorderApiController;
use App\Http\Controllers\Api\SuicideAttemptApiController;
use App\Http\Controllers\Api\SubstanceConsumptionApiController;
use App\Http\Controllers\Api\FollowupApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\DashboardApiController;

Route::middleware('auth:sanctum')->group(function () {
    // Patients
    Route::apiResource('patients', PatientApiController::class);
    
    // Mental Disorders
    Route::apiResource('mental-disorders', MentalDisorderApiController::class);
    
    // Suicide Attempts
    Route::apiResource('suicide-attempts', SuicideAttemptApiController::class);
    
    // Substance Consumptions
    Route::apiResource('substance-consumptions', SubstanceConsumptionApiController::class);
    
    // Followups
    Route::apiResource('followups', FollowupApiController::class);
    Route::post('followups/bulk', [FollowupApiController::class, 'bulkCreate']);
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('monthly/{year}/{month}', [ReportApiController::class, 'monthly']);
        Route::get('statistics', [ReportApiController::class, 'statistics']);
        Route::post('export', [ReportApiController::class, 'export']);
    });
    
    // Dashboard
    Route::get('dashboard/stats', [DashboardApiController::class, 'stats']);
    Route::get('dashboard/recent-cases', [DashboardApiController::class, 'recentCases']);
    Route::get('dashboard/alerts', [DashboardApiController::class, 'alerts']);
    
    // User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});