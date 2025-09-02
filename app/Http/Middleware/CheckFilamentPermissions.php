<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckFilamentPermissions
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        $user = auth()->user();

        // Verificar que tenga al menos un rol válido
        if (!$user->hasAnyRole(['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant'])) {
            abort(403, 'No tienes un rol válido asignado para acceder al sistema.');
        }

        // Verificar que tenga permiso básico de dashboard
        if (!$user->can('view_dashboard')) {
            abort(403, 'No tienes permisos para acceder al panel administrativo.');
        }

        // Verificar acceso específico por ruta
        $this->checkRoutePermissions($request);

        return $next($request);
    }

    private function checkRoutePermissions(Request $request): void
    {
        $path = $request->path();
        $user = auth()->user();

        // Rutas que requieren permisos específicos
        $routePermissions = [
            'admin/users' => 'manage_users',
            'admin/role-permissions' => 'manage_roles',
            'admin/report-page' => 'view_reports',
            'admin/mental-disorders' => ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker'],
            'admin/suicide-attempts' => ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker'],
            'admin/substance-consumptions' => ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker'],
        ];

        foreach ($routePermissions as $route => $permission) {
            if (str_contains($path, $route)) {
                if (is_array($permission)) {
                    // Verificar roles
                    if (!$user->hasAnyRole($permission)) {
                        abort(403, 'No tienes el rol necesario para acceder a esta sección.');
                    }
                } else {
                    // Verificar permiso específico
                    if (!$user->can($permission)) {
                        abort(403, 'No tienes permisos para acceder a esta sección.');
                    }
                }
            }
        }
    }
}