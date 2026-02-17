<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware que verifica si el usuario tiene una licencia activa.
 * 
 * - SuperAdmin: siempre pasa
 * - Admin: debe tener una licencia activa propia
 * - Usuario: hereda la licencia de su Admin creador
 * 
 * Si la licencia está expirada, se cierra la sesión y se redirige al login con mensaje.
 */
class CheckLicenseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // SuperAdmin nunca es bloqueado
        if ($user->role === 'SuperAdmin') {
            return $next($request);
        }

        // Verificar licencia activa
        if (!$user->hasActiveLicense()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('license_expired', 
                'No tienes una licencia activa en este momento'
            );
        }

        return $next($request);
    }
}
