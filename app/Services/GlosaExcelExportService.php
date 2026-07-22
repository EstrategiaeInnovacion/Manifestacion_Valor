<?php

namespace App\Services;

use App\Models\GlosaImport;
use App\Models\GlosaVaultRecord;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Exception;

class GlosaExcelExportService
{
    /**
     * Diccionario de Encabezados Descriptivos en Español (HUMAN-READABLE)
     */
    public const DESCRIPTIVE_HEADERS = [
        'clave_operacion'          => 'Clave de Operación (Patente-Pedimento-Aduana)',
        'Patente'                  => 'Patente Aduanal',
        'Pedimento'                => 'Número de Pedimento',
        'SeccionAduanera'          => 'Sección Aduanera de Despacho',
        'TipoOperacion'            => 'Tipo de Operación (1:Imp / 2:Exp)',
        'ClaveDocumento'           => 'Clave de Documento',
        'SeccionAduaneraEntrada'   => 'Sección Aduanera de Entrada',
        'CurpContribuyente'        => 'CURP del Contribuyente',
        'Rfc'                      => 'RFC del Contribuyente',
        'CurpAgenteA'              => 'CURP Agente / Apoderado Aduanal',
        'CurpAgente'               => 'CURP Agente / Apoderado Aduanal',
        'TipoCambio'               => 'Tipo de Cambio (MXN/USD)',
        'TotalFletes'              => 'Total Fletes ($)',
        'TotalSeguros'             => 'Total Seguros ($)',
        'TotalEmbalajes'           => 'Total Embalajes ($)',
        'TotalIncrementables'      => 'Total Incrementables ($)',
        'TotalDeducibles'          => 'Total Deducibles ($)',
        'PesoBrutoMercancia'       => 'Peso Bruto (KG)',
        'MedioTransporteSalida'    => 'Medio Transporte Salida',
        'MedioTransporteArribo'    => 'Medio Transporte Arribo',
        'MedioTransporteEntrada_Salida' => 'Medio Transporte Entrada / Salida',
        'DestinoMercancia'         => 'Destino de la Mercancía',
        'NombreContribuyente'      => 'Nombre / Razón Social Contribuyente',
        'TipoPedimento'            => 'Tipo de Pedimento',
        'FechaRecepcionPedimento'  => 'Fecha Recepción Pedimento',
        'FechaPagoReal'            => 'Fecha de Pago Real (Validación)',
        'RfcTransportista'         => 'RFC del Transportista',
        'CurpTransportista'        => 'CURP del Transportista',
        'NombreTransportista'      => 'Nombre del Transportista',
        'PaisTransporte'           => 'País del Transporte',
        'IdentificadorTransporte'  => 'Identificador del Transporte',
        'NumeroGuia'               => 'Número de Guía / Manifiesto',
        'TipoGuia'                 => 'Tipo de Guía',
        'NumContenedor'            => 'Número de Contenedor',
        'TipoContenedor'           => 'Tipo de Contenedor',
        'FechaFacturacion'         => 'Fecha de Facturación',
        'NumeroFactura'            => 'Número de Factura',
        'NumeroFacturacion'        => 'Número de Factura',
        'TerminoFacturacion'       => 'Término de Facturación (Incoterm)',
        'MonedaFacturacion'        => 'Moneda de Facturación',
        'ValorDolares'             => 'Valor Comercial en Dólares (USD)',
        'ValorFacturaUSD'          => 'Valor en Dólares (USD)',
        'ValorMonedaExtranjera'    => 'Valor en Moneda Extranjera',
        'PaisFacturacion'          => 'País de Facturación',
        'EntidadFedFacturacion'    => 'Entidad Federativa Facturación',
        'IndentFiscalProveedor'    => 'ID Fiscal del Proveedor (Tax ID)',
        'ProveedorMercancia'       => 'Nombre del Proveedor',
        'TipoFecha'                => 'Tipo de Fecha',
        'FechaOperacion'           => 'Fecha de Operación',
        'ClaveCaso'                => 'Clave de Caso',
        'IdentificadorCaso'        => 'Identificador de Caso',
        'ComplementoCaso'          => 'Complemento de Caso',
        'InstitucionEmisora'       => 'Institución Emisora Cuenta Garantía',
        'NumeroCuenta'             => 'Número de Cuenta',
        'FolioConstancia'          => 'Folio de Constancia',
        'FechaConstancia'          => 'Fecha de Constancia',
        'TipoCuenta'               => 'Tipo de Cuenta',
        'ClaveGarantia'            => 'Clave de Garantía',
        'ValorUnitarioTitulo'      => 'Valor Unitario Título',
        'TotalGarantia'            => 'Total de Garantía',
        'CantidadUnidades'         => 'Cantidad Unidades Medida',
        'TitulosAsignados'         => 'Títulos Asignados',
        'ClaveContribucion'        => 'Clave de Contribución',
        'TasaContribucion'         => 'Tasa de Contribución (%)',
        'TipoTasa'                 => 'Tipo de Tasa',
        'FormaPago'                => 'Forma de Pago Contribución',
        'ImportePago'              => 'Importe Pagado ($)',
        'SecuenciaObservacion'     => 'Secuencia Observación',
        'Observaciones'            => 'Observaciones',
        'PedimentoOriginal'        => 'Número de Pedimento Original',
        'PatenteAduanalOrig'       => 'Patente Aduanal Original',
        'SeccionAduaneraDespOrig'  => 'Sección Aduanera Original',
        'FraccionOriginal'         => 'Fracción Arancelaria Original',
        'UnidadMedida'             => 'Unidad de Medida',
        'MercanciaDescargada'      => 'Cantidad Descargada',
        'NombreDestinatarioMercancia' => 'Nombre del Destinatario',
        'Fraccion'                 => 'Fracción Arancelaria',
        'FraccionArancelaria'      => 'Fracción Arancelaria',
        'SecuenciaFraccion'        => 'Secuencia de Fracción',
        'SubdivisionFraccion'      => 'Subdivisión de Fracción',
        'DescripcionMercancia'     => 'Descripción de la Mercancía',
        'PrecioUnitario'           => 'Precio Unitario',
        'ValorAduana'              => 'Valor en Aduana ($)',
        'ValorComercial'           => 'Valor Comercial ($)',
        'CantidadUMComercial'      => 'Cantidad en U.M. Comercial',
        'UnidadMedidaComercial'    => 'Unidad de Medida Comercial',
        'CantidadUMTarifa'         => 'Cantidad en U.M. Tarifa',
        'UnidadMedidaTarifa'       => 'Unidad de Medida Tarifa',
        'PaisOrigenDestino'        => 'País Origen / Destino',
        'PaisCompradorVendedor'    => 'País Comprador / Vendedor',
        'VinNumeroSerie'           => 'VIN / Número de Serie',
        'KilometrajeVehiculo'      => 'Kilometraje del Vehículo',
        'ClavePermiso'             => 'Clave de Permiso',
        'FirmaDescargo'            => 'Firma de Descargo Permiso',
        'NumeroPermiso'            => 'Número de Permiso',
        'ValorComercialDolares'    => 'Valor Comercial en Dólares (USD)',
        'PedimentoAnterior'        => 'Número de Pedimento Anterior',
        'PatenteAnterior'          => 'Patente Aduanal Anterior',
        'SeccionAduaneraAnterior'  => 'Sección Aduanera Anterior',
        'SemaforoFiscal'           => 'Semáforo Fiscal (Selección Automatizada)',
        'GradoIncidencia'          => 'Grado de Incidencia (Reconocimiento)',
        'Folio'                    => 'Folio de Solicitud Data Stage',
        'RFCoPatenteAduanal'       => 'RFC o Patente Consultada',
        'Fecha_Inicial'            => 'Fecha Inicial Solicitada',
        'Fecha_Final'              => 'Fecha Final Solicitada',
        'Fecha_Ejecucion'          => 'Fecha de Ejecución Data Stage',
        'Total_Fracciones'         => 'Total de Fracciones Declaradas',
        'Total_Contribuciones'     => 'Total de Contribuciones Pagadas',
        'Estado'                   => 'Estado de la Bóveda',
    ];

    /**
     * Genera un archivo Excel (.xlsx) con exactamente 26 hojas organizadas por bóveda.
     */
    public function generateExcel(GlosaImport $import): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Eliminar la hoja predeterminada

        $vaultCodes = array_keys(GlosaDataStageService::VAULT_NAMES);

        foreach ($vaultCodes as $index => $vaultCode) {
            $sheetName = GlosaDataStageService::VAULT_NAMES[$vaultCode] ?? "Boveda_{$vaultCode}";
            // Nombre de hoja seguro para Excel (máx 31 caracteres)
            $safeTitle = substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], '_', $sheetName), 0, 31);

            $worksheet = $spreadsheet->createSheet($index);
            $worksheet->setTitle($safeTitle);

            // Consultar registros de la bóveda para esta importación
            $records = GlosaVaultRecord::where('import_id', $import->id)
                ->where('vault_code', $vaultCode)
                ->get();

            if ($records->isEmpty()) {
                // Hoja vacía con mensaje informativo y encabezados claros
                $worksheet->setCellValue('A1', self::DESCRIPTIVE_HEADERS['clave_operacion']);
                $worksheet->setCellValue('B1', self::DESCRIPTIVE_HEADERS['Estado']);
                $worksheet->setCellValue('A2', 'Sin registros');
                $worksheet->setCellValue('B2', 'Bóveda sin datos en la solicitud');
                $this->applyHeaderStyle($worksheet, 2);
                $this->autoSizeColumns($worksheet, 2);
                continue;
            }

            // Extraer nombres de columna desde el primer registro
            $firstData = $records->first()->data;
            if (is_string($firstData)) {
                $firstData = json_decode($firstData, true) ?? [];
            }

            $rawHeaders = array_keys($firstData);
            if (!in_array('clave_operacion', $rawHeaders)) {
                array_unshift($rawHeaders, 'clave_operacion');
            }

            // Escribir encabezados descriptivos legibles en fila 1
            $colIndex = 1;
            foreach ($rawHeaders as $headerKey) {
                $descriptiveName = self::DESCRIPTIVE_HEADERS[$headerKey] ?? ucwords(str_replace('_', ' ', $headerKey));
                $worksheet->setCellValue([$colIndex, 1], $descriptiveName);
                $colIndex++;
            }

            // Estilar encabezados (Fila 1)
            $lastColumnIndex = count($rawHeaders);
            $this->applyHeaderStyle($worksheet, $lastColumnIndex);

            // Escribir datos a partir de fila 2
            $rowIndex = 2;
            foreach ($records as $record) {
                $data = $record->data;
                if (is_string($data)) {
                    $data = json_decode($data, true) ?? [];
                }

                $colIdx = 1;
                foreach ($rawHeaders as $headerKey) {
                    $cellValue = $data[$headerKey] ?? '';
                    if (is_array($cellValue)) {
                        $cellValue = json_encode($cellValue);
                    }
                    $worksheet->setCellValue([$colIdx, $rowIndex], (string)$cellValue);
                    $colIdx++;
                }
                $rowIndex++;
            }

            // Ajuste holgado de ancho de columnas para evitar texto recortado
            $this->autoSizeColumns($worksheet, $lastColumnIndex);

            // Activar Filtro Automático en fila 1
            $highestColumnLetter = $worksheet->getHighestColumn();
            $worksheet->setAutoFilter("A1:{$highestColumnLetter}1");
        }

        // Guardar archivo .xlsx en almacenamiento temporal
        $tempDir = storage_path('app/glosa_exports');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = "Glosa_DataStage_{$import->folio}_{$import->id}.xlsx";
        $filePath = "{$tempDir}/{$filename}";

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Aplica diseño profesional a los encabezados del Excel (Fila 1)
     */
    protected function applyHeaderStyle($worksheet, int $columnCount): void
    {
        $highestColumnLetter = $worksheet->getHighestColumn();
        $headerRange = "A1:{$highestColumnLetter}1";

        $worksheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'name'  => 'Calibri',
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate Dark 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => false,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '0F172A'],
                ],
            ],
        ]);

        $worksheet->getRowDimension(1)->setRowHeight(30);
    }

    /**
     * Ajusta el ancho de las columnas holgadamente según la longitud de sus textos
     */
    protected function autoSizeColumns($worksheet, int $columnCount): void
    {
        for ($colIdx = 1; $colIdx <= $columnCount; $colIdx++) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $headerValue = (string)$worksheet->getCell([$colIdx, 1])->getValue();
            
            // Ancho basado en la longitud del texto del encabezado + margen de 5 caracteres (mínimo 20)
            $headerLength = mb_strlen($headerValue);
            $paddedWidth = max($headerLength + 5, 20);

            $worksheet->getColumnDimension($colLetter)->setAutoSize(false);
            $worksheet->getColumnDimension($colLetter)->setWidth($paddedWidth);
        }
    }
}
