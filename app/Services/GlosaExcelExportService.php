<?php

namespace App\Services;

use App\Models\GlosaImport;
use App\Models\GlosaVaultRecord;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Storage;
use Exception;

class GlosaExcelExportService
{
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
                // Hoja vacía con mensaje informativo
                $worksheet->setCellValue('A1', 'clave_operacion');
                $worksheet->setCellValue('B1', 'Estado');
                $worksheet->setCellValue('A2', 'Sin registros');
                $worksheet->setCellValue('B2', 'Bóveda sin datos en la solicitud');
                $this->applyHeaderStyle($worksheet, 2);
                continue;
            }

            // Extraer nombres de columna desde el primer registro
            $firstData = $records->first()->data;
            if (is_string($firstData)) {
                $firstData = json_decode($firstData, true) ?? [];
            }

            $headers = array_keys($firstData);
            if (!in_array('clave_operacion', $headers)) {
                array_unshift($headers, 'clave_operacion');
            }

            // Escribir encabezados en fila 1
            $colIndex = 1;
            foreach ($headers as $headerName) {
                $worksheet->setCellValue([$colIndex, 1], $headerName);
                $colIndex++;
            }

            // Estilar encabezados (Fila 1)
            $lastColumnIndex = count($headers);
            $this->applyHeaderStyle($worksheet, $lastColumnIndex);

            // Escribir datos a partir de fila 2
            $rowIndex = 2;
            foreach ($records as $record) {
                $data = $record->data;
                if (is_string($data)) {
                    $data = json_decode($data, true) ?? [];
                }

                $colIdx = 1;
                foreach ($headers as $headerName) {
                    $cellValue = $data[$headerName] ?? '';
                    if (is_array($cellValue)) {
                        $cellValue = json_encode($cellValue);
                    }
                    $worksheet->setCellValue([$colIdx, $rowIndex], (string)$cellValue);
                    $colIdx++;
                }
                $rowIndex++;
            }

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
     * Aplica diseño profesional a los encabezados del Excel
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
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '0F172A'],
                ],
            ],
        ]);

        $worksheet->getRowDimension(1)->setRowHeight(26);
    }
}
