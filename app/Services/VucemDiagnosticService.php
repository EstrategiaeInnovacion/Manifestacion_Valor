<?php

namespace App\Services;

use App\Models\MvAcuse;
use App\Models\VucemErrorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VucemDiagnosticService
{
    /**
     * Analiza los logs de error de VUCEM y determina el estado del servicio.
     *
     * Umbrales:
     *   - tasa_error > 75% con al menos 3 intentos en 30 min → VUCEM caído
     *   - tasa_error > 30% con al menos 2 intentos en 30 min → Inestabilidad general
     *   - errores del usuario ≥ 3 en 24h pero sistema OK → posible problema local
     *   - ninguno de los anteriores → VUCEM operando normalmente
     *
     * @param int|null $userId       ID del usuario actual (para conteo personal)
     * @param int|null $applicantId  Solicitante involucrado (informativo, no usado en umbrales)
     */
    public function getDiagnostico(?int $userId = null, ?int $applicantId = null): array
    {
        $userId   = $userId ?? Auth::id();
        $hace30min = now()->subMinutes(30);
        $hace24h   = now()->subHours(24);

        // ── Métricas del sistema (todos los usuarios) ─────────────────────────
        $erroresSistema = VucemErrorLog::where('created_at', '>=', $hace30min)->count();

        // Envíos exitosos de MVE en los últimos 30 min (tabla mv_acuses)
        $exitososSistema = MvAcuse::where('fecha_envio', '>=', $hace30min)->count();

        $totalSistema  = $erroresSistema + $exitososSistema;
        $tasaError     = $totalSistema >= 3 ? ($erroresSistema / $totalSistema) : 0.0;

        // ── Métricas del usuario actual ───────────────────────────────────────
        $erroresUsuario = $userId
            ? VucemErrorLog::where('user_id', $userId)
                ->where('created_at', '>=', $hace24h)
                ->count()
            : 0;

        // ── Diagnóstico ───────────────────────────────────────────────────────
        if ($tasaError > 0.75 && $totalSistema >= 3) {
            return $this->respuesta(
                estado:  'VUCEM_CAIDO',
                icono:   'wifi-off',
                color:   'red',
                titulo:  'VUCEM está presentando fallas generalizadas',
                mensaje: 'Otros usuarios del sistema también reportan errores en este momento. '
                       . 'El servicio de VUCEM parece estar caído o con problemas graves.',
                accion:  'Espera 10–15 minutos antes de reintentar. '
                       . 'Si el problema persiste más de una hora, contacta a soporte técnico.',
                erroresSistema: $erroresSistema,
                exitososSistema: $exitososSistema,
                erroresUsuario: $erroresUsuario
            );
        }

        if ($tasaError > 0.30 && $totalSistema >= 2) {
            return $this->respuesta(
                estado:  'INTERMITENTE',
                icono:   'wifi',
                color:   'yellow',
                titulo:  'VUCEM presenta intermitencia',
                mensaje: 'El servicio de VUCEM está inestable. Algunos intentos fallan pero otros '
                       . 'tienen éxito. No es un problema exclusivo de tu cuenta.',
                accion:  'Puedes reintentar en 2–5 minutos. '
                       . 'Considera programar el envío para un horario de menor carga.',
                erroresSistema: $erroresSistema,
                exitososSistema: $exitososSistema,
                erroresUsuario: $erroresUsuario
            );
        }

        if ($erroresUsuario >= 3) {
            return $this->respuesta(
                estado:  'POSIBLE_LOCAL',
                icono:   'alert-triangle',
                color:   'orange',
                titulo:  'Posible problema de conectividad local',
                mensaje: "Llevas {$erroresUsuario} errores de conexión hoy, pero otros usuarios no "
                       . 'reportan problemas similares. El sistema VUCEM parece estar operando.',
                accion:  'Verifica tu conexión a internet. Si usas VPN o proxy, intenta '
                       . 'desactivarlo temporalmente. También puedes intentar más tarde.',
                erroresSistema: $erroresSistema,
                exitososSistema: $exitososSistema,
                erroresUsuario: $erroresUsuario
            );
        }

        return $this->respuesta(
            estado:  'OPERANDO',
            icono:   'check-circle',
            color:   'green',
            titulo:  'VUCEM parece estar operando normalmente',
            mensaje: 'No se detectaron fallas recientes en el sistema. '
                   . 'El error puede haber sido temporal.',
            accion:  'Intenta nuevamente. Si el error persiste, revisa que tus credenciales '
                   . '(certificado, llave privada y clave webservice) sean correctas.',
            erroresSistema: $erroresSistema,
            exitososSistema: $exitososSistema,
            erroresUsuario: $erroresUsuario
        );
    }

    private function respuesta(
        string $estado,
        string $icono,
        string $color,
        string $titulo,
        string $mensaje,
        string $accion,
        int    $erroresSistema,
        int    $exitososSistema,
        int    $erroresUsuario
    ): array {
        return [
            'estado'                => $estado,
            'icono'                 => $icono,
            'color'                 => $color,
            'titulo'                => $titulo,
            'mensaje'               => $mensaje,
            'accion'                => $accion,
            'errores_sistema_30min' => $erroresSistema,
            'exitosos_sistema_30min'=> $exitososSistema,
            'errores_usuario_24h'   => $erroresUsuario,
        ];
    }

    /**
     * Estado del sistema VUCEM para el banner automático del layout.
     * Resultado cacheado 5 minutos para no golpear la BD en cada request.
     *
     * Si el admin activó el override manual, devuelve OPERANDO directamente.
     * Retorna solo lo necesario para el banner: estado, titulo, mensaje.
     */
    public static function getEstadoSistema(): array
    {
        // Si hay un override manual activo del admin, forzar OPERANDO
        if (Cache::get('vucem_override_operando')) {
            return ['estado' => 'OPERANDO', 'override' => true];
        }

        return Cache::remember('vucem_estado_sistema', 300, function () {
            $hace30min = now()->subMinutes(30);

            $errores  = VucemErrorLog::where('created_at', '>=', $hace30min)->count();
            $exitosos = MvAcuse::where('fecha_envio', '>=', $hace30min)->count();
            $total    = $errores + $exitosos;
            $tasa     = $total >= 3 ? ($errores / $total) : 0.0;

            if ($tasa > 0.75 && $total >= 3) {
                return [
                    'estado'  => 'VUCEM_CAIDO',
                    'color'   => 'red',
                    'titulo'  => 'Servicio VUCEM no disponible',
                    'mensaje' => 'Se detectaron fallas generalizadas en VUCEM. El servicio puede estar caído o con problemas graves en este momento.',
                ];
            }

            if ($tasa > 0.30 && $total >= 2) {
                return [
                    'estado'  => 'INTERMITENTE',
                    'color'   => 'yellow',
                    'titulo'  => 'VUCEM con intermitencia',
                    'mensaje' => 'VUCEM está presentando inestabilidad. Algunos servicios pueden fallar o tardar más de lo normal.',
                ];
            }

            return ['estado' => 'OPERANDO'];
        });
    }

    /**
     * Fuerza el estado a OPERANDO durante 2 horas (override manual del admin).
     * Limpia también el cache automático para que no se restablezca antes.
     */
    public static function forzarOperando(): void
    {
        Cache::forget('vucem_estado_sistema');
        Cache::put('vucem_override_operando', true, now()->addHours(2));
    }

    /**
     * Elimina el override manual y deja que el diagnóstico automático tome el control.
     */
    public static function limpiarOverride(): void
    {
        Cache::forget('vucem_override_operando');
        Cache::forget('vucem_estado_sistema');
    }

    /**
     * Indica si hay un override manual activo.
     */
    public static function tieneOverride(): bool
    {
        return (bool) Cache::get('vucem_override_operando');
    }
}
