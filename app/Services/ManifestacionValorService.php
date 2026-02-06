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

                // C. Precio Por Pagar
                foreach ($cove['precios_por_pagar'] ?? [] as $precio) {
                    $addField($this->formatVucemDate($precio['fecha'] ?? $precio['fechaPago'] ?? ''));
                    $addField($this->formatVucemNumber($precio['importe'] ?? $precio['total'] ?? 0));
                    $situacion = $precio['momentoSituacion'] ?? $precio['situacionNofechaPago'] ?? '';
                    if (!empty($situacion)) $addField($situacion);
                    $addField($precio['formaPago'] ?? $precio['tipoPago'] ?? '');
                    if (!empty($precio['especifique'])) $addField($precio['especifique']);
                    $addField($precio['tipoMoneda'] ?? 'USD');
                    $addField($this->formatVucemNumber($precio['tipoCambio'] ?? 1));
                }

                // D. Compensación
                foreach ($cove['compensos_pago'] ?? [] as $compenso) {
                    $addField($compenso['formaPago'] ?? $compenso['tipoPago'] ?? '');
                    $addField($this->formatVucemDate($compenso['fecha'] ?? ''));
                    $addField($compenso['motivo'] ?? '');
                    $addField($compenso['prestacionMercancia'] ?? '');
                    if (!empty($compenso['especifique'])) $addField($compenso['especifique']);
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

        return '||' . implode('|', $fields) . '||';
    }

    /**
     * Formato fecha para Cadena Original: d/m/Y (Ej: 19/12/2025)
     */
    public function formatVucemDate($date): string
    {
        if (empty($date)) return '';
        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) return $date;
            $timestamp = is_numeric($date) ? $date : strtotime($date);
            if ($timestamp === false) return $date;
            return date('d/m/Y', $timestamp);
        } catch (\Exception $e) { return $date; }
    }

    /**
     * Formato fecha para XML.
     * CORRECCIÓN: VUCEM parece rechazar ISO (Y-m-d). 
     * Probamos enviando d/m/Y (igual que en la cadena original).
     */
    public function formatXmlDate($date): string
    {
        if (empty($date)) return '';
        try {
            // Si ya viene d/m/Y (ej: 19/12/2025), devolver tal cual
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                return $date;
            }
            
            // Si es timestamp o ISO (2025-12-19), convertir a d/m/Y
            $timestamp = is_numeric($date) ? $date : strtotime($date);
            if ($timestamp === false) return $date;

            return date('d/m/Y', $timestamp); // EJ: 19/12/2025
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
}