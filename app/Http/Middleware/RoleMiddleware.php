<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario autenticado tenga uno de los roles permitidos.
 *
 * Uso en rutas:
 *   Route::middleware('role:SuperAdmin,Admin')->group(...)
 *   Route::get('/admin', ...)->middleware('role:SuperAdmin');
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Roles permitidos (separados por coma en la definición de ruta)
     */
    public function handle(Request $request, Closure $next, string...$roles): Response
    {
        // Verificar que el usuario esté autenticado
        if (!$request->user()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No autenticado.'], 401)
                : redirect()->route('login');
        }

        // Verificar que el usuario tenga uno de los roles permitidos
        if (!in_array($request->user()->role, $roles, true)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No tienes permiso para acceder a este recurso.'], 403)
                : abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}
