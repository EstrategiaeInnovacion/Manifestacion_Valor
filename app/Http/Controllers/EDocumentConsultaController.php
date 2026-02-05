<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultarEDocumentRequest;
use App\Models\EdocumentRegistrado;
use App\Services\ManifestacionValorService;
use App\Services\ConsultarEdocumentService; 
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EDocumentConsultaController extends Controller
{
    /**
     * Muestra el formulario de consulta de COVE.
     */
    public function index()
    {
        $user = Auth::user();
        $solicitantes = $user->clientApplicants()->get();
        
        return view('edocument.consulta', [
            'solicitantes' => $solicitantes,
            'pageTitle' => 'Consulta de COVE',
            'pageSubtitle' => 'Recuperación de Valor y Mercancías',
            'description' => 'Ingresa el eDocument (COVE) para importar los valores y mercancías directamente de VUCEM.',
        ]);
    }

    /**
     * Ejecuta la consulta al Web Service de COVE.
     */
    public function consultar(ConsultarEDocumentRequest $request, ManifestacionValorService $mvService)
    {
        try {
            // 1. Validaciones básicas
            $user = Auth::user();
            $solicitanteId = $request->input('solicitante_id');
            $solicitante = $user->clientApplicants()->find($solicitanteId);

            // Validar que el solicitante exista y tenga RFC
            if (!$solicitante || !$solicitante->applicant_rfc) {
                return back()->withErrors(['solicitante_id' => 'Solicitante inválido o sin RFC configurado'])->withInput();
            }

            // 2. Archivos eFirma y Claves
            $certificado = $request->file('certificado');
            $llavePrivada = $request->file('llave_privada');
            $passwordLlave = $request->input('contrasena_llave');
            
            // CAPTURA DE LA NUEVA CLAVE WEB SERVICE DESDE EL FORMULARIO
            $claveWebService = $request->input('clave_webservice');

            if (!$certificado || !$llavePrivada || !$passwordLlave) {
                return back()->withErrors(['certificado' => 'Se requieren los archivos de la e.firma para desencriptar el COVE.'])->withInput();
            }

            // 3. Validación de Folio usando el servicio
            $folio = $mvService->normalizeEdocumentFolio($request->input('folio_edocument'));
            $validation = $mvService->validateEdocumentFolio($folio);
            
            if (!$validation['valid']) {
                return back()->withErrors(['folio_edocument' => $validation['message']])->withInput();
            }

            // 4. Preparar archivos temporales
            $tempCertificadoPath = tempnam(sys_get_temp_dir(), 'cert_');
            $tempLlavePath = tempnam(sys_get_temp_dir(), 'key_');
            
            try {
                file_put_contents($tempCertificadoPath, $certificado->get());
                file_put_contents($tempLlavePath, $llavePrivada->get());

                // 5. Llamar al Servicio VUCEM
                $consultarService = app(ConsultarEdocumentService::class);
                
                $result = $consultarService->consultarEdocument(
                    $folio, 
                    $solicitante->applicant_rfc, 
                    $claveWebService, // <--- AQUÍ SE PASA LA CLAVE MANUAL
                    $tempCertificadoPath,
                    $tempLlavePath,
                    $passwordLlave
                );

                if (!$result['success']) {
                    return back()->withErrors(['folio_edocument' => 'VUCEM: ' . $result['message']])->withInput();
                }

                // 6. Guardar registro en BD (caché local del resultado)
                $record = EdocumentRegistrado::updateOrCreate(
                    ['folio_edocument' => $folio],
                    [
                        'existe_en_vucem' => true,
                        'fecha_ultima_consulta' => now(),
                        'response_code' => '200',
                        'response_message' => $result['message'],
                        'cove_data' => isset($result['cove_data']) ? json_encode($result['cove_data']) : null,
                    ]
                );
                
                // 7. Procesar archivos XML para la vista
                $filesForView = [];
                if (!empty($result['archivos'])) {
                    foreach ($result['archivos'] as $archivo) {
                        $filesForView[] = [
                            'name' => $archivo['nombre'],
                            'mime' => $archivo['tipo'],
                            'content' => $archivo['contenido'],
                        ];
                    }
                    // Guardar en caché temporal para descarga
                    $filesForView = $this->storeTemporaryFiles($filesForView);
                }

                // 8. Retornar vista con resultados
                return view('edocument.consulta', [
                    'solicitantes' => $user->clientApplicants()->get(),
                    'solicitante_seleccionado' => $solicitanteId,
                    'folio' => $folio,
                    'result' => $result,
                    'files' => $filesForView,
                    'pageTitle' => 'Consulta de COVE',
                    'pageSubtitle' => 'Detalle de Valor y Mercancías',
                    'description' => 'Información recuperada exitosamente de VUCEM.',
                ]);
                
            } finally {
                // Limpieza de archivos temporales
                if (file_exists($tempCertificadoPath)) @unlink($tempCertificadoPath);
                if (file_exists($tempLlavePath)) @unlink($tempLlavePath);
            }
            
        } catch (\Exception $e) {
            Log::error('[COVE] Error:', ['msg' => $e->getMessage()]);
            return back()->withErrors(['folio_edocument' => 'Error del sistema: ' . $e->getMessage()])->withInput();
        }
    }

    public function descargar(string $token)
    {
        $cacheKey = 'edocument_download:' . $token;
        $payload = Cache::get($cacheKey);

        if (!$payload) return back()->withErrors(['download' => 'El archivo ha expirado.']);

        return response()->streamDownload(function () use ($payload) {
            echo base64_decode($payload['content']);
        }, $payload['name'], ['Content-Type' => $payload['mime']]);
    }

    private function storeTemporaryFiles(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            $token = (string) Str::uuid();
            Cache::put('edocument_download:' . $token, [
                'name' => $file['name'],
                'mime' => $file['mime'],
                'content' => $file['content'],
            ], now()->addMinutes(60));

            $stored[] = ['token' => $token, 'name' => $file['name'], 'mime' => $file['mime']];
        }
        return $stored;
    }
}