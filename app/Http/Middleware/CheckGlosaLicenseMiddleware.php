<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGlosaLicenseMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasGlosaAccess()) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Su licencia actual no cuenta con acceso habilitado para el Módulo de Glosa Aduanera (Data Stage).'
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 
                'Su licencia actual no cuenta con acceso habilitado para el Módulo de Glosa Aduanera (Data Stage). Contacte a su administrador para solicitar una actualización.'
            );
        }

        return $next($request);
    }
}
