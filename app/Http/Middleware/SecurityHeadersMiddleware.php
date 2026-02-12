<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware global que agrega Security Headers a todas las respuestas HTTP.
 *
 * Protege contra:
 * - Clickjacking (X-Frame-Options)
 * - MIME sniffing (X-Content-Type-Options)
 * - XSS (X-XSS-Protection)
 * - Information leakage (Referrer-Policy)
 * - Acceso a APIs del dispositivo (Permissions-Policy)
 */
class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevenir clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevenir MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protección XSS del navegador
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Controlar referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restringir APIs del dispositivo
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS solo en producción (cuando se sirve por HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
