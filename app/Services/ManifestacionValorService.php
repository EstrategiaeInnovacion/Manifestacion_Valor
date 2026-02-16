<?php

namespace App\Services;

use App\Models\MvClientApplicant;
use App\Models\MvDatosManifestacion;
use App\Models\MvInformacionCove;

class ManifestacionValorService
{
    public function normalizeEdocumentFolio(?string $folio): string
    {
        $folio = $folio ?? '';
        $folio = preg_replace('/\s+/', '', $folio);
        return strtoupper(trim($folio));
    }

    /**
     * Valida el formato del folio eDocument/COVE.
     * Formatos válidos: COVE + 10 alfanuméricos, o 13 dígitos.
     */
    public function validateEdocumentFolio(string $folio): array
    {
        // Normalizar primero
        $folio = $this->normalizeEdocumentFolio($folio);

        if (empty($folio)) {
            return ['valid' => false, 'message' => 'El folio eDocument es requerido.'];
        }

        if (strlen($folio) < 10) {
            return ['valid' => false, 'message' => 'El folio eDocument debe tener al menos 10 caracteres.'];
        }

        // Formato COVE: COVE + 10 alfanuméricos (ej: COVE257VFW2I7)
        if (preg_match('/^COVE[A-Z0-9]{10}$/', $folio)) {
            return ['valid' => true, 'message' => 'Folio COVE válido.', 'tipo' => 'COVE'];
        }

        // Formato eDocument numérico: 13 dígitos (ej: 0433250D59FS5)
        if (preg_match('/^[A-Z0-9]{13}$/', $folio)) {
            return ['valid' => true, 'message' => 'Folio eDocument válido.', 'tipo' => 'eDocument'];
        }

        // Si no coincide con ningún formato conocido, aceptar si tiene entre 10-15 caracteres alfanuméricos
        if (preg_match('/^[A-Z0-9]{10,15}$/', $folio)) {
            return ['valid' => true, 'message' => 'Folio válido.', 'tipo' => 'Genérico'];
        }

        return ['valid' => false, 'message' => 'Formato de folio inválido. Debe ser un COVE (ej: COVE257VFW2I7) o eDocument (13 caracteres alfanuméricos).'];
    }

    /**
     * Construye la cadena original con formato VUCEM estricto.
     * FECHAS: d/m/Y (Ej: 19/12/2025)
     * NUMEROS: Sin ceros innecesarios a la derecha.
     */
    public function buildCadenaOriginal(
        MvClientApplicant $applicant,
        ?MvDatosManifestacion $datosManifestacion,
        ?MvInformacionCove $informacionCove,
        array $documentos
    ): string {
        $fields = [];

        $addField = function ($value) use (&$fields) {
            if (is_bool($value)) {
                $fields[] = $value ? '1' : '0';
            } elseif (is_null($value) || $value === '') {
                $fields[] = '';
            } else {
                $cleanValue = str_replace('|', '', trim((string) $value));
                $fields[] = $cleanValue;
            }
        };

        // --- 1. DATOS GENERALES ---
        // IMPORTANTE: El RFC del importador SÍ va al inicio de la cadena original
        // según documentación oficial de VUCEM
        $addField($datosManifestacion?->rfc_importador ?? $applicant->applicant_rfc);

        foreach ($datosManifestacion?->persona_consulta ?? [] as $persona) {
            $addField(strtoupper($persona['rfc'] ?? ''));
            $addField($persona['tipo_figura'] ?? '');
        }

        if (count($documentos) > 0) {
            foreach ($documentos as $documento) {
                $folio = $documento['folio_edocument'] ?? $documento['eDocument'] ?? '';
                $addField($this->normalizeEdocumentFolio($folio));
            }
        }

        // --- 2. INFORMACIÓN COVE ---
        // FUSIÓN DE DATOS (Igual que en MveSignService)
        $covesList = $informacionCove->informacion_cove ?? [];
        $pedimentosList = $informacionCove->pedimentos ?? [];
        $preciosPagadosList = $informacionCove->precios_pagados ?? $informacionCove->precio_pagado ?? [];
        $incrementablesList = $informacionCove->incrementables ?? [];
        $decrementablesList = $informacionCove->decrementables ?? [];

        if (!empty($covesList)) {
            if (empty($covesList[0]['pedimentos']) && !empty($pedimentosList)) $covesList[0]['pedimentos'] = $pedimentosList;
            if (empty($covesList[0]['precios_pagados']) && !empty($preciosPagadosList)) $covesList[0]['precios_pagados'] = $preciosPagadosList;
            if (empty($covesList[0]['incrementables']) && !empty($incrementablesList)) $covesList[0]['incrementables'] = $incrementablesList;
            if (empty($covesList[0]['decrementables']) && !empty($decrementablesList)) $covesList[0]['decrementables'] = $decrementablesList;
        }
        
        if (count($covesList) > 0) {
            foreach ($covesList as $cove) {
                $addField($cove['numero_cove'] ?? $cove['cove'] ?? '');
                $addField($cove['incoterm'] ?? '');
                $addField($cove['vinculacion'] ?? $datosManifestacion?->existe_vinculacion ?? '0');

                // A. Pedimentos
                foreach ($cove['pedimentos'] ?? [] as $pedimento) {
                    $addField($pedimento['numero'] ?? $pedimento['pedimento'] ?? '');
                    $addField($pedimento['patente'] ?? '');
                    $addField($pedimento['aduana'] ?? '');
                }

                // B. Precio Pagado
                $preciosPagados = $cove['precios_pagados'] ?? $cove['precio_pagado'] ?? [];
                if (empty($preciosPagados) && !empty($informacionCove->precio_pagado)) $preciosPagados = $informacionCove->precio_pagado;

                foreach ($preciosPagados as $precio) {
                    $addField($this->formatVucemDate($precio['fecha'] ?? $precio['fechaPago'] ?? ''));
                    $addField($this->formatVucemNumber($precio['importe'] ?? $precio['total'] ?? 0));
                    $addField($precio['formaPago'] ?? $precio['tipoPago'] ?? '');
                    if (!empty($precio['especifique'])) $addField($precio['especifique']);
                    $addField($precio['tipoMoneda'] ?? 'USD');
                    $addField($this->formatVucemNumber($precio['tipoCambio'] ?? 1));
                }

                // C. Precio Por Pagar - SOLO incluir si tiene datos reales
                $preciosPorPagar = $cove['precios_por_pagar'] ?? [];
                if (!empty($preciosPorPagar)) {
                    foreach ($preciosPorPagar as $precio) {
                        $addField($this->formatVucemDate($precio['fecha'] ?? $precio['fechaPago'] ?? ''));
                        $addField($this->formatVucemNumber($precio['importe'] ?? $precio['total'] ?? 0));
                        $situacion = $precio['momentoSituacion'] ?? $precio['situacionNofechaPago'] ?? '';
                        if (!empty($situacion)) $addField($situacion);
                        $addField($precio['formaPago'] ?? $precio['tipoPago'] ?? '');
                        if (!empty($precio['especifique'])) $addField($precio['especifique']);
                        $addField($precio['tipoMoneda'] ?? 'USD');
                        $addField($this->formatVucemNumber($precio['tipoCambio'] ?? 1));
                    }
                }

                // D. Compensación - SOLO incluir si tiene datos reales
                // ORDEN CORRECTO según VUCEM: fecha, motivo, prestacionMercancia, tipoPago
                $compensosPago = $cove['compensos_pago'] ?? [];
                if (!empty($compensosPago)) {
                    foreach ($compensosPago as $compenso) {
                        $addField($this->formatVucemDate($compenso['fecha'] ?? ''));
                        $addField($compenso['motivo'] ?? '');
                        $addField($compenso['prestacionMercancia'] ?? '');
                        $addField($compenso['formaPago'] ?? $compenso['tipoPago'] ?? '');
                        if (!empty($compenso['especifique'])) $addField($compenso['especifique']);
                    }
                }

                // E. Método Valoración
                $addField($cove['metodo_valoracion'] ?? $datosManifestacion?->metodo_valoracion ?? '');

                // F. Incrementables
                $incrementables = $cove['incrementables'] ?? $cove['incrementable'] ?? [];
                if (empty($incrementables) && !empty($informacionCove->incrementables)) $incrementables = $informacionCove->incrementables;

                foreach ($incrementables as $inc) {
                    $addField($inc['incrementable'] ?? $inc['tipoIncrementable'] ?? '');
                    $addField($this->formatVucemDate($inc['fechaErogacion'] ?? $inc['fecha_erogacion'] ?? ''));
                    $addField($this->formatVucemNumber($inc['importe'] ?? 0));
                    $addField($inc['tipoMoneda'] ?? $inc['tipo_moneda'] ?? 'USD');
                    $addField($this->formatVucemNumber($inc['tipoCambio'] ?? $inc['tipo_cambio'] ?? 1));
                    $aCargo = $inc['aCargoImportador'] ?? $inc['a_cargo_importador'] ?? 0;
                    $addField($aCargo ? '1' : '0');
                }

                // G. Decrementables
                $decrementables = $cove['decrementables'] ?? $cove['decrementable'] ?? [];
                if (empty($decrementables) && !empty($informacionCove->decrementables)) $decrementables = $informacionCove->decrementables;
                
                foreach ($decrementables as $dec) {
                    $addField($dec['decrementable'] ?? $dec['tipoDecrementable'] ?? '');
                    $addField($this->formatVucemDate($dec['fechaErogacion'] ?? $dec['fecha_erogacion'] ?? ''));
                    $addField($this->formatVucemNumber($dec['importe'] ?? 0));
                    $addField($dec['tipoMoneda'] ?? $dec['tipo_moneda'] ?? 'USD');
                    $addField($this->formatVucemNumber($dec['tipoCambio'] ?? $dec['tipo_cambio'] ?? 1));
                }
            }
        } else {
             $addField(''); $addField(''); $addField(''); 
             $addField($datosManifestacion?->metodo_valoracion ?? '');
        }

        // --- 3. VALORES TOTALES ---
        $valorData = $informacionCove?->valor_en_aduana ?? [];
        $addField($this->formatVucemNumber($valorData['total_precio_pagado'] ?? 0));
        $addField($this->formatVucemNumber($valorData['total_precio_por_pagar'] ?? 0));
        $addField($this->formatVucemNumber($valorData['total_incrementables'] ?? 0));
        $addField($this->formatVucemNumber($valorData['total_decrementables'] ?? 0));
        $addField($this->formatVucemNumber($valorData['total_valor_aduana'] ?? 0));

        // IMPORTANTE: VUCEM usa UN SOLO pipe al inicio y final, NO doble pipe
        $cadena = '|' . implode('|', $fields) . '|';

        // DEBUG: Log cada campo de la cadena original
        \Illuminate\Support\Facades\Log::debug('CAMPOS DE LA CADENA ORIGINAL:', [
            'total_campos' => count($fields),
            'campos' => $fields,
            'cadena_completa' => $cadena
        ]);

        return $cadena;
    }

    /**
     * Formato fecha para Cadena Original: d/m/Y (Ej: 19/12/2025)
     * IMPORTANTE: La cadena original usa formato dd/mm/yyyy según documentación VUCEM
     */
    public function formatVucemDate($date): string
    {
        if (empty($date)) return '';
        try {
            // Si ya viene en formato dd/mm/yyyy, devolver tal cual
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                return $date;
            }

            // Si viene en formato ISO con hora (2025-12-19T00:00:00), convertir a dd/mm/yyyy
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $date)) {
                $timestamp = strtotime($date);
                if ($timestamp !== false) {
                    return date('d/m/Y', $timestamp);
                }
            }

            // Si viene en formato ISO sin hora (2025-12-19), convertir a dd/mm/yyyy
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $timestamp = strtotime($date);
                if ($timestamp !== false) {
                    return date('d/m/Y', $timestamp);
                }
            }

            // Para cualquier otro formato, convertir a dd/mm/yyyy
            $timestamp = is_numeric($date) ? $date : strtotime($date);
            if ($timestamp === false) return $date;
            return date('d/m/Y', $timestamp);
        } catch (\Exception $e) { return $date; }
    }

    /**
     * Formato fecha para XML.
     * VUCEM requiere formato ISO 8601 completo: Y-m-d\TH:i:s (Ej: 2025-12-19T00:00:00)
     */
    public function formatXmlDate($date): string
    {
        if (empty($date)) return '';
        try {
            // Si ya viene en formato ISO con hora (2025-12-19T00:00:00), devolver tal cual
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $date)) {
                return $date;
            }

            // Si ya viene en formato ISO sin hora (2025-12-19), agregar hora
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date . 'T00:00:00';
            }

            // Si viene en formato d/m/Y (19/12/2025), convertir a ISO con hora
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                $timestamp = strtotime($date);
                if ($timestamp !== false) {
                    return date('Y-m-d\TH:i:s', $timestamp); // EJ: 2025-12-19T00:00:00
                }
            }

            // Si es timestamp o cualquier otro formato, convertir a ISO con hora
            $timestamp = is_numeric($date) ? $date : strtotime($date);
            if ($timestamp === false) return $date;

            return date('Y-m-d\TH:i:s', $timestamp); // EJ: 2025-12-19T00:00:00
        } catch (\Exception $e) {
            return $date;
        }
    }

    public function formatVucemNumber($value): string
    {
        if ($value === '' || $value === null) return '0';
        $clean = preg_replace('/[,\s]/', '', (string)$value);
        if (!is_numeric($clean)) return '0';
        return (string)(float)$clean;
    }

    /**
     * Parsea el contenido de un Archivo M (pedimento) y extrae los datos relevantes para la MVE.
     * El Archivo M tiene un formato de registros fijos donde cada línea inicia con un código de 3 dígitos.
     *
     * Códigos de registro principales:
     * - 500: Encabezado del pedimento (contiene RFC importador/exportador)
     * - 501: Datos generales del pedimento
     * - 505: Datos del proveedor/comprador
     * - 510: Partida de mercancía
     * - 551: Vinculación
     * - 552: Incrementables/Decrementables
     */
    public function parseArchivoMForMV(string $content): array
    {
        $result = [
            'datos_manifestacion' => [
                'rfc_importador' => null,
                'nombre_importador' => null,
                'tipo_operacion' => null,
                'clave_pedimento' => null,
                'aduana' => null,
                'patente' => null,
                'pedimento' => null,
                'fecha_entrada' => null,
            ],
            'informacion_cove' => [],
            'pedimentos' => [],
            'proveedores' => [],
            'mercancias' => [],
            'documentos' => [],
            'vinculacion' => null,
            'incrementables' => [],
            'decrementables' => [],
        ];

        // Normalizar saltos de línea
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // El archivo M usa formato pipe-delimited: CODIGO|campo1|campo2|...
            $fields = explode('|', $line);
            $codigo = trim($fields[0] ?? '');

            if (empty($codigo)) continue;

            switch ($codigo) {
                case '500':
                    // 500|tipo_op|patente|num_pedimento|aduana||
                    $result['datos_manifestacion']['tipo_operacion'] = trim($fields[1] ?? '');
                    $result['datos_manifestacion']['patente'] = trim($fields[2] ?? '');
                    $result['datos_manifestacion']['pedimento'] = trim($fields[3] ?? '');
                    $result['datos_manifestacion']['aduana'] = trim($fields[4] ?? '');
                    break;

                case '501':
                    // 501|patente|num_pedimento|aduana|secuencia|clave_ped|aduana2||RFC|curp|...
                    $patente = trim($fields[1] ?? '');
                    $numPedimento = trim($fields[2] ?? '');
                    $aduana = trim($fields[3] ?? '');
                    $clavePedimento = trim($fields[5] ?? '');
                    $rfcImportador = trim($fields[8] ?? '');

                    $result['datos_manifestacion']['rfc_importador'] = $rfcImportador;
                    $result['datos_manifestacion']['patente'] = $patente;
                    $result['datos_manifestacion']['pedimento'] = $numPedimento;
                    $result['datos_manifestacion']['aduana'] = $aduana;
                    $result['datos_manifestacion']['clave_pedimento'] = $clavePedimento;

                    // Nombre del importador (campo 22 si existe)
                    if (isset($fields[22]) && !empty(trim($fields[22]))) {
                        $result['datos_manifestacion']['nombre_importador'] = trim($fields[22]);
                    }

                    // Fecha de entrada (campo 23 si existe, formato AAAAMMDD)
                    if (isset($fields[23])) {
                        $fechaStr = trim($fields[23]);
                        if (strlen($fechaStr) === 8 && is_numeric($fechaStr)) {
                            $result['datos_manifestacion']['fecha_entrada'] =
                                substr($fechaStr, 6, 2) . '/' .
                                substr($fechaStr, 4, 2) . '/' .
                                substr($fechaStr, 0, 4);
                        }
                    }

                    $result['pedimentos'][] = [
                        'patente' => $patente,
                        'pedimento' => $numPedimento,
                        'aduana' => $aduana,
                        'clave_pedimento' => $clavePedimento,
                    ];
                    break;

                case '505':
                    // 505|num_pedimento||numero_cove|incoterm||||||id_fiscal|nombre_proveedor||||||
                    $proveedor = [
                        'numero_cove' => trim($fields[3] ?? ''),
                        'incoterm' => trim($fields[4] ?? ''),
                        'id_fiscal' => trim($fields[10] ?? ''),
                        'nombre' => trim($fields[11] ?? ''),
                    ];
                    if (!empty($proveedor['id_fiscal']) || !empty($proveedor['nombre'])) {
                        $result['proveedores'][] = $proveedor;
                    }
                    if (!empty($proveedor['numero_cove'])) {
                        $result['informacion_cove'][] = [
                            'numero_cove' => $proveedor['numero_cove'],
                            'incoterm' => $proveedor['incoterm'],
                        ];
                    }
                    break;

                case '507':
                    // 507|num_pedimento|tipo_doc|folio|||
                    $tipoDoc = trim($fields[2] ?? '');
                    $folio = trim($fields[3] ?? '');
                    if (!empty($folio)) {
                        $result['documentos'][] = [
                            'tipo_documento' => $tipoDoc,
                            'folio_edocument' => $folio,
                        ];
                    }
                    break;

                case '551':
                    // 551|num_pedimento|fraccion|secuencia|unidad_tarifa|descripcion|valor_dolares|val_comercial|val_aduana|precio_unitario|cantidad|unidad_medida|peso_kg|...||vinculacion|...
                    $mercancia = [
                        'fraccion' => trim($fields[2] ?? ''),
                        'secuencia' => trim($fields[3] ?? ''),
                        'descripcion' => trim($fields[5] ?? ''),
                        'valor_dolares' => $this->parseNumeroArchivoM($fields[6] ?? ''),
                        'valor_comercial' => $this->parseNumeroArchivoM($fields[7] ?? ''),
                        'valor_aduana' => $this->parseNumeroArchivoM($fields[8] ?? ''),
                        'precio_unitario' => $this->parseNumeroArchivoM($fields[9] ?? ''),
                        'cantidad' => $this->parseNumeroArchivoM($fields[10] ?? ''),
                        'unidad' => trim($fields[11] ?? ''),
                    ];

                    if (!empty($mercancia['fraccion'])) {
                        $result['mercancias'][] = $mercancia;
                    }

                    // Vinculación (campo 16): 0 = No, 1 = Sí
                    if (isset($fields[16]) && trim($fields[16]) !== '') {
                        $claveVinculacion = trim($fields[16]);
                        $result['vinculacion'] = $claveVinculacion === '1' ? 'SI' : 'NO';
                    }
                    break;

                case '554':
                    // Incrementables: 554|num_pedimento|clave|importe|...
                    $clave = trim($fields[2] ?? '');
                    $importe = $this->parseNumeroArchivoM($fields[3] ?? '');
                    if (!empty($clave)) {
                        $result['incrementables'][] = [
                            'clave' => $clave,
                            'importe' => $importe,
                        ];
                    }
                    break;

                case '556':
                    // Decrementables: 556|num_pedimento|clave|importe|...
                    $clave = trim($fields[2] ?? '');
                    $importe = $this->parseNumeroArchivoM($fields[3] ?? '');
                    if (!empty($clave)) {
                        $result['decrementables'][] = [
                            'clave' => $clave,
                            'importe' => $importe,
                        ];
                    }
                    break;
            }
        }

        // Calcular totales si hay mercancías
        if (!empty($result['mercancias'])) {
            $totalValorAduana = 0;
            foreach ($result['mercancias'] as $mercancia) {
                $totalValorAduana += floatval($mercancia['valor_aduana'] ?? 0);
            }
            $result['datos_manifestacion']['total_valor_aduana'] = $totalValorAduana;
        }

        return $result;
    }

    /**
     * Parsea un número del formato del Archivo M (puede tener signo al final y decimales implícitos)
     */
    private function parseNumeroArchivoM(?string $valor): float
    {
        if (empty($valor)) return 0.0;
        
        $valor = trim($valor);
        if (empty($valor)) return 0.0;

        // Remover caracteres no numéricos excepto punto y signo
        $valor = preg_replace('/[^0-9.\-]/', '', $valor);
        
        if (!is_numeric($valor)) return 0.0;
        
        return floatval($valor);
    }
}