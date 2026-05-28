<?php

namespace App\Traits;

use App\Models\VucemErrorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait para manejar errores de conectividad con VUCEM.
 *
 * Regla:
 *   - Errores de red/curl (timeout, conexión rechazada, SSL, etc.) →
 *     se registran en logs con detalle completo, se guardan en vucem_error_logs
 *     para diagnóstico, y se muestra al usuario un mensaje genérico de conectividad.
 *   - Errores de datos devueltos por VUCEM (código de error, datos inválidos) →
 *     se muestran al usuario tal cual (no usar este trait para esos casos).
 */
trait VucemConnectivityHandler
{
    /**
     * Mensaje amigable para el usuario ante problemas de conectividad VUCEM.
     */
    private const VUCEM_CONNECTIVITY_MSG =
        'Se detectaron problemas de conectividad con VUCEM. '
        . 'Es posible que experimente lentitud o dificultades al realizar operaciones por Web Service.';

    /**
     * Maneja un error cURL de conectividad:
     * registra el detalle en logs y en vucem_error_logs, y devuelve respuesta con mensaje amigable.
     *
     * @param string   $curlError   Mensaje de error devuelto por curl_error()
     * @param string   $ctx         Contexto/prefijo para el log (ej: 'MV_ENVIO')
     * @param array    $merge       Claves adicionales a incluir en el array de retorno
     * @param int|null $applicantId ID del solicitante involucrado (opcional)
     */
    protected function handleCurlError(string $curlError, string $ctx, array $merge = [], ?int $applicantId = null): array
    {
        Log::error("[{$ctx}] Error de conectividad cURL con VUCEM", [
            'curl_error' => $curlError,
        ]);

        $this->registrarErrorVucem($curlError, $ctx, $applicantId);

        return array_merge([
            'success'            => false,
            'connectivity_error' => true,
            'message'            => self::VUCEM_CONNECTIVITY_MSG,
        ], $merge);
    }

    /**
     * Maneja una excepción de conectividad al intentar comunicarse con VUCEM:
     * registra el detalle en logs y en vucem_error_logs, y devuelve respuesta con mensaje amigable.
     *
     * @param \Exception $e           Excepción capturada
     * @param string     $ctx         Contexto/prefijo para el log (ej: 'MV_SOAP')
     * @param array      $merge       Claves adicionales a incluir en el array de retorno
     * @param int|null   $applicantId ID del solicitante involucrado (opcional)
     */
    protected function handleConnectionException(\Exception $e, string $ctx, array $merge = [], ?int $applicantId = null): array
    {
        Log::error("[{$ctx}] Excepción de conectividad con VUCEM", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->registrarErrorVucem($e->getMessage(), $ctx, $applicantId);

        return array_merge([
            'success'            => false,
            'connectivity_error' => true,
            'message'            => self::VUCEM_CONNECTIVITY_MSG,
        ], $merge);
    }

    // -------------------------------------------------------------------------
    // Métodos de soporte
    // -------------------------------------------------------------------------

    /**
     * Registra un error de infraestructura VUCEM (HTTP 5xx, WebLogic, SOAP malformado)
     * sin el prefijo "cURL" que sería engañoso cuando la petición sí llegó al servidor.
     *
     * @param string $descripcion  Descripción breve del problema (aparece en vucem_error_logs)
     * @param string $ctx          Contexto (ej: 'DIGITALIZACION')
     * @param int|null $applicantId
     */
    protected function registrarErrorVucemPublico(string $descripcion, string $ctx, ?int $applicantId = null): void
    {
        try {
            VucemErrorLog::create([
                'user_id'       => Auth::id(),
                'applicant_id'  => $applicantId,
                'servicio'      => $this->contextoAServicio($ctx),
                'tipo_error'    => 'HTTP_ERROR',
                'curl_error_raw'=> mb_substr($descripcion, 0, 1000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[VucemConnectivityHandler] No se pudo guardar error en BD: ' . $e->getMessage());
        }
    }

    /**
     * Persiste el error de conectividad en vucem_error_logs para diagnóstico posterior.
     */
    private function registrarErrorVucem(string $errorRaw, string $ctx, ?int $applicantId): void
    {
        try {
            VucemErrorLog::create([
                'user_id'       => Auth::id(),
                'applicant_id'  => $applicantId,
                'servicio'      => $this->contextoAServicio($ctx),
                'tipo_error'    => $this->detectarTipoError($errorRaw),
                'curl_error_raw'=> mb_substr($errorRaw, 0, 1000),
            ]);
        } catch (\Throwable $e) {
            // No interrumpir el flujo principal si falla el log a BD
            Log::warning('[VucemConnectivityHandler] No se pudo guardar error en BD: ' . $e->getMessage());
        }
    }

    /**
     * Clasifica el error cURL en una categoría legible.
     */
    private function detectarTipoError(string $errorRaw): string
    {
        $lower = strtolower($errorRaw);

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout') || str_contains($lower, 'operation timed')) {
            return 'TIMEOUT';
        }
        if (str_contains($lower, 'connection refused') || str_contains($lower, 'refused')) {
            return 'CONNECTION_REFUSED';
        }
        if (str_contains($lower, 'ssl') || str_contains($lower, 'certificate') || str_contains($lower, 'handshake')) {
            return 'SSL_ERROR';
        }
        if (str_contains($lower, 'could not resolve') || str_contains($lower, 'resolve host') || str_contains($lower, 'name lookup')) {
            return 'DNS_ERROR';
        }
        if (str_contains($lower, 'network') || str_contains($lower, 'unreachable')) {
            return 'NETWORK_ERROR';
        }

        return 'CURL_ERROR';
    }

    /**
     * Mapea el contexto de log al nombre canónico del servicio VUCEM.
     */
    private function contextoAServicio(string $ctx): string
    {
        $ctx = strtoupper($ctx);

        if (str_contains($ctx, 'MV_CONSULTA') || str_contains($ctx, 'CONSULTA_MV')) {
            return 'MV_CONSULTA';
        }
        if (str_contains($ctx, 'DIGITALIZACION_CONSULTA')) {
            return 'DIGITALIZACION_CONSULTA';
        }
        if (str_contains($ctx, 'DIGITALIZACION') || str_contains($ctx, 'DIGITALIZAR')) {
            return 'DIGITALIZACION';
        }
        if (str_contains($ctx, 'MVE') || str_contains($ctx, 'MV_ENVIO') || str_contains($ctx, 'MV_SOAP')) {
            return 'MV_ENVIO';
        }

        return 'OTRO';
    }
}
