<?php

namespace App\Services;

use App\Models\MvAcuse;
use App\Models\MvClientApplicant;
use App\Models\MvDatosManifestacion;
use App\Models\MvInformacionCove;
use App\Models\MvDocumentos;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para construir y enviar XML SOAP de Manifestación de Valor a VUCEM
 * 
 * Sigue exactamente la estructura del XSD: IngresoManifestacionService.xsd
 * Namespace: http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx
 */
class MvVucemSoapService
{
    // Namespace del servicio VUCEM para Ingreso de Manifestación
    private const NS_MV = 'http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx';
    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const PASSWORD_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';

    private ManifestacionValorService $mveService;

    public function __construct()
    {
        $this->mveService = new ManifestacionValorService();
    }

    /**
     * Genera el XML SOAP completo para enviar a VUCEM
     * Este método es útil para modo prueba: ver el XML sin enviarlo
     * 
     * @return array ['success' => bool, 'xml' => string, 'mapping' => array, 'errors' => array]
     */
    public function buildSoapXml(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        MvDocumentos $documentos,
        string $rfc,
        string $claveWebService,
        array $firmaData = []
    ): array {
        $errors = [];
        $mapping = [];

        try {
            // 1. Preparar datos del applicant
            $rfcImportador = strtoupper($datosManifestacion->rfc_importador ?? $applicant->applicant_rfc);
            $mapping['rfcImportador'] = [
                'campo_bd' => 'mv_datos_manifestacion.rfc_importador',
                'valor_bd' => $rfcImportador,
                'campo_xsd' => 'importador-exportador/rfc',
                'tipo_xsd' => 'xsd:string'
            ];

            // 2. Preparar personas de consulta
            $personasConsultaXml = '';
            $personasConsulta = $datosManifestacion->persona_consulta ?? [];
            if (!empty($personasConsulta)) {
                foreach ($personasConsulta as $idx => $persona) {
                    $rfcPersona = strtoupper($persona['rfc'] ?? '');
                    $tipoFigura = $persona['tipo_figura'] ?? '';
                    
                    $mapping["personaConsulta_{$idx}"] = [
                        'campo_bd' => "mv_datos_manifestacion.persona_consulta[{$idx}]",
                        'valor_bd' => ['rfc' => $rfcPersona, 'tipoFigura' => $tipoFigura],
                        'campo_xsd' => 'datosManifestacionValor/personaConsulta',
                        'tipo_xsd' => 'PersonaConsulta (rfc: string, tipoFigura: string)'
                    ];
                    
                    $personasConsultaXml .= "
            <mv:personaConsulta>
               <mv:rfc>{$rfcPersona}</mv:rfc>
               <mv:tipoFigura>{$tipoFigura}</mv:tipoFigura>
            </mv:personaConsulta>";
                }
            }

            // 3. Preparar documentos (eDocuments)
            $documentosXml = '';
            $docsArray = $documentos->documentos ?? [];
            if (!empty($docsArray)) {
                foreach ($docsArray as $idx => $doc) {
                    $eDocument = $this->mveService->normalizeEdocumentFolio($doc['folio_edocument'] ?? '');
                    
                    if (empty($eDocument)) {
                        $errors[] = "Documento {$idx}: folio_edocument vacío";
                        continue;
                    }

                    $mapping["documento_{$idx}"] = [
                        'campo_bd' => "mv_documentos.documentos[{$idx}].folio_edocument",
                        'valor_bd' => $eDocument,
                        'campo_xsd' => 'datosManifestacionValor/documentos/eDocument',
                        'tipo_xsd' => 'xsd:string'
                    ];

                    $documentosXml .= "
            <mv:documentos>
               <mv:eDocument>{$eDocument}</mv:eDocument>
            </mv:documentos>";
                }
            }

            if (empty($documentosXml)) {
                $errors[] = 'Se requiere al menos un eDocument';
            }

            // 4. Preparar información COVE (puede haber múltiples)
            $informacionCoveXml = '';
            $covesData = $informacionCove->informacion_cove ?? [];
            $pedimentosData = $informacionCove->pedimentos ?? [];
            $precioPagadoData = $informacionCove->precio_pagado ?? [];
            $precioPorPagarData = $informacionCove->precio_por_pagar ?? [];
            $compensoPagoData = $informacionCove->compenso_pago ?? [];
            $incrementablesData = $informacionCove->incrementables ?? [];
            $decrementablesData = $informacionCove->decrementables ?? [];

            foreach ($covesData as $coveIdx => $cove) {
                $numeroCove = $cove['numero_cove'] ?? $cove['cove'] ?? '';
                $incoterm = $cove['incoterm'] ?? '';
                $existeVinculacion = $cove['vinculacion'] ?? $datosManifestacion->existe_vinculacion ?? '0';
                $metodoValoracion = $cove['metodo_valoracion'] ?? $datosManifestacion->metodo_valoracion ?? '';

                $mapping["cove_{$coveIdx}"] = [
                    'campo_bd' => "mv_informacion_cove.informacion_cove[{$coveIdx}]",
                    'valor_bd' => [
                        'cove' => $numeroCove, 
                        'incoterm' => $incoterm, 
                        'vinculacion' => $existeVinculacion,
                        'metodoValoracion' => $metodoValoracion
                    ],
                    'campo_xsd' => 'datosManifestacionValor/informacionCove',
                    'tipo_xsd' => 'InformacionCove'
                ];

                if (empty($numeroCove)) {
                    $errors[] = "COVE {$coveIdx}: número de cove vacío";
                }

                // Construir pedimentos XML
                $pedimentosXml = '';
                foreach ($pedimentosData as $pedIdx => $ped) {
                    $numPedimento = $ped['pedimento'] ?? $ped['numero'] ?? '';
                    $patente = $ped['patente'] ?? '';
                    $aduana = $ped['aduana'] ?? '';

                    $mapping["pedimento_{$coveIdx}_{$pedIdx}"] = [
                        'campo_bd' => "mv_informacion_cove.pedimentos[{$pedIdx}]",
                        'valor_bd' => ['pedimento' => $numPedimento, 'patente' => $patente, 'aduana' => $aduana],
                        'campo_xsd' => 'informacionCove/pedimento',
                        'tipo_xsd' => 'Pedimento (pedimento, patente, aduana: string)'
                    ];

                    $pedimentosXml .= "
               <mv:pedimento>
                  <mv:pedimento>{$numPedimento}</mv:pedimento>
                  <mv:patente>{$patente}</mv:patente>
                  <mv:aduana>{$aduana}</mv:aduana>
               </mv:pedimento>";
                }

                // Construir precio pagado XML
                $precioPagadoXml = $this->buildPrecioPagadoXml($precioPagadoData, $mapping, $coveIdx, $errors);

                // Construir precio por pagar XML
                $precioPorPagarXml = $this->buildPrecioPorPagarXml($precioPorPagarData, $mapping, $coveIdx, $errors);

                // Construir compenso pago XML
                $compensoPagoXml = $this->buildCompensoPagoXml($compensoPagoData, $mapping, $coveIdx, $errors);

                // Construir incrementables XML
                $incrementablesXml = $this->buildIncrementablesXml($incrementablesData, $mapping, $coveIdx, $errors);

                // Construir decrementables XML
                $decrementablesXml = $this->buildDecrementablesXml($decrementablesData, $mapping, $coveIdx, $errors);

                $informacionCoveXml .= "
            <mv:informacionCove>
               <mv:cove>{$numeroCove}</mv:cove>
               <mv:incoterm>{$incoterm}</mv:incoterm>
               <mv:existeVinculacion>{$existeVinculacion}</mv:existeVinculacion>{$pedimentosXml}{$precioPagadoXml}{$precioPorPagarXml}{$compensoPagoXml}
               <mv:metodoValoracion>{$metodoValoracion}</mv:metodoValoracion>{$incrementablesXml}{$decrementablesXml}
            </mv:informacionCove>";
            }

            // 5. Preparar valor en aduana
            $valorAduanaData = $informacionCove->valor_en_aduana ?? [];
            $totalPrecioPagado = $this->formatDecimal($valorAduanaData['total_precio_pagado'] ?? '0.00');
            $totalPrecioPorPagar = $this->formatDecimal($valorAduanaData['total_precio_por_pagar'] ?? '0.00');
            $totalIncrementables = $this->formatDecimal($valorAduanaData['total_incrementables'] ?? '0.00');
            $totalDecrementables = $this->formatDecimal($valorAduanaData['total_decrementables'] ?? '0.00');
            $totalValorAduana = $this->formatDecimal($valorAduanaData['total_valor_aduana'] ?? '0.00');

            $mapping['valorEnAduana'] = [
                'campo_bd' => 'mv_informacion_cove.valor_en_aduana',
                'valor_bd' => $valorAduanaData,
                'campo_xsd' => 'datosManifestacionValor/valorEnAduana',
                'tipo_xsd' => 'ValorEnAduana (totalPrecioPagado, totalPrecioPorPagar, totalIncrementables, totalDecrementables, totalValorAduana: decimal)'
            ];

            $valorEnAduanaXml = "
            <mv:valorEnAduana>
               <mv:totalPrecioPagado>{$totalPrecioPagado}</mv:totalPrecioPagado>
               <mv:totalPrecioPorPagar>{$totalPrecioPorPagar}</mv:totalPrecioPorPagar>
               <mv:totalIncrementables>{$totalIncrementables}</mv:totalIncrementables>
               <mv:totalDecrementables>{$totalDecrementables}</mv:totalDecrementables>
               <mv:totalValorAduana>{$totalValorAduana}</mv:totalValorAduana>
            </mv:valorEnAduana>";

            // 6. Firma electrónica (si se proporciona)
            $firmaXml = '';
            if (!empty($firmaData)) {
                $certificado = $firmaData['certificado'] ?? '';
                $cadenaOriginal = htmlspecialchars($firmaData['cadenaOriginal'] ?? '', ENT_XML1);
                $firma = $firmaData['firma'] ?? '';

                $mapping['firmaElectronica'] = [
                    'campo_bd' => 'generado_en_runtime',
                    'valor_bd' => ['certificado' => 'base64...', 'cadenaOriginal' => 'cadena...', 'firma' => 'base64...'],
                    'campo_xsd' => 'firmaElectronica',
                    'tipo_xsd' => 'FirmaElectronica (certificado: base64Binary, cadenaOriginal: string, firma: base64Binary)'
                ];

                $firmaXml = "
         <mv:firmaElectronica>
            <mv:certificado>{$certificado}</mv:certificado>
            <mv:cadenaOriginal>{$cadenaOriginal}</mv:cadenaOriginal>
            <mv:firma>{$firma}</mv:firma>
         </mv:firmaElectronica>";
            }

            // 7. Construir XML SOAP completo
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:mv="' . self::NS_MV . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="' . self::NS_WSSE . '">
         <wsse:UsernameToken>
            <wsse:Username>' . $rfc . '</wsse:Username>
            <wsse:Password Type="' . self::PASSWORD_TYPE . '">' . $claveWebService . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <mv:registroManifestacion>
         <mv:informacionManifestacion>' . $firmaXml . '
            <mv:importador-exportador>
               <mv:rfc>' . $rfcImportador . '</mv:rfc>
            </mv:importador-exportador>
            <mv:datosManifestacionValor>' . $personasConsultaXml . $documentosXml . $informacionCoveXml . $valorEnAduanaXml . '
            </mv:datosManifestacionValor>
         </mv:informacionManifestacion>
      </mv:registroManifestacion>
   </soapenv:Body>
</soapenv:Envelope>';

            return [
                'success' => count($errors) === 0,
                'xml' => $xml,
                'xml_formatted' => $this->formatXml($xml),
                'mapping' => $mapping,
                'errors' => $errors,
                'validation' => $this->validateXmlStructure($xml)
            ];

        } catch (Exception $e) {
            Log::error('[MV_SOAP] Error generando XML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'xml' => '',
                'mapping' => $mapping,
                'errors' => array_merge($errors, ['Exception: ' . $e->getMessage()])
            ];
        }
    }

    /**
     * Construir XML de precios pagados según XSD
     */
    private function buildPrecioPagadoXml(array $data, array &$mapping, int $coveIdx, array &$errors): string
    {
        $xml = '';
        foreach ($data as $idx => $item) {
            // XSD: fechaPago (dateTime), total (decimal), tipoPago (string), especifique? (string), tipoMoneda (string), tipoCambio (decimal)
            $fechaPago = $this->formatDateTime($item['fecha'] ?? $item['fechaPago'] ?? '');
            $total = $this->formatDecimal($item['importe'] ?? $item['total'] ?? '0.00');
            $tipoPago = $item['formaPago'] ?? $item['tipoPago'] ?? '';
            $especifique = $item['especifique'] ?? '';
            $tipoMoneda = $item['tipoMoneda'] ?? '';
            $tipoCambio = $this->formatDecimal($item['tipoCambio'] ?? '1.00');

            $mapping["precioPagado_{$coveIdx}_{$idx}"] = [
                'campo_bd' => "mv_informacion_cove.precio_pagado[{$idx}]",
                'valor_bd' => $item,
                'campo_xsd' => 'informacionCove/precioPagado',
                'tipo_xsd' => 'PrecioPagado (fechaPago: dateTime, total: decimal, tipoPago: string, especifique?: string, tipoMoneda: string, tipoCambio: decimal)'
            ];

            $especifiqueXml = !empty($especifique) ? "\n                  <mv:especifique>{$especifique}</mv:especifique>" : '';

            $xml .= "
               <mv:precioPagado>
                  <mv:fechaPago>{$fechaPago}</mv:fechaPago>
                  <mv:total>{$total}</mv:total>
                  <mv:tipoPago>{$tipoPago}</mv:tipoPago>{$especifiqueXml}
                  <mv:tipoMoneda>{$tipoMoneda}</mv:tipoMoneda>
                  <mv:tipoCambio>{$tipoCambio}</mv:tipoCambio>
               </mv:precioPagado>";
        }
        return $xml;
    }

    /**
     * Construir XML de precios por pagar según XSD
     */
    private function buildPrecioPorPagarXml(array $data, array &$mapping, int $coveIdx, array &$errors): string
    {
        $xml = '';
        foreach ($data as $idx => $item) {
            // XSD: fechaPago (dateTime), total (decimal), situacionNofechaPago? (string), tipoPago (string), especifique? (string), tipoMoneda (string), tipoCambio (decimal)
            $fechaPago = $this->formatDateTime($item['fecha'] ?? $item['fechaPago'] ?? '');
            $total = $this->formatDecimal($item['importe'] ?? $item['total'] ?? '0.00');
            $situacion = $item['momentoSituacion'] ?? $item['situacionNofechaPago'] ?? '';
            $tipoPago = $item['formaPago'] ?? $item['tipoPago'] ?? '';
            $especifique = $item['especifique'] ?? '';
            $tipoMoneda = $item['tipoMoneda'] ?? '';
            $tipoCambio = $this->formatDecimal($item['tipoCambio'] ?? '1.00');

            $mapping["precioPorPagar_{$coveIdx}_{$idx}"] = [
                'campo_bd' => "mv_informacion_cove.precio_por_pagar[{$idx}]",
                'valor_bd' => $item,
                'campo_xsd' => 'informacionCove/precioPorPagar',
                'tipo_xsd' => 'PrecioPorPagar (fechaPago: dateTime, total: decimal, situacionNofechaPago?: string, tipoPago: string, especifique?: string, tipoMoneda: string, tipoCambio: decimal)'
            ];

            $situacionXml = !empty($situacion) ? "\n                  <mv:situacionNofechaPago>{$situacion}</mv:situacionNofechaPago>" : '';
            $especifiqueXml = !empty($especifique) ? "\n                  <mv:especifique>{$especifique}</mv:especifique>" : '';

            $xml .= "
               <mv:precioPorPagar>
                  <mv:fechaPago>{$fechaPago}</mv:fechaPago>
                  <mv:total>{$total}</mv:total>{$situacionXml}
                  <mv:tipoPago>{$tipoPago}</mv:tipoPago>{$especifiqueXml}
                  <mv:tipoMoneda>{$tipoMoneda}</mv:tipoMoneda>
                  <mv:tipoCambio>{$tipoCambio}</mv:tipoCambio>
               </mv:precioPorPagar>";
        }
        return $xml;
    }

    /**
     * Construir XML de compenso pago según XSD
     */
    private function buildCompensoPagoXml(array $data, array &$mapping, int $coveIdx, array &$errors): string
    {
        $xml = '';
        foreach ($data as $idx => $item) {
            // XSD: tipoPago (string), fecha (dateTime), motivo (string), prestacionMercancia (string), especifique? (string)
            $tipoPago = $item['formaPago'] ?? $item['tipoPago'] ?? '';
            $fecha = $this->formatDateTime($item['fecha'] ?? '');
            $motivo = $item['motivo'] ?? '';
            $prestacionMercancia = $item['prestacionMercancia'] ?? '';
            $especifique = $item['especifique'] ?? '';

            $mapping["compensoPago_{$coveIdx}_{$idx}"] = [
                'campo_bd' => "mv_informacion_cove.compenso_pago[{$idx}]",
                'valor_bd' => $item,
                'campo_xsd' => 'informacionCove/compensoPago',
                'tipo_xsd' => 'CompensoPago (tipoPago: string, fecha: dateTime, motivo: string, prestacionMercancia: string, especifique?: string)'
            ];

            $especifiqueXml = !empty($especifique) ? "\n                  <mv:especifique>{$especifique}</mv:especifique>" : '';

            $xml .= "
               <mv:compensoPago>
                  <mv:tipoPago>{$tipoPago}</mv:tipoPago>
                  <mv:fecha>{$fecha}</mv:fecha>
                  <mv:motivo>{$motivo}</mv:motivo>
                  <mv:prestacionMercancia>{$prestacionMercancia}</mv:prestacionMercancia>{$especifiqueXml}
               </mv:compensoPago>";
        }
        return $xml;
    }

    /**
     * Construir XML de incrementables según XSD
     */
    private function buildIncrementablesXml(array $data, array &$mapping, int $coveIdx, array &$errors): string
    {
        $xml = '';
        foreach ($data as $idx => $item) {
            // XSD: tipoIncrementable (string), fechaErogacion (dateTime), importe (decimal), tipoMoneda (string), tipoCambio (decimal), aCargoImportador (long)
            $tipoIncrementable = $item['incrementable'] ?? $item['tipoIncrementable'] ?? '';
            $fechaErogacion = $this->formatDateTime($item['fechaErogacion'] ?? $item['fecha_erogacion'] ?? '');
            $importe = $this->formatDecimal($item['importe'] ?? '0.00');
            $tipoMoneda = $item['tipoMoneda'] ?? $item['tipo_moneda'] ?? '';
            $tipoCambio = $this->formatDecimal($item['tipoCambio'] ?? $item['tipo_cambio'] ?? '1.00');
            $aCargoImportador = ($item['aCargoImportador'] ?? $item['a_cargo_importador'] ?? false) ? '1' : '0';

            $mapping["incrementable_{$coveIdx}_{$idx}"] = [
                'campo_bd' => "mv_informacion_cove.incrementables[{$idx}]",
                'valor_bd' => $item,
                'campo_xsd' => 'informacionCove/incrementables',
                'tipo_xsd' => 'Incrementables (tipoIncrementable: string, fechaErogacion: dateTime, importe: decimal, tipoMoneda: string, tipoCambio: decimal, aCargoImportador: long)'
            ];

            $xml .= "
               <mv:incrementables>
                  <mv:tipoIncrementable>{$tipoIncrementable}</mv:tipoIncrementable>
                  <mv:fechaErogacion>{$fechaErogacion}</mv:fechaErogacion>
                  <mv:importe>{$importe}</mv:importe>
                  <mv:tipoMoneda>{$tipoMoneda}</mv:tipoMoneda>
                  <mv:tipoCambio>{$tipoCambio}</mv:tipoCambio>
                  <mv:aCargoImportador>{$aCargoImportador}</mv:aCargoImportador>
               </mv:incrementables>";
        }
        return $xml;
    }

    /**
     * Construir XML de decrementables según XSD
     */
    private function buildDecrementablesXml(array $data, array &$mapping, int $coveIdx, array &$errors): string
    {
        $xml = '';
        foreach ($data as $idx => $item) {
            // XSD: tipoDecrementable (string), fechaErogacion (dateTime), importe (decimal), tipoMoneda (string), tipoCambio (decimal)
            $tipoDecrementable = $item['decrementable'] ?? $item['tipoDecrementable'] ?? '';
            $fechaErogacion = $this->formatDateTime($item['fechaErogacion'] ?? $item['fecha_erogacion'] ?? '');
            $importe = $this->formatDecimal($item['importe'] ?? '0.00');
            $tipoMoneda = $item['tipoMoneda'] ?? $item['tipo_moneda'] ?? '';
            $tipoCambio = $this->formatDecimal($item['tipoCambio'] ?? $item['tipo_cambio'] ?? '1.00');

            $mapping["decrementable_{$coveIdx}_{$idx}"] = [
                'campo_bd' => "mv_informacion_cove.decrementables[{$idx}]",
                'valor_bd' => $item,
                'campo_xsd' => 'informacionCove/decrementables',
                'tipo_xsd' => 'Decrementables (tipoDecrementable: string, fechaErogacion: dateTime, importe: decimal, tipoMoneda: string, tipoCambio: decimal)'
            ];

            $xml .= "
               <mv:decrementables>
                  <mv:tipoDecrementable>{$tipoDecrementable}</mv:tipoDecrementable>
                  <mv:fechaErogacion>{$fechaErogacion}</mv:fechaErogacion>
                  <mv:importe>{$importe}</mv:importe>
                  <mv:tipoMoneda>{$tipoMoneda}</mv:tipoMoneda>
                  <mv:tipoCambio>{$tipoCambio}</mv:tipoCambio>
               </mv:decrementables>";
        }
        return $xml;
    }

    /**
     * Formatear fecha a formato ISO 8601 (xsd:dateTime)
     */
    private function formatDateTime(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            // Intentar parsear varios formatos de fecha
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d\TH:i:s',
                'Y-m-d',
                'd/m/Y H:i:s',
                'd/m/Y',
                'd-m-Y',
                'dmY'  // Formato de archivo M (ej: 15012025)
            ];

            foreach ($formats as $format) {
                $dateTime = \DateTime::createFromFormat($format, $date);
                if ($dateTime !== false) {
                    return $dateTime->format('Y-m-d\TH:i:s');
                }
            }

            // Si no se pudo parsear, intentar strtotime
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('Y-m-d\TH:i:s', $timestamp);
            }

            return $date;
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Formatear número a formato decimal válido para XSD
     */
    private function formatDecimal($value): string
    {
        if (empty($value) || $value === '') {
            return '0.00';
        }

        // Eliminar comas de miles y espacios
        $clean = preg_replace('/[,\s]/', '', (string)$value);
        
        // Asegurar que sea un número válido
        if (!is_numeric($clean)) {
            return '0.00';
        }

        return number_format((float)$clean, 2, '.', '');
    }

    /**
     * Formatear XML para legibilidad
     */
    private function formatXml(string $xml): string
    {
        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml);
            return $dom->saveXML();
        } catch (Exception $e) {
            return $xml;
        }
    }

    /**
     * Validar estructura XML básica
     */
    private function validateXmlStructure(string $xml): array
    {
        $validation = [
            'xml_valid' => false,
            'has_envelope' => false,
            'has_header' => false,
            'has_body' => false,
            'has_registro' => false,
            'errors' => []
        ];

        try {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $result = $dom->loadXML($xml);
            
            if ($result) {
                $validation['xml_valid'] = true;
                $validation['has_envelope'] = $dom->getElementsByTagNameNS(self::NS_SOAP, 'Envelope')->length > 0;
                $validation['has_header'] = $dom->getElementsByTagNameNS(self::NS_SOAP, 'Header')->length > 0;
                $validation['has_body'] = $dom->getElementsByTagNameNS(self::NS_SOAP, 'Body')->length > 0;
                $validation['has_registro'] = strpos($xml, 'registroManifestacion') !== false;
            }

            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $validation['errors'][] = trim($error->message);
            }
            libxml_clear_errors();

        } catch (Exception $e) {
            $validation['errors'][] = $e->getMessage();
        }

        return $validation;
    }

    /**
     * Enviar XML a VUCEM (o simular en modo prueba)
     */
    public function sendToVucem(
        string $xml,
        string $rfc,
        string $claveWebService,
        bool $testMode = true
    ): array {
        $endpoint = config('vucem.mv_endpoint');

        Log::info('[MV_SOAP] Iniciando envío a VUCEM', [
            'rfc' => $rfc,
            'test_mode' => $testMode,
            'endpoint' => $endpoint
        ]);

        if ($testMode) {
            // Modo prueba: no enviar, solo validar
            return [
                'success' => true,
                'mode' => 'test',
                'message' => 'MODO PRUEBA: XML generado correctamente. No se envió a VUCEM.',
                'xml_sent' => $xml,
                'response' => null,
                'numero_operacion' => 'TEST-' . date('YmdHis')
            ];
        }

        try {
            // Usar cURL directo para mejor control de SSL
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => trim($xml),
                CURLOPT_TIMEOUT => config('vucem.soap_timeout', 120),
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ""',
                    'Content-Length: ' . strlen(trim($xml))
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('[MV_SOAP] Error cURL', ['error' => $curlError]);
                return [
                    'success' => false,
                    'mode' => 'production',
                    'message' => 'Error de conexión: ' . $curlError,
                    'xml_sent' => $xml,
                    'response' => null
                ];
            }

            Log::info('[MV_SOAP] Respuesta recibida de VUCEM', [
                'status' => $httpCode,
                'body_length' => strlen($responseBody)
            ]);

            // Parsear respuesta
            return $this->parseVucemResponse($responseBody, $xml);

        } catch (Exception $e) {
            Log::error('[MV_SOAP] Error al enviar a VUCEM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'mode' => 'production',
                'message' => 'Error de conexión con VUCEM: ' . $e->getMessage(),
                'xml_sent' => $xml,
                'response' => null
            ];
        }
    }

    /**
     * Parsear respuesta de VUCEM
     */
    private function parseVucemResponse(string $responseBody, string $xmlSent): array
    {
        $result = [
            'success' => false,
            'mode' => 'production',
            'message' => '',
            'xml_sent' => $xmlSent,
            'response' => $responseBody,
            'numero_operacion' => null,
            'errores' => []
        ];

        try {
            // Buscar número de operación
            if (preg_match('/<[:\w]*numeroOperacion>(.*?)<\/[:\w]*numeroOperacion>/', $responseBody, $matches)) {
                $result['numero_operacion'] = $matches[1];
                $result['success'] = true;
                $result['message'] = 'Manifestación registrada correctamente. Número de operación: ' . $matches[1];
            }

            // Buscar errores
            if (preg_match_all('/<[:\w]*mensaje>.*?<[:\w]*codigoError>(.*?)<\/[:\w]*codigoError>.*?<[:\w]*descripcionError>(.*?)<\/[:\w]*descripcionError>.*?<\/[:\w]*mensaje>/s', $responseBody, $errorMatches, PREG_SET_ORDER)) {
                foreach ($errorMatches as $error) {
                    $result['errores'][] = [
                        'codigo' => $error[1],
                        'descripcion' => $error[2]
                    ];
                }
                
                if (!empty($result['errores'])) {
                    $result['success'] = false;
                    $result['message'] = 'VUCEM rechazó la solicitud: ' . $result['errores'][0]['descripcion'];
                }
            }

            // Si no encontramos nada específico
            if (empty($result['message'])) {
                $result['message'] = 'Respuesta recibida de VUCEM (revisar logs para detalles)';
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Obtener información de mapeo de campos para documentación
     */
    public function getFieldMappingInfo(): array
    {
        return [
            'rfc_importador' => [
                'bd' => 'mv_datos_manifestacion.rfc_importador',
                'xsd' => 'importador-exportador/rfc',
                'tipo' => 'string',
                'requerido' => true,
                'descripcion' => 'RFC del importador o exportador'
            ],
            'persona_consulta' => [
                'bd' => 'mv_datos_manifestacion.persona_consulta (JSON array)',
                'xsd' => 'datosManifestacionValor/personaConsulta[]',
                'tipo' => 'PersonaConsulta[]',
                'requerido' => false,
                'campos' => ['rfc', 'tipoFigura'],
                'descripcion' => 'Personas autorizadas a consultar la manifestación'
            ],
            'documentos' => [
                'bd' => 'mv_documentos.documentos (JSON array)',
                'xsd' => 'datosManifestacionValor/documentos[]',
                'tipo' => 'Documento[]',
                'requerido' => true,
                'campos' => ['eDocument'],
                'descripcion' => 'eDocuments de los documentos digitalizados'
            ],
            'informacion_cove' => [
                'bd' => 'mv_informacion_cove.informacion_cove (JSON array)',
                'xsd' => 'datosManifestacionValor/informacionCove[]',
                'tipo' => 'InformacionCove[]',
                'requerido' => true,
                'campos' => ['cove', 'incoterm', 'existeVinculacion', 'metodoValoracion'],
                'descripcion' => 'Información de cada COVE relacionado'
            ],
            'pedimentos' => [
                'bd' => 'mv_informacion_cove.pedimentos (JSON array)',
                'xsd' => 'informacionCove/pedimento[]',
                'tipo' => 'Pedimento[]',
                'requerido' => false,
                'campos' => ['pedimento', 'patente', 'aduana'],
                'descripcion' => 'Pedimentos relacionados'
            ],
            'precio_pagado' => [
                'bd' => 'mv_informacion_cove.precio_pagado (JSON array)',
                'xsd' => 'informacionCove/precioPagado[]',
                'tipo' => 'PrecioPagado[]',
                'requerido' => true,
                'campos' => ['fechaPago', 'total', 'tipoPago', 'especifique?', 'tipoMoneda', 'tipoCambio'],
                'descripcion' => 'Desglose de precios pagados'
            ],
            'precio_por_pagar' => [
                'bd' => 'mv_informacion_cove.precio_por_pagar (JSON array)',
                'xsd' => 'informacionCove/precioPorPagar[]',
                'tipo' => 'PrecioPorPagar[]',
                'requerido' => true,
                'campos' => ['fechaPago', 'total', 'situacionNofechaPago?', 'tipoPago', 'especifique?', 'tipoMoneda', 'tipoCambio'],
                'descripcion' => 'Desglose de precios por pagar'
            ],
            'compenso_pago' => [
                'bd' => 'mv_informacion_cove.compenso_pago (JSON array)',
                'xsd' => 'informacionCove/compensoPago[]',
                'tipo' => 'CompensoPago[]',
                'requerido' => true,
                'campos' => ['tipoPago', 'fecha', 'motivo', 'prestacionMercancia', 'especifique?'],
                'descripcion' => 'Compensaciones de pago'
            ],
            'incrementables' => [
                'bd' => 'mv_informacion_cove.incrementables (JSON array)',
                'xsd' => 'informacionCove/incrementables[]',
                'tipo' => 'Incrementables[]',
                'requerido' => true,
                'campos' => ['tipoIncrementable', 'fechaErogacion', 'importe', 'tipoMoneda', 'tipoCambio', 'aCargoImportador'],
                'descripcion' => 'Conceptos incrementables al valor'
            ],
            'decrementables' => [
                'bd' => 'mv_informacion_cove.decrementables (JSON array)',
                'xsd' => 'informacionCove/decrementables[]',
                'tipo' => 'Decrementables[]',
                'requerido' => true,
                'campos' => ['tipoDecrementable', 'fechaErogacion', 'importe', 'tipoMoneda', 'tipoCambio'],
                'descripcion' => 'Conceptos decrementables al valor'
            ],
            'valor_en_aduana' => [
                'bd' => 'mv_informacion_cove.valor_en_aduana (JSON)',
                'xsd' => 'datosManifestacionValor/valorEnAduana',
                'tipo' => 'ValorEnAduana',
                'requerido' => true,
                'campos' => ['totalPrecioPagado', 'totalPrecioPorPagar', 'totalIncrementables', 'totalDecrementables', 'totalValorAduana'],
                'descripcion' => 'Totales del valor en aduana'
            ]
        ];
    }
}
