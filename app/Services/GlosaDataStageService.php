<?php

namespace App\Services;

use App\Models\GlosaImport;
use App\Models\Glosa501DatosGenerales;
use App\Models\Glosa505Factura;
use App\Models\Glosa510Contribucion;
use App\Models\Glosa551Partida;
use App\Models\Glosa557ContribucionPartida;
use App\Models\Glosa701Rectificacion;
use App\Models\GlosaVaultRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;
use Exception;

class GlosaDataStageService
{
    /**
     * Mapa oficial de Bóvedas VOCE-SAAI M3 / Data Stage
     */
    public const VAULT_NAMES = [
        '501'     => '501_Datos_Generales',
        '502'     => '502_Transporte',
        '503'     => '503_Guias',
        '504'     => '504_Contenedores',
        '505'     => '505_Facturas',
        '506'     => '506_Fechas',
        '507'     => '507_Casos_Pedimento',
        '508'     => '508_Cuentas_Garantia',
        '509'     => '509_Tasas_Pedimento',
        '510'     => '510_Contribuciones',
        '511'     => '511_Observaciones',
        '512'     => '512_Descargos',
        '514'     => '514_Movimientos_Cuenta',
        '520'     => '520_Destinatarios',
        '551'     => '551_Partidas',
        '552'     => '552_Mercancias',
        '553'     => '553_Permisos_Partida',
        '554'     => '554_Casos_Partida',
        '555'     => '555_Cuentas_Garantia_Partida',
        '556'     => '556_Tasas_Partida',
        '557'     => '557_Contribuciones_Partida',
        '558'     => '558_Observaciones_Partida',
        '701'     => '701_Rectificaciones',
        '702'     => '702_Diferencias_Contribuciones',
        'Sel'     => 'Seleccion_Automatizada',
        'Inci'    => 'Incidencias',
        'Resumen' => 'Resumen_Solicitud',
    ];

    /**
     * Ingesta y procesa un archivo ZIP de Data Stage
     */
    public function processZipFile(UploadedFile|string $zipInput, User $user): GlosaImport
    {
        $adminId = ($user->role === 'Admin' || $user->role === 'SuperAdmin') 
            ? $user->id 
            : ($user->created_by ?? $user->id);

        $filePath = $zipInput instanceof UploadedFile ? $zipInput->getRealPath() : $zipInput;
        $originalFilename = $zipInput instanceof UploadedFile ? $zipInput->getClientOriginalName() : basename($zipInput);

        $import = GlosaImport::create([
            'user_id'           => $user->id,
            'admin_id'          => $adminId,
            'original_filename' => $originalFilename,
            'status'            => 'processing',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            $import->update([
                'status'        => 'failed',
                'error_message' => 'No se pudo abrir el archivo ZIP.',
            ]);
            throw new Exception('No se pudo abrir el archivo ZIP.');
        }

        try {
            DB::beginTransaction();

            $filesCount = 0;
            $vaultRecordsBatch = [];
            $datos501Batch = [];
            $facturas505Batch = [];
            $contrib510Batch = [];
            $partidas551Batch = [];
            $contrib557Batch = [];
            $rectif701Batch = [];

            $totalValorDolares505 = 0;
            $totalValorDolares551 = 0;
            $totalContribuciones = 0;
            $folio = null;
            $rfc = null;
            $fechaInicial = null;
            $fechaFinal = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($ext, ['asc', 'txt'])) {
                    continue;
                }

                $filesCount++;
                $vaultCode = $this->extractVaultCode($filename);
                $sheetName = self::VAULT_NAMES[$vaultCode] ?? "Boveda_{$vaultCode}";
                $content = $zip->getFromIndex($i);

                if ($content === false) {
                    continue;
                }

                $lines = explode("\n", str_replace("\r", "", $content));
                if (empty($lines)) {
                    continue;
                }

                // Fila 1 = Encabezados
                $headerLine = array_shift($lines);
                $headers = array_map(fn($h) => trim($h), explode('|', rtrim($headerLine, '|')));

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }

                    $values = array_map(fn($v) => trim($v), explode('|', rtrim($line, '|')));
                    $rowData = [];
                    foreach ($headers as $idx => $headerName) {
                        $rowData[$headerName] = $values[$idx] ?? '';
                    }

                    // Construcción de clave_operacion (Patente-Pedimento-SeccionAduanera)
                    $claveOperacion = $this->extractClaveOperacion($rowData);
                    $rowData['clave_operacion'] = $claveOperacion;

                    // Procesamiento específico por Bóveda
                    if ($vaultCode === '501') {
                        $datos501Batch[] = [
                            'import_id'            => $import->id,
                            'admin_id'             => $adminId,
                            'clave_operacion'      => $claveOperacion,
                            'patente'              => $rowData['Patente'] ?? null,
                            'pedimento'            => $rowData['Pedimento'] ?? null,
                            'seccion_aduanera'     => $rowData['SeccionAduanera'] ?? null,
                            'tipo_operacion'       => $rowData['TipoOperacion'] ?? null,
                            'clave_documento'      => $rowData['ClaveDocumento'] ?? null,
                            'seccion_aduanera_entrada' => $rowData['SeccionAduaneraEntrada'] ?? null,
                            'curp_contribuyente'   => $rowData['CurpContribuyente'] ?? null,
                            'rfc'                  => $rowData['Rfc'] ?? null,
                            'curp_agente'          => $rowData['CurpAgenteA'] ?? $rowData['CurpAgente'] ?? null,
                            'tipo_cambio'          => $this->parseFloat($rowData['TipoCambio'] ?? '1'),
                            'total_fletes'         => $this->parseFloat($rowData['TotalFletes'] ?? '0'),
                            'total_seguros'        => $this->parseFloat($rowData['TotalSeguros'] ?? '0'),
                            'total_embalajes'      => $this->parseFloat($rowData['TotalEmbalajes'] ?? '0'),
                            'total_incrementables' => $this->parseFloat($rowData['TotalIncrementables'] ?? '0'),
                            'total_deducibles'     => $this->parseFloat($rowData['TotalDeducibles'] ?? '0'),
                            'peso_bruto'           => $this->parseFloat($rowData['PesoBrutoMercancia'] ?? $rowData['PesoBruto'] ?? '0'),
                            'medio_transporte_salida' => $rowData['MedioTransporteSalida'] ?? null,
                            'medio_transporte_arribo' => $rowData['MedioTransporteArribo'] ?? null,
                            'medio_transporte_entrada_salida' => $rowData['MedioTransporteEntrada_Salida'] ?? null,
                            'destino_mercancia'    => $rowData['DestinoMercancia'] ?? null,
                            'nombre_contribuyente' => $rowData['NombreContribuyente'] ?? null,
                            'tipo_pedimento'       => $rowData['TipoPedimento'] ?? null,
                            'fecha_pago_real'      => $this->parseDate($rowData['FechaPagoReal'] ?? null),
                            'raw_data'             => json_encode($rowData),
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];
                        if (empty($rfc) && !empty($rowData['Rfc'])) {
                            $rfc = $rowData['Rfc'];
                        }
                    } elseif ($vaultCode === '505') {
                        $valUsd = $this->parseFloat($rowData['ValorDolares'] ?? $rowData['ValorFacturaUSD'] ?? '0');
                        $totalValorDolares505 += $valUsd;
                        $facturas505Batch[] = [
                            'import_id'         => $import->id,
                            'admin_id'          => $adminId,
                            'clave_operacion'   => $claveOperacion,
                            'numero_factura'    => $rowData['NumeroFactura'] ?? $rowData['NumeroFacturacion'] ?? null,
                            'fecha_factura'     => $this->parseDate($rowData['FechaFacturacion'] ?? null),
                            'incoterm'          => $rowData['TerminoFacturacion'] ?? null,
                            'moneda'            => $rowData['MonedaFacturacion'] ?? null,
                            'valor_dolares'     => $valUsd,
                            'valor_moneda_extranjera' => $this->parseFloat($rowData['ValorMonedaExtranjera'] ?? '0'),
                            'proveedor_nombre'  => $rowData['ProveedorMercancia'] ?? null,
                            'proveedor_tax_id'  => $rowData['IndentFiscalProveedor'] ?? $rowData['IdentificacionFiscalProveedor'] ?? null,
                            'raw_data'          => json_encode($rowData),
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    } elseif ($vaultCode === '510') {
                        $imp = $this->parseFloat($rowData['ImportePago'] ?? '0');
                        $totalContribuciones += $imp;
                        $contrib510Batch[] = [
                            'import_id'         => $import->id,
                            'admin_id'          => $adminId,
                            'clave_operacion'   => $claveOperacion,
                            'clave_contribucion'=> $rowData['ClaveContribucion'] ?? null,
                            'forma_pago'        => $rowData['FormaPago'] ?? $rowData['ClaveFormaPago'] ?? null,
                            'importe'           => $imp,
                            'fecha_pago_real'   => $this->parseDate($rowData['FechaPagoReal'] ?? null),
                            'raw_data'          => json_encode($rowData),
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    } elseif ($vaultCode === '551') {
                        $fraccionVal = $rowData['Fraccion'] ?? $rowData['FraccionArancelaria'] ?? null;
                        $valPartidaUsd = $this->parseFloat($rowData['ValorDolares'] ?? '0');
                        $totalValorDolares551 += $valPartidaUsd;
                        $partidas551Batch[] = [
                            'import_id'            => $import->id,
                            'admin_id'             => $adminId,
                            'clave_operacion'      => $claveOperacion,
                            'secuencia'            => (int)($rowData['SecuenciaFraccion'] ?? $rowData['SecuenciaFraccionArancelaria'] ?? 1),
                            'fraccion_arancelaria' => $fraccionVal,
                            'subdivision'          => $rowData['SubdivisionFraccion'] ?? $rowData['SubdivisionFraccionArancelaria'] ?? null,
                            'descripcion_mercancia'=> $rowData['DescripcionMercancia'] ?? null,
                            'precio_unitario'      => $this->parseFloat($rowData['PrecioUnitario'] ?? '0'),
                            'valor_aduana'         => $this->parseFloat($rowData['ValorAduana'] ?? '0'),
                            'valor_comercial'      => $this->parseFloat($rowData['ValorComercial'] ?? '0'),
                            'valor_dolares'        => $valPartidaUsd,
                            'cantidad_umc'         => $this->parseFloat($rowData['CantidadUMComercial'] ?? $rowData['CantidadMercanciaUMC'] ?? '0'),
                            'umc'                  => $rowData['UnidadMedidaComercial'] ?? $rowData['ClaveUMC'] ?? null,
                            'cantidad_umt'         => $this->parseFloat($rowData['CantidadUMTarifa'] ?? $rowData['CantidadMercanciaUMT'] ?? '0'),
                            'umt'                  => $rowData['UnidadMedidaTarifa'] ?? $rowData['ClaveUMT'] ?? null,
                            'pais_origen_destino'  => $rowData['PaisOrigenDestino'] ?? null,
                            'fecha_pago_real'      => $this->parseDate($rowData['FechaPagoReal'] ?? null),
                            'raw_data'             => json_encode($rowData),
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];
                    } elseif ($vaultCode === '557') {
                        $contrib557Batch[] = [
                            'import_id'         => $import->id,
                            'admin_id'          => $adminId,
                            'clave_operacion'   => $claveOperacion,
                            'secuencia'         => (int)($rowData['SecuenciaFraccion'] ?? $rowData['SecuenciaFraccionArancelaria'] ?? 1),
                            'clave_contribucion'=> $rowData['ClaveContribucion'] ?? null,
                            'forma_pago'        => $rowData['FormaPago'] ?? $rowData['ClaveFormaPago'] ?? null,
                            'importe'           => $this->parseFloat($rowData['ImportePago'] ?? '0'),
                            'fecha_pago_real'   => $this->parseDate($rowData['FechaPagoReal'] ?? null),
                            'raw_data'          => json_encode($rowData),
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    } elseif ($vaultCode === '701') {
                        $claveOrig = null;
                        if (!empty($rowData['PedimentoOriginal'])) {
                            $claveOrig = ($rowData['PatenteAduanalOrig'] ?? '') . '-' . $rowData['PedimentoOriginal'] . '-' . ($rowData['SeccionAduaneraDespOrig'] ?? '');
                        }
                        $rectif701Batch[] = [
                            'import_id'               => $import->id,
                            'admin_id'                => $adminId,
                            'clave_operacion'         => $claveOperacion,
                            'clave_operacion_original'=> $claveOrig,
                            'pedimento_anterior'      => $rowData['PedimentoAnterior'] ?? null,
                            'patente_anterior'        => $rowData['PatenteAnterior'] ?? null,
                            'seccion_anterior'        => $rowData['SeccionAduaneraAnterior'] ?? null,
                            'pedimento_original'       => $rowData['PedimentoOriginal'] ?? null,
                            'patente_original'        => $rowData['PatenteAduanalOrig'] ?? null,
                            'seccion_original'        => $rowData['SeccionAduaneraDespOrig'] ?? null,
                            'fecha_pago_rectificacion'=> $this->parseDate($rowData['FechaPago'] ?? null),
                            'fecha_pago_original'     => $this->parseDate($rowData['FechaPagoReal'] ?? null),
                            'raw_data'                => json_encode($rowData),
                            'created_at'              => now(),
                            'updated_at'              => now(),
                        ];
                    } elseif ($vaultCode === 'Resumen') {
                        $folio = $rowData['Folio'] ?? null;
                        $rfc = $rowData['RFCoPatenteAduanal'] ?? $rfc;
                        $fechaInicial = $this->parseDate($rowData['Fecha_Inicial'] ?? null);
                        $fechaFinal = $this->parseDate($rowData['Fecha_Final'] ?? null);
                    }

                    // Guardar registro en la boveda genérica
                    $vaultRecordsBatch[] = [
                        'import_id'       => $import->id,
                        'admin_id'        => $adminId,
                        'vault_code'      => $vaultCode,
                        'sheet_name'      => $sheetName,
                        'clave_operacion' => $claveOperacion,
                        'data'            => json_encode($rowData),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
            }

            $zip->close();

            // Inserción en bloques (Chunk Insert)
            foreach (array_chunk($datos501Batch, 500) as $chunk) {
                Glosa501DatosGenerales::insert($chunk);
            }
            foreach (array_chunk($facturas505Batch, 500) as $chunk) {
                Glosa505Factura::insert($chunk);
            }
            foreach (array_chunk($contrib510Batch, 500) as $chunk) {
                Glosa510Contribucion::insert($chunk);
            }
            foreach (array_chunk($partidas551Batch, 500) as $chunk) {
                Glosa551Partida::insert($chunk);
            }
            foreach (array_chunk($contrib557Batch, 500) as $chunk) {
                Glosa557ContribucionPartida::insert($chunk);
            }
            foreach (array_chunk($rectif701Batch, 500) as $chunk) {
                Glosa701Rectificacion::insert($chunk);
            }
            foreach (array_chunk($vaultRecordsBatch, 500) as $chunk) {
                GlosaVaultRecord::insert($chunk);
            }

            // Actualización final del estado de la Importación
            $import->update([
                'folio'                => $folio,
                'rfc'                  => $rfc,
                'fecha_inicial'        => $fechaInicial,
                'fecha_final'          => $fechaFinal,
                'total_files'          => $filesCount,
                'total_pedimentos'     => count($datos501Batch),
                'total_partidas'       => count($partidas551Batch),
                'total_valor_dolares'  => max($totalValorDolares505, $totalValorDolares551),
                'total_contribuciones' => $totalContribuciones,
                'status'               => 'completed',
            ]);

            DB::commit();

            Log::info("[GLOSA DATA STAGE] Importación completada con éxito. ID: {$import->id}, Pedimentos: " . count($datos501Batch));
            return $import;
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($zip)) {
                @$zip->close();
            }
            $import->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('[GLOSA DATA STAGE] Error durante la ingesta del ZIP: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extrae el código de bóveda a partir del nombre del archivo plano (ej. 1920833_501.asc -> 501)
     */
    protected function extractVaultCode(string $filename): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        if (str_contains($basename, '_')) {
            $parts = explode('_', $basename);
            return end($parts);
        }
        return $basename;
    }

    /**
     * Genera la clave de operación unificada: Patente-Pedimento-SeccionAduanera
     */
    protected function extractClaveOperacion(array $rowData): ?string
    {
        $patente = $rowData['Patente'] ?? $rowData['PatenteAduanal'] ?? null;
        $pedimento = $rowData['Pedimento'] ?? $rowData['NumeroPedimento'] ?? null;
        $aduana = $rowData['SeccionAduanera'] ?? $rowData['SeccionAduaneraDespacho'] ?? null;

        if (!empty($patente) && !empty($pedimento) && !empty($aduana)) {
            return "{$patente}-{$pedimento}-{$aduana}";
        }
        return null;
    }

    /**
     * Normaliza formato de fechas DDMMAAAA, DD/MM/AAAA o YYYY-MM-DD a YYYY-MM-DD
     */
    protected function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $val = trim($value);
        if ($val === '' || $val === '0') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                return $val;
            }
            if (preg_match('/^\d{8}$/', $val)) {
                return Carbon::createFromFormat('dmY', $val)->format('Y-m-d');
            }
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $val)) {
                return Carbon::createFromFormat('d/m/Y', $val)->format('Y-m-d');
            }
            return Carbon::parse($val)->format('Y-m-d');
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Parser seguro de flotantes/decimales
     */
    protected function parseFloat(?string $value): float
    {
        if (empty($value)) {
            return 0.0;
        }
        $cleaned = str_replace(',', '', trim($value));
        return is_numeric($cleaned) ? (float)$cleaned : 0.0;
    }
}
