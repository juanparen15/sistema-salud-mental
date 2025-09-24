<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('admin')
    ->group(function () {
        
        // Ruta para manejar cambios de permisos via AJAX
        Route::post('/role-permissions/manage-permissions/update', [
            App\Filament\Resources\RolePermissionResource\Pages\ManagePermissions::class,
            'handlePermissionChanges'
        ])->name('admin.permissions.update');
        
        // Ruta para exportar matriz de permisos
        Route::get('/role-permissions/manage-permissions/export', [
            App\Filament\Resources\RolePermissionResource\Pages\ManagePermissions::class,
            'exportMatrix'
        ])->name('admin.permissions.export');
        
        // Ruta para sincronizar permisos del sistema
        Route::post('/role-permissions/manage-permissions/sync', [
            App\Filament\Resources\RolePermissionResource\Pages\ManagePermissions::class,
            'syncPermissions'
        ])->name('admin.permissions.sync');
    });