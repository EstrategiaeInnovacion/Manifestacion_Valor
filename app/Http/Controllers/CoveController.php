<?php

namespace App\Http\Controllers;

use App\Models\Cove;
use App\Models\MvClientApplicant;
use App\Services\CoveService;
use App\Services\ManifestacionValorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class CoveController extends Controller
{
    private CoveService $coveService;

    public function __construct(CoveService $coveService)
    {
        $this->coveService = $coveService;
    }

    /**
     * Lista todos los COVEs
     */
    public function index()
    {
        $coves = Cove::with('applicant')->orderBy('created_at', 'desc')->paginate(10);
        return view('cove.index', compact('coves'));
    }

    /**
     * Muestra la pantalla para seleccionar solicitante
     */
    public function selectApplicant()
    {
        $applicants = MvClientApplicant::all();
        return view('cove.select-applicant', compact('applicants'));
    }

    /**
     * Formulario de carga de Archivo M
     */
    public function uploadForm(MvClientApplicant $applicant)
    {
        return view('cove.upload-file', compact('applicant'));
    }

    /**
     * Procesa la carga de Archivo M y lo convierte en un borrador de COVE
     */
    public function storeFromM(Request $request, MvClientApplicant $applicant)
    {
        $request->validate([
            'archivo_m' => 'required|file|max:2048'
        ]);

        try {
            $file = $request->file('archivo_m');
            $content = file_get_contents($file->getRealPath());
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, UTF-8');

            $mveService = new ManifestacionValorService();
            $datosExtraidos = $mveService->parseArchivoMForMV($content, false);

            $rfcArchivoM = $datosExtraidos['datos_manifestacion']['rfc_importador'] ?? null;
            $decryptedRfc = $applicant->applicant_rfc;

            if (empty($rfcArchivoM) || strtoupper($rfcArchivoM) !== strtoupper($decryptedRfc)) {
                return redirect()->back()->withErrors([
                    'archivo_m' => 'El RFC del archivo (' . ($rfcArchivoM ?: 'vacío') . ') no coincide con el del solicitante (' . $decryptedRfc . ').'
                ]);
            }

            // Mapeo a estructura JSON de COVE
            $emisor = [
                'tipoIdentificador' => 0, // TAX_ID
                'identificacion' => $datosExtraidos['proveedores'][0]['id_fiscal'] ?? '320656860',
                'nombre' => $datosExtraidos['proveedores'][0]['nombre'] ?? 'EXPONENTIAL TECHNOLOGY GROUP, INC.',
                'domicilio' => [
                    'calle' => 'MARK IV PARKWAY',
                    'numeroExterior' => '5050',
                    'numeroInterior' => '',
                    'codigoPostal' => '76106',
                    'colonia' => 'TEXAS',
                    'localidad' => 'FORT WORTH',
                    'municipio' => 'FORT WORTH',
                    'entidadFederativa' => 'TEXAS',
                    'pais' => 'ESTADOS UNIDOS DE AMERICA'
                ]
            ];

            // Inicializar destinatario con datos por defecto del applicant
            $destinatario = [
                'tipoIdentificador' => 1, // RFC
                'identificacion' => $decryptedRfc,
                'nombre' => $applicant->business_name,
                'domicilio' => [
                    'calle' => 'CORDILLERA HIMALAYA',
                    'numeroExterior' => '910',
                    'numeroInterior' => '11',
                    'codigoPostal' => '78216',
                    'colonia' => 'LOMAS 4A SECCION',
                    'localidad' => 'SAN LUIS POTOSI',
                    'municipio' => 'SAN LUIS POTOSI',
                    'entidadFederativa' => 'SAN LUIS POTOSÍ',
                    'pais' => 'MEXICO'
                ]
            ];

            // Extraer y sobreescribir con datos reales del registro 501 si está presente
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
            foreach ($lines as $line) {
                $fields = explode('|', trim($line));
                if (($fields[0] ?? '') === '501') {
                    $destinatario['nombre'] = trim($fields[21] ?? $destinatario['nombre']);
                                      // En la importación del archivo M, respetamos los campos conjuntos tal como vienen
                    $destinatario['domicilio']['calle'] = trim($fields[22] ?? '');
                    $destinatario['domicilio']['numeroInterior'] = trim($fields[23] ?? '');
                    $destinatario['domicilio']['numeroExterior'] = trim($fields[24] ?? '');
                    $destinatario['domicilio']['codigoPostal'] = trim($fields[25] ?? '');
                    
                    $coloniaRaw = trim($fields[26] ?? '');
                    $destinatario['domicilio']['colonia'] = ''; // Dejar vacío para que no se duplique, ya que viene dentro de calle/localidad
                    $destinatario['domicilio']['localidad'] = $coloniaRaw;
                    $destinatario['domicilio']['municipio'] = $coloniaRaw;

                    $entidadRaw = trim($fields[27] ?? '');
                    if (!empty($entidadRaw)) {
                        $destinatario['domicilio']['entidadFederativa'] = ($entidadRaw === 'SL' || $entidadRaw === 'SLP') ? 'SAN LUIS POTOSÍ' : $entidadRaw;
                    }

                    $paisRaw = trim($fields[28] ?? '');
                    if (!empty($paisRaw)) {
                        $destinatario['domicilio']['pais'] = ($paisRaw === 'MEX') ? 'MEXICO' : $paisRaw;
                    }
                    break;
                }
            }

            // Obtener el valor de la factura en dólares (del registro 505)
            $coveInfo = $datosExtraidos['informacion_cove'][0] ?? [];
            $valorFacturaUsd = (float)($coveInfo['valor_dolares'] ?? 0);
            if ($valorFacturaUsd <= 0) {
                $valorFacturaUsd = (float)($coveInfo['valor_factura'] ?? 0);
            }

            $mercancias = [];
            foreach (($datosExtraidos['mercancias'] ?? []) as $merc) {
                $especificas = [];
                if (!empty($merc['descripciones_especificas'])) {
                    foreach ($merc['descripciones_especificas'] as $det) {
                        $especificas[] = [
                            'marca' => $det['marca'] ?? '',
                            'modelo' => $det['modelo'] ?? '',
                            'subModelo' => $det['subModelo'] ?? '',
                            'numeroSerie' => $det['numeroSerie'] ?? '',
                        ];
                    }
                } else {
                    $especificas = [
                        [
                            'marca' => '',
                            'modelo' => '',
                            'subModelo' => '',
                            'numeroSerie' => '',
                        ]
                    ];
                }

                $cantidad = (float)($merc['cantidad'] ?? 1);
                // Si el valor comercial del archivo M difiere del de la factura (ej: pesos vs dólares),
                // usamos el valor en dólares de la factura asignado a la partida, y calculamos el unitario real
                $valorTotalUsd = ($valorFacturaUsd > 0) ? $valorFacturaUsd : (float)($merc['valor_comercial'] ?? 0);
                $unitarioReal = ($cantidad > 0) ? ($valorTotalUsd / $cantidad) : 0.0;

                $mercancias[] = [
                    'descripcionGenerica' => $merc['descripcion'] ?? 'MERCANCIA IMPORTADA',
                    'claveUnidadMedida' => 'piece',
                    'tipoMoneda' => $coveInfo['moneda'] ?? 'USD',
                    'cantidad' => $cantidad,
                    'valorUnitario' => $unitarioReal,
                    'valorTotal' => $valorTotalUsd,
                    'valorDolares' => $valorTotalUsd,
                    'descripcionesEspecificas' => $especificas
                ];
            }

            // Extraer fecha de expedición del registro 506 o en su defecto usar la fecha de entrada del pedimento (registro 501)
            $fechaExpedicion = $datosExtraidos['fechas_expedicion'][1] ?? null;
            if (!$fechaExpedicion) {
                // Intentar convertir la fecha_entrada (DD/MM/AAAA) a formato AAAA-MM-DD
                $fechaEntrada = $datosExtraidos['datos_manifestacion']['fecha_entrada'] ?? null;
                if ($fechaEntrada && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fechaEntrada, $m)) {
                    $fechaExpedicion = "{$m[3]}-{$m[2]}-{$m[1]}";
                } else {
                    $fechaExpedicion = date('Y-m-d');
                }
            }

            $coveJson = [
                'tipoOperacion' => 'IMPORTACION',
                'relacionFacturas' => 'SIN RELACION DE FACTURAS',
                'tipoFigura' => 'IMPORTADOR',
                'fechaExpedicion' => $fechaExpedicion,
                'eDocumentOriginal' => '', // Inicializado vacío para permitir adendas
                'patentesAduanales' => [$datosExtraidos['datos_manifestacion']['patente'] ?? '1628'],
                'rfcsConsulta' => [$datosExtraidos['datos_manifestacion']['rfc_agente_aduanal'] ?? 'RIRV691116P84'],
                'observaciones' => '',
                'facturas' => [
                    [
                        'numeroFactura' => $datosExtraidos['datos_manifestacion']['pedimento'] ?? '391668',
                        'fechaExpedicion' => $fechaExpedicion,
                        'subdivision' => 0,
                        'certificadoOrigen' => 0,
                        'numeroExportadorConfiable' => null,
                        'emisor' => $emisor,
                        'destinatario' => $destinatario,
                        'mercancias' => $mercancias
                    ]
                ]
            ];

            // Guardar borrador en BD
            $cove = Cove::create([
                'applicant_id' => $applicant->id,
                'factura_numero' => $coveJson['facturas'][0]['numeroFactura'],
                'factura_fecha' => $fechaExpedicion,
                'status' => 'borrador',
                'cove_json' => $coveJson
            ]);

            return redirect()->route('coves.edit', $cove)->with('success', 'Archivo M importado como Borrador de COVE.');

        } catch (Exception $e) {
            Log::error('[COVE_CONTROLLER] Error al procesar Archivo M: ' . $e->getMessage());
            return redirect()->back()->withErrors(['archivo_m' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Crea un borrador de COVE vacío de forma manual para el solicitante
     */
    public function manualCreate(MvClientApplicant $applicant)
    {
        $fechaHoy = date('Y-m-d');
        
        $emisor = [
            'tipoIdentificador' => 0,
            'identificacion' => '',
            'nombre' => '',
            'domicilio' => [
                'calle' => '',
                'numeroExterior' => '',
                'numeroInterior' => '',
                'codigoPostal' => '',
                'colonia' => '',
                'localidad' => '',
                'municipio' => '',
                'entidadFederativa' => '',
                'pais' => ''
            ]
        ];

        $destinatario = [
            'tipoIdentificador' => 1,
            'identificacion' => $applicant->applicant_rfc,
            'nombre' => $applicant->business_name,
            'domicilio' => [
                'calle' => 'CORDILLERA HIMALAYA',
                'numeroExterior' => '910',
                'numeroInterior' => '11',
                'codigoPostal' => '78216',
                'colonia' => 'LOMAS 4A SECCION',
                'localidad' => 'SAN LUIS POTOSI',
                'municipio' => 'SAN LUIS POTOSI',
                'entidadFederativa' => 'SAN LUIS POTOSÍ',
                'pais' => 'MEXICO'
            ]
        ];

        $coveJson = [
            'tipoOperacion' => 'IMPORTACION',
            'relacionFacturas' => 'SIN RELACION DE FACTURAS',
            'tipoFigura' => 'IMPORTADOR',
            'fechaExpedicion' => $fechaHoy,
            'eDocumentOriginal' => '', // Inicializado vacío para adendas
            'patentesAduanales' => ['1628'],
            'rfcsConsulta' => ['RIRV691116P84'],
            'observaciones' => '',
            'correoElectronico' => 'guillermo.aguilera@estrategiaeinnovacion.com.mx',
            'facturas' => [
                [
                    'numeroFactura' => 'NUEVO_FOLIO',
                    'fechaExpedicion' => $fechaHoy,
                    'subdivision' => 0,
                    'certificadoOrigen' => 0,
                    'numeroExportadorConfiable' => null,
                    'emisor' => $emisor,
                    'destinatario' => $destinatario,
                    'mercancias' => [
                        [
                            'descripcionGenerica' => 'MERCANCIA 1',
                            'claveUnidadMedida' => 'piece',
                            'tipoMoneda' => 'USD',
                            'cantidad' => 1.0,
                            'valorUnitario' => 0.0,
                            'valorTotal' => 0.0,
                            'valorDolares' => 0.0,
                            'descripcionesEspecificas' => [
                                [
                                    'marca' => '',
                                    'modelo' => '',
                                    'subModelo' => '',
                                    'numeroSerie' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $cove = Cove::create([
            'applicant_id' => $applicant->id,
            'factura_numero' => $coveJson['facturas'][0]['numeroFactura'],
            'factura_fecha' => $fechaHoy,
            'status' => 'borrador',
            'cove_json' => $coveJson
        ]);

        return redirect()->route('coves.edit', $cove)->with('success', 'Borrador de COVE manual creado. Por favor complete los detalles.');
    }

    /**
     * Formulario de edición del COVE borrador
     */
    public function edit(Cove $cove)
    {
        return view('cove.edit', compact('cove'));
    }

    /**
     * Previsualiza el acuse del COVE
     */
    public function preview(Cove $cove)
    {
        return view('cove.preview', compact('cove'));
    }

    /**
     * Actualiza el borrador del COVE
     */
    public function update(Request $request, Cove $cove)
    {
        $data = $request->validate([
            'cove_json' => 'required|array'
        ]);

        $currentJson = $cove->cove_json ?? [];
        $newJson = array_replace_recursive($currentJson, $data['cove_json']);

        // Asegurar que los arrays numéricos (como mercancías, patentes, rfcsConsulta) se actualicen completamente si cambiaron de tamaño
        if (isset($data['cove_json']['facturas'][0]['mercancias'])) {
            $newJson['facturas'][0]['mercancias'] = $data['cove_json']['facturas'][0]['mercancias'];
        }

        $cove->update([
            'cove_json' => $newJson,
            'factura_numero' => $newJson['facturas'][0]['numeroFactura'] ?? $cove->factura_numero
        ]);

        return redirect()->route('coves.index')->with('success', 'Borrador de COVE actualizado correctamente.');
    }

    /**
     * Transmite el COVE de forma síncrona/asíncrona
     */
    public function transmit(Cove $cove)
    {
        // Cambiar estatus y despachar Job de cola
        $cove->update(['status' => 'pendiente']);

        \App\Jobs\TransmitirCoveJob::dispatch($cove->id);

        return redirect()->route('coves.index')->with('success', 'COVE encolado para transmisión asíncrona.');
    }

    /**
     * Registra manualmente el e-Document de un COVE que ya fue enviado a VUCEM
     */
    public function registerEdocument(Request $request, Cove $cove)
    {
        $request->validate([
            'edocument' => 'required|string|min:5|max:30',
        ]);

        $cove->update([
            'status'        => 'procesado',
            'edocument'     => strtoupper(trim($request->edocument)),
            'error_mensaje' => null,
        ]);

        return redirect()->route('coves.index')->with('success', 'e-Document registrado correctamente. El COVE ahora aparece como procesado.');
    }

    /**
     * Elimina un COVE
     */
    public function destroy(Cove $cove)
    {
        $cove->delete();
        return redirect()->route('coves.index')->with('success', 'Registro de COVE eliminado correctamente.');
    }
}
