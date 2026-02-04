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

    public function validateEdocumentFolio(string $folio): array
    {
        $normalized = $this->normalizeEdocumentFolio($folio);

        if ($normalized === '') {
            return ['valid' => false, 'message' => 'El folio eDocument es obligatorio.'];
        }

        $length = strlen($normalized);
        if ($length < 8 || $length > 30) {
            return ['valid' => false, 'message' => 'El folio eDocument debe tener entre 8 y 30 caracteres.'];
        }

        if (!preg_match('/^[A-Z0-9]+$/', $normalized)) {
            return ['valid' => false, 'message' => 'El folio eDocument solo puede contener caracteres alfanuméricos.'];
        }

        return ['valid' => true, 'message' => 'Formato válido.'];
    }

    public function parsePedimentoEdocuments(string $layoutText): array
    {
        $folios = [];
        $lines = preg_split('/\r\n|\r|\n/', $layoutText);

        foreach ($lines as $line) {
            if (!preg_match('/^507\|.*\|ED\|([A-Z0-9]+)\|/i', $line, $matches)) {
                continue;
            }
            $folio = $this->normalizeEdocumentFolio($matches[1] ?? '');
            if ($folio !== '') {
                $folios[] = $folio;
            }
        }
        return array_values(array_unique($folios));
    }

    /**
     * Parsear Archivo M (Data Stage de Aduanas) para pre-llenar formulario MV
     * 
     * @param string $content Contenido del archivo M
     * @return array Datos estructurados con datos_manifestacion, informacion_cove y documentos
     */
    public function parseArchivoMForMV(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        
        $datosManifestacion = [
            'patente' => null,
            'pedimento' => null,
            'aduana' => null,
            'rfc_importador' => null
        ];
        
        $informacionCove = [];
        $documentos = [];
        $fechaExpedicion = null; // Para extraer el año
        $vinculacion = null; // Clave de vinculación del registro 551
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Registro 501 - Datos Generales
            if (str_starts_with($line, '501|')) {
                // Usar explode para obtener todos los campos
                $fields = explode('|', $line);
                
                // Los campos están en los siguientes índices después de "501|"
                $datosManifestacion['patente'] = isset($fields[1]) ? trim($fields[1]) : null;
                $datosManifestacion['pedimento'] = isset($fields[2]) ? trim($fields[2]) : null;
                $datosManifestacion['aduana'] = isset($fields[3]) ? trim($fields[3]) : null;
                $datosManifestacion['rfc_importador'] = isset($fields[8]) ? strtoupper(trim($fields[8])) : null;
            }
            
            // Registro 505 - Facturas/COVE
            if (str_starts_with($line, '505|')) {
                $fields = explode('|', $line);
                
                // Extraer fecha si aún no la tenemos (formato: DDMMYYYY)
                if ($fechaExpedicion === null && isset($fields[2])) {
                    $fechaRaw = trim($fields[2]);
                    if (strlen($fechaRaw) === 8) {
                        // Extraer año (últimos 4 caracteres)
                        $fechaExpedicion = $fechaRaw;
                    }
                }
                
                // Convertir incoterm a formato VUCEM (TIPINC.XXX)
                $incotermRaw = isset($fields[4]) ? trim($fields[4]) : '';
                $incoterm = $incotermRaw ? 'TIPINC.' . $incotermRaw : '';
                
                $informacionCove[] = [
                    'numero_cove' => isset($fields[3]) ? trim($fields[3]) : '',
                    'incoterm' => $incoterm,
                    'fecha_expedicion' => isset($fields[2]) ? trim($fields[2]) : '',
                    'numero_factura' => '', // Se llenará manualmente
                    'emisor_original' => isset($fields[10]) ? trim($fields[10]) : '', // Código del emisor
                    'destinatario' => '', // Se llenará manualmente
                    'metodo_valoracion' => '' // Se llenará manualmente
                ];
            }
            
            // Registro 507 - Identificadores (eDocuments)
            if (str_starts_with($line, '507|')) {
                $fields = explode('|', $line);
                
                // Si el índice 2 es 'ED', extraer el folio del índice 3
                if (isset($fields[2]) && strtoupper(trim($fields[2])) === 'ED' && isset($fields[3])) {
                    $folioRaw = trim($fields[3]);
                    $folioNormalizado = $this->normalizeEdocumentFolio($folioRaw);
                    
                    if (!empty($folioNormalizado)) {
                        $documentos[] = [
                            'folio_edocument' => $folioNormalizado,
                            'tipo_documento' => 'ED'
                        ];
                    }
                }
            }
            
            // Registro 551 - Partidas (contiene clave de vinculación en campo 17)
            // Ejemplo: 551|5001121|82077003|1|01|DESC|6126.00000|7218|6126|336.70|1.000|6|1.00000|6||1|1||||PRT|PRT|||||
            // Campo 17 (índice 16): Clave de vinculación
            // 0 = No existe vinculación
            // 1 = Sí existe vinculación y no afecta el valor en aduana
            // 2 = Sí existe vinculación y afecta el valor en aduana
            if (str_starts_with($line, '551|')) {
                $fields = explode('|', $line);
                
                // Solo extraer la vinculación si aún no la tenemos (usar el primer registro 551)
                if ($vinculacion === null && isset($fields[16])) {
                    $vinculacionRaw = trim($fields[16]);
                    if ($vinculacionRaw !== '' && in_array($vinculacionRaw, ['0', '1', '2'])) {
                        $vinculacion = $vinculacionRaw;
                    }
                }
            }
        }
        
        // Armar pedimento completo: YY ADUANA PATENTE PEDIMENTO
        if ($datosManifestacion['pedimento'] && $datosManifestacion['aduana'] && $datosManifestacion['patente'] && $fechaExpedicion) {
            // Extraer últimos 2 dígitos del año
            $year = substr($fechaExpedicion, -2); // Últimos 2 caracteres (YY)
            
            // Armar pedimento completo sin espacios (el JS se encargará del formato visual)
            $pedimentoCompleto = $year . $datosManifestacion['aduana'] . $datosManifestacion['patente'] . $datosManifestacion['pedimento'];
            
            $datosManifestacion['pedimento'] = $pedimentoCompleto;
        }
        
        // Agregar la vinculación a cada COVE extraído
        // La vinculación aplica a nivel de pedimento, por lo que se aplica a todos los COVEs
        if ($vinculacion !== null) {
            foreach ($informacionCove as &$cove) {
                $cove['vinculacion'] = $vinculacion;
            }
            unset($cove); // Romper referencia
        }
        
        // Eliminar duplicados de documentos
        $documentos = array_values(array_unique($documentos, SORT_REGULAR));
        
        return [
            'datos_manifestacion' => $datosManifestacion,
            'informacion_cove' => $informacionCove,
            'documentos' => $documentos,
            'vinculacion' => $vinculacion // También devolver a nivel raíz para fácil acceso
        ];
    }

    public function buildRegistroManifestacionXml(array $documentos): string
    {
        $xml = new \SimpleXMLElement('<datosManifestacionValor></datosManifestacionValor>');

        foreach ($documentos as $documento) {
            $folio = $this->normalizeEdocumentFolio($documento['folio_edocument'] ?? $documento['eDocument'] ?? '');
            if ($folio === '') continue;

            $documentosNode = $xml->addChild('documentos');
            $documentosNode->addChild('eDocument', $folio);
        }
        return $xml->asXML() ?: '';
    }

    /**
     * Construye la cadena original siguiendo estrictamente el XSD de VUCEM.
     * Los campos vacíos se representan como || y el orden es crítico.
     * 
     * ORDEN DE CAMPOS:
     * 1. RFC Importador
     * 2. Por cada personaConsulta: rfc, tipoFigura (CLAVE)
     * 3. Por cada documento: eDocument
     * 4. Por cada informacionCove:
     *    - cove, incoterm (CLAVE), existeVinculacion (0/1/2)
     *    - Por cada pedimento: numero, patente, aduana (CLAVE)
     *    - Por cada precioPagado: fecha, importe, formaPago (CLAVE), especifique, tipoMoneda (CLAVE), tipoCambio
     *    - Por cada precioPorPagar: fecha, importe, momentoSituacion, formaPago (CLAVE), especifique, tipoMoneda (CLAVE), tipoCambio
     *    - Por cada compensoPago: formaPago (CLAVE), fecha, motivo, prestacionMercancia, especifique
     *    - metodoValoracion (CLAVE)
     *    - Por cada incrementable: tipo (CLAVE), fechaErogacion, importe, tipoMoneda (CLAVE), tipoCambio, aCargoImportador (0/1)
     *    - Por cada decrementable: tipo (CLAVE), fechaErogacion, importe, tipoMoneda (CLAVE), tipoCambio
     * 5. Valores totales: totalPrecioPagado, totalPrecioPorPagar, totalIncrementables, totalDecrementables, totalValorAduana
     */
    public function buildCadenaOriginal(
        MvClientApplicant $applicant,
        ?MvDatosManifestacion $datosManifestacion,
        ?MvInformacionCove $informacionCove,
        array $documentos
    ): string {
        $fields = [];

        // Helper: Agrega un campo limpiando pipes internos. Null/vacío se convierte en cadena vacía.
        $addField = function ($value) use (&$fields) {
            if (is_bool($value)) {
                $fields[] = $value ? '1' : '0';
            } elseif (is_null($value) || $value === '') {
                $fields[] = ''; 
            } else {
                // Eliminar pipes dentro del valor para no romper la estructura
                $cleanValue = str_replace('|', '', trim((string) $value));
                $fields[] = $cleanValue;
            }
        };

        // --- 1. RFC IMPORTADOR ---
        $addField($datosManifestacion?->rfc_importador ?? $applicant->applicant_rfc);

        // --- 2. PERSONAS CONSULTA (Repetible) ---
        $personasConsulta = $datosManifestacion?->persona_consulta ?? [];
        if (count($personasConsulta) > 0) {
            foreach ($personasConsulta as $persona) {
                $addField($persona['rfc'] ?? '');
                // tipo_figura debe ser la CLAVE (TIPFIG.REP, TIPFIG.AGE, TIPFIG.MAN, etc.)
                $addField($persona['tipo_figura'] ?? '');
            }
        }

        // --- 3. DOCUMENTOS eDocument (Repetible) ---
        if (count($documentos) > 0) {
            foreach ($documentos as $documento) {
                $folio = $documento['folio_edocument'] ?? $documento['eDocument'] ?? '';
                $addField($this->normalizeEdocumentFolio($folio));
            }
        }

        // --- 4. INFORMACION COVE ---
        $informacionCoveData = $informacionCove?->informacion_cove ?? [];
        $pedimentos = $informacionCove?->pedimentos ?? [];
        $precioPagado = $informacionCove?->precio_pagado ?? [];
        $precioPorPagar = $informacionCove?->precio_por_pagar ?? [];
        $compensoPago = $informacionCove?->compenso_pago ?? [];
        $incrementables = $informacionCove?->incrementables ?? [];
        $decrementables = $informacionCove?->decrementables ?? [];

        // Si hay COVEs, procesar cada uno
        if (count($informacionCoveData) > 0) {
            foreach ($informacionCoveData as $cove) {
                // A. Datos básicos COVE
                $addField($cove['numero_cove'] ?? $cove['cove'] ?? '');
                // incoterm debe ser la CLAVE (TIPINC.FOB, TIPINC.CIF, TIPINC.FCA, etc.)
                $addField($cove['incoterm'] ?? '');
                // vinculacion: 0=No, 1=Sí y no afecta, 2=Sí y afecta
                $addField($cove['vinculacion'] ?? $datosManifestacion?->existe_vinculacion ?? '');

                // B. Pedimentos (por cada pedimento) - SIEMPRE incluir campos aunque estén vacíos
                if (count($pedimentos) > 0) {
                    foreach ($pedimentos as $pedimento) {
                        // numero: sin espacios (25 640 1882 5001121 -> 256401882 5001121 sin espacios)
                        $addField($pedimento['numero'] ?? $pedimento['pedimento'] ?? '');
                        $addField($pedimento['patente'] ?? '');
                        // aduana: CLAVE (64-0 -> 640)
                        $addField($pedimento['aduana'] ?? '');
                    }
                } else {
                    // Pedimento vacío: 3 campos (numero|patente|aduana)
                    $addField(''); // numero
                    $addField(''); // patente
                    $addField(''); // aduana
                }

                // C. Precio Pagado (por cada registro) - SIEMPRE incluir estructura
                if (count($precioPagado) > 0) {
                    foreach ($precioPagado as $precio) {
                        $addField($precio['fecha'] ?? '');
                        $addField($precio['importe'] ?? '');
                        // formaPago: CLAVE (PAGADO.TRA, PAGADO.CHQ, etc.)
                        $addField($precio['formaPago'] ?? '');
                        $addField($precio['especifique'] ?? '');
                        // tipoMoneda: CLAVE (USD, MXN, EUR, etc.)
                        $addField($precio['tipoMoneda'] ?? '');
                        $addField($precio['tipoCambio'] ?? '');
                    }
                } else {
                    // Precio Pagado vacío: 6 campos (fecha|importe|formaPago|especifique|tipoMoneda|tipoCambio)
                    $addField(''); // fecha
                    $addField(''); // importe
                    $addField(''); // formaPago
                    $addField(''); // especifique
                    $addField(''); // tipoMoneda
                    $addField(''); // tipoCambio
                }

                // D. Precio Por Pagar (por cada registro) - SIEMPRE incluir estructura
                if (count($precioPorPagar) > 0) {
                    foreach ($precioPorPagar as $precio) {
                        $addField($precio['fecha'] ?? '');
                        $addField($precio['importe'] ?? '');
                        $addField($precio['momentoSituacion'] ?? '');
                        // formaPago: CLAVE
                        $addField($precio['formaPago'] ?? '');
                        $addField($precio['especifique'] ?? '');
                        // tipoMoneda: CLAVE
                        $addField($precio['tipoMoneda'] ?? '');
                        $addField($precio['tipoCambio'] ?? '');
                    }
                } else {
                    // Precio Por Pagar vacío: 7 campos (fecha|importe|momentoSituacion|formaPago|especifique|tipoMoneda|tipoCambio)
                    $addField(''); // fecha
                    $addField(''); // importe
                    $addField(''); // momentoSituacion
                    $addField(''); // formaPago
                    $addField(''); // especifique
                    $addField(''); // tipoMoneda
                    $addField(''); // tipoCambio
                }

                // E. Compensación (por cada registro) - SIEMPRE incluir estructura
                if (count($compensoPago) > 0) {
                    foreach ($compensoPago as $compenso) {
                        // formaPago: CLAVE
                        $addField($compenso['formaPago'] ?? '');
                        $addField($compenso['fecha'] ?? '');
                        $addField($compenso['motivo'] ?? '');
                        $addField($compenso['prestacionMercancia'] ?? '');
                        $addField($compenso['especifique'] ?? '');
                    }
                } else {
                    // Compenso vacío: 5 campos (formaPago|fecha|motivo|prestacionMercancia|especifique)
                    $addField(''); // formaPago
                    $addField(''); // fecha
                    $addField(''); // motivo
                    $addField(''); // prestacionMercancia
                    $addField(''); // especifique
                }

                // F. Método Valoración
                // metodo_valoracion: CLAVE (VALADU.VTM, VALADU.SEG, VALADU.TER, etc.)
                $addField($cove['metodo_valoracion'] ?? $datosManifestacion?->metodo_valoracion ?? '');

                // G. Incrementables (por cada registro) - SIEMPRE incluir estructura
                if (count($incrementables) > 0) {
                    foreach ($incrementables as $inc) {
                        // incrementable: CLAVE (INCREMEN.COM, INCREMEN.FLE, INCREMEN.SEG, etc.)
                        $addField($inc['incrementable'] ?? $inc['tipoIncrementable'] ?? '');
                        $addField($inc['fechaErogacion'] ?? $inc['fecha_erogacion'] ?? '');
                        $addField($inc['importe'] ?? '');
                        // tipoMoneda: CLAVE
                        $addField($inc['tipoMoneda'] ?? $inc['tipo_moneda'] ?? '');
                        $addField($inc['tipoCambio'] ?? $inc['tipo_cambio'] ?? '');
                        // aCargoImportador: 0 o 1
                        $aCargoVal = $inc['aCargoImportador'] ?? $inc['a_cargo_importador'] ?? '';
                        if ($aCargoVal !== '') {
                            $addField($aCargoVal ? '1' : '0');
                        } else {
                            $addField('');
                        }
                    }
                } else {
                    // Incrementables vacío: 6 campos (tipo|fechaErogacion|importe|tipoMoneda|tipoCambio|aCargoImportador)
                    $addField(''); // tipo/incrementable
                    $addField(''); // fechaErogacion
                    $addField(''); // importe
                    $addField(''); // tipoMoneda
                    $addField(''); // tipoCambio
                    $addField(''); // aCargoImportador
                }

                // H. Decrementables (por cada registro) - SIEMPRE incluir estructura
                if (count($decrementables) > 0) {
                    foreach ($decrementables as $dec) {
                        // decrementable: CLAVE (DECREMEN.DES, DECREMEN.DEV, DECREMEN.OTR, etc.)
                        $addField($dec['decrementable'] ?? $dec['tipoDecrementable'] ?? '');
                        $addField($dec['fechaErogacion'] ?? $dec['fecha_erogacion'] ?? '');
                        $addField($dec['importe'] ?? '');
                        // tipoMoneda: CLAVE
                        $addField($dec['tipoMoneda'] ?? $dec['tipo_moneda'] ?? '');
                        $addField($dec['tipoCambio'] ?? $dec['tipo_cambio'] ?? '');
                    }
                } else {
                    // Decrementables vacío: 5 campos (tipo|fechaErogacion|importe|tipoMoneda|tipoCambio)
                    $addField(''); // tipo/decrementable
                    $addField(''); // fechaErogacion
                    $addField(''); // importe
                    $addField(''); // tipoMoneda
                    $addField(''); // tipoCambio
                }
            }
        } else {
            // Sin COVEs: agregar estructura base vacía
            $addField(''); // numero_cove
            $addField(''); // incoterm
            $addField(''); // vinculacion
            // Pedimento vacío: 3 campos
            $addField(''); $addField(''); $addField('');
            // Precio Pagado vacío: 6 campos
            $addField(''); $addField(''); $addField(''); $addField(''); $addField(''); $addField('');
            // Precio Por Pagar vacío: 7 campos
            $addField(''); $addField(''); $addField(''); $addField(''); $addField(''); $addField(''); $addField('');
            // Compenso vacío: 5 campos
            $addField(''); $addField(''); $addField(''); $addField(''); $addField('');
            // Método valoración
            $addField($datosManifestacion?->metodo_valoracion ?? '');
            // Incrementables vacío: 6 campos
            $addField(''); $addField(''); $addField(''); $addField(''); $addField(''); $addField('');
            // Decrementables vacío: 5 campos
            $addField(''); $addField(''); $addField(''); $addField(''); $addField('');
        }

        // --- 5. VALORES TOTALES (Valor en Aduana) ---
        $valorData = $informacionCove?->valor_en_aduana ?? [];
        $addField($valorData['total_precio_pagado'] ?? '');
        $addField($valorData['total_precio_por_pagar'] ?? '');
        $addField($valorData['total_incrementables'] ?? '');
        $addField($valorData['total_decrementables'] ?? '');
        $addField($valorData['total_valor_aduana'] ?? '');

        // Formato VUCEM: Doble pipe al inicio y final
        return '||' . implode('|', $fields) . '||';
    }
}