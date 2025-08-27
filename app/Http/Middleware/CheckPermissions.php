<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();
        
        // Si es super admin, permitir todo
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Verificar si el usuario tiene al menos uno de los permisos requeridos
        $hasPermission = false;
        
        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Permisos insuficientes'], 403);
            }
            
            abort(403, 'No tienes permisos para realizar esta acción');
        }

        return $next($request);
    }
}