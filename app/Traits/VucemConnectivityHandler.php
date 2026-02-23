<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait para manejar errores de conectividad con VUCEM.
 *
 * Regla:
 *   - Errores de red/curl (timeout, conexión rechazada, SSL, etc.) →
 *     se registran en logs con detalle completo y se muestra al usuario
 *     un mensaje genérico de conectividad.
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
     * registra el detalle completo en logs y devuelve respuesta con mensaje amigable.
     *
     * @param string $curlError  Mensaje de error devuelto por curl_error()
     * @param string $ctx        Contexto/prefijo para el log (ej: 'MV_SOAP')
     * @param array  $merge      Claves adicionales a incluir en el array de retorno
     * @return array
     */
    protected function handleCurlError(string $curlError, string $ctx, array $merge = []): array
    {
        Log::error("[{$ctx}] Error de conectividad cURL con VUCEM", [
            'curl_error' => $curlError,
        ]);

        return array_merge([
            'success'            => false,
            'connectivity_error' => true,
            'message'            => self::VUCEM_CONNECTIVITY_MSG,
        ], $merge);
    }

    /**
     * Maneja una excepción de conectividad al intentar comunicarse con VUCEM:
     * registra el detalle completo en logs y devuelve respuesta con mensaje amigable.
     *
     * Usar solo en bloques catch que envuelvan exclusivamente la llamada cURL,
     * no en bloques que también cubran lógica de datos.
     *
     * @param \Exception $e     Excepción capturada
     * @param string     $ctx   Contexto/prefijo para el log (ej: 'MV_SOAP')
     * @param array      $merge Claves adicionales a incluir en el array de retorno
     * @return array
     */
    protected function handleConnectionException(\Exception $e, string $ctx, array $merge = []): array
    {
        Log::error("[{$ctx}] Excepción de conectividad con VUCEM", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return array_merge([
            'success'            => false,
            'connectivity_error' => true,
            'message'            => self::VUCEM_CONNECTIVITY_MSG,
        ], $merge);
    }
}
