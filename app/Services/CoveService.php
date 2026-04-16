<?php

namespace App\Services;

use App\Models\MvClientApplicant;
use App\Models\CoveDocument;
use Illuminate\Support\Facades\Log;

class CoveService
{
    private CoveVucemSoapService $coveVucemSoapService;
    private EFirmaService $efirmaService;

    public function __construct()
    {
        $this->coveVucemSoapService = new CoveVucemSoapService();
        $this->efirmaService = new EFirmaService();
    }

    /**
     * Construye la Cadena Original de COVE para Múltiples Comprobantes
     */
    public function buildCadenaOriginal(CoveDocument $coveDocument): string
    {
        $fields = [];
        $addField = function ($value) use (&$fields) {
            $cleanValue = str_replace('|', '', trim((string) $value));
            $fields[] = $cleanValue;
        };

        $covesData = $coveDocument->payload ?? [];
        
        foreach ($covesData as $cove) {
            $facturas = $cove['facturas'] ?? [];

            foreach ($facturas as $factura) {
                // Cadena Original de COVE (simplificada, adaptarla si es necesario más estricta):
                // VUCEM usualmente concatena:
                // |TipoOperacion|Patente|RFC|NumeroFactura|...etc|
                
                // NOTA: Para este ejemplo, alinearemos a una de las caderas más comunes de RecibirCove
                // Sin embargo, si la firma falla, se deberá revisar el anexo 22 y manual técnico VUCEM para COVE.
                
                $addField($factura['certificadoOrigen'] ?? '0');
                $addField($factura['numeroFacturaOriginal'] ?? $factura['factura'] ?? $factura['numeroFactura'] ?? 'S/N');
                $addField($factura['subdivision'] ?? '0');
                
                $emisor = $factura['emisor'] ?? [];
                if (!empty($emisor)) {
                    $addField($emisor['tipoIdentificador'] ?? '1');
                    $addField($emisor['identificacion'] ?? '');
                }

                $destinatario = $factura['destinatario'] ?? [];
                if (!empty($destinatario)) {
                    $addField($destinatario['tipoIdentificador'] ?? '1');
                    $addField($destinatario['identificacion'] ?? '');
                }

                $mercancias = $factura['mercancias'] ?? [];
                foreach ($mercancias as $mercancia) {
                    $addField($mercancia['descripcionGenerica'] ?? '');
                    $addField($mercancia['claveUnidadMedida'] ?? '');
                    $addField($this->formatDecimal($mercancia['cantidad'] ?? 0));
                    $addField($this->formatDecimal($mercancia['valorUnitario'] ?? 0));
                    $addField($this->formatDecimal($mercancia['valorTotal'] ?? 0));
                }
            }
        }

        // Importante: un pipeline al inicio y otro al final
        return '|' . implode('|', $fields) . '|';
    }

    /**
     * Orquesta la generación, firmado y obtención del XML listo para envío
     */
    public function prepararCoveXml(
        MvClientApplicant $applicant,
        CoveDocument $coveDocument,
        string $certificatePath,
        string $privateKeyPath,
        string $privateKeyPassword,
        string $claveWebservice
    ): array {
        try {
            // 1. Cadena Original
            $cadenaOriginal = $this->buildCadenaOriginal($coveDocument);

            Log::info('COVE - Cadena Original Generada', [
                'longitud' => strlen($cadenaOriginal),
                'CONTENIDO_CADENA' => $cadenaOriginal
            ]);

            // 2. Firma
            $firmaResult = $this->efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal,
                strtoupper($applicant->applicant_rfc),
                $certificatePath,
                $privateKeyPath,
                $privateKeyPassword
            );

            // 3. Envelope SOAP
            $soapEnvelopeResult = $this->coveVucemSoapService->buildRecibirCoveXml(
                $applicant,
                $coveDocument,
                strtoupper($applicant->applicant_rfc),
                $claveWebservice,
                $firmaResult
            );

            if (!$soapEnvelopeResult['success']) {
                return $soapEnvelopeResult;
            }

            Log::info('COVE - SOAP Envelope construido', [
                'envelope_size' => strlen($soapEnvelopeResult['xml'])
            ]);

            return [
                'success' => true,
                'cadena_original' => $cadenaOriginal,
                'xml' => $soapEnvelopeResult['xml']
            ];

        } catch (\Exception $e) {
            Log::error('COVE - Error preparando XML', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al preparar COVE: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }



    private function formatDecimal($value): string
    {
        if (empty($value) || $value === '') {
            return '0.000';
        }
        $clean = preg_replace('/[,\s]/', '', (string)$value);
        if (!is_numeric($clean)) return '0.000';
        return number_format((float)$clean, 3, '.', '');
    }
}
