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
    // ==========================================================
    // SECCIÓN 1: CONSULTA EDOCUMENT (DIGITALIZACIÓN)
    // ==========================================================
    
    public function index()
    {
        return $this->renderView('EDOCUMENT');
    }

    public function consultar(ConsultarEDocumentRequest $request, ManifestacionValorService $mvService)
    {
        return $this->processConsultation($request, $mvService, 'EDOCUMENT');
    }

    // ==========================================================
    // SECCIÓN 2: CONSULTA COVE (VALOR)
    // ==========================================================

    public function indexCove()
    {
        return $this->renderView('COVE');
    }

    public function consultarCove(ConsultarEDocumentRequest $request, ManifestacionValorService $mvService)
    {
        return $this->processConsultation($request, $mvService, 'COVE');
    }

    // ==========================================================
    // LÓGICA COMPARTIDA (PRIVADA)
    // ==========================================================

    private function renderView($mode)
    {
        $user = Auth::user();
        $solicitantes = $user->clientApplicants()->get();
        
        // Configuramos la vista dinámicamente según el modo
        $viewData = [
            'solicitantes' => $solicitantes,
            'mode' => $mode, 
            'pageTitle' => $mode === 'COVE' ? 'Consulta de COVE (Valor)' : 'Consulta de eDocument',
            'pageSubtitle' => $mode === 'COVE' ? 'Valor en Aduana y Mercancías' : 'Digitalización de Documentos',
            'formRoute' => $mode === 'COVE' ? route('cove.consulta') : route('edocument.consulta'),
            'description' => $mode === 'COVE' 
                ? 'Ingresa el eDocument (COVE) para recuperar los valores, mercancías y el XML de respuesta de VUCEM.' 
                : 'Ingresa el eDocument de digitalización para descargar los archivos PDF asociados.',
        ];

        return view('edocument.consulta', $viewData);
    }

    private function processConsultation($request, $manifestacionService, $mode)
    {
        try {
            // 1. Validaciones básicas
            $user = Auth::user();
            $solicitanteId = $request->input('solicitante_id');
            $solicitante = $user->clientApplicants()->find($solicitanteId);

            if (!$solicitante || !$solicitante->applicant_rfc || !$solicitante->ws_file_upload_key) {
                return back()->withErrors(['solicitante_id' => 'Solicitante inválido o sin credenciales VUCEM'])->withInput();
            }

            // 2. Archivos eFirma
            $certificado = $request->file('certificado');
            $llavePrivada = $request->file('llave_privada');
            $passwordLlave = $request->input('contrasena_llave');

            // Si no suben archivos, intentar usar configuración global (si aplica en tu lógica)
            // Por ahora validamos que sean obligatorios si no hay lógica global
            if (!$certificado || !$llavePrivada || !$passwordLlave) {
                return back()->withErrors(['certificado' => 'Se requieren los archivos de la e.firma para autenticar la consulta en VUCEM.'])->withInput();
            }

            // 3. Validación de Folio
            $folio = $manifestacionService->normalizeEdocumentFolio($request->input('folio_edocument'));
            $validation = $manifestacionService->validateEdocumentFolio($folio);
            if (!$validation['valid']) return back()->withErrors(['folio_edocument' => $validation['message']])->withInput();

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
                    $solicitante->ws_file_upload_key,
                    $tempCertificadoPath,
                    $tempLlavePath,
                    $passwordLlave
                );

                if (!$result['success']) {
                    return back()->withErrors(['folio_edocument' => 'VUCEM: ' . $result['message']])->withInput();
                }

                // 6. Guardar registro en BD
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
                
                // 7. Procesar archivos para vista
                $filesForView = [];
                if (!empty($result['archivos'])) {
                    foreach ($result['archivos'] as $archivo) {
                        $filesForView[] = [
                            'name' => $archivo['nombre'],
                            'mime' => $archivo['tipo'],
                            'content' => $archivo['contenido'],
                            'size' => $archivo['tamano'] ?? strlen(base64_decode($archivo['contenido'] ?? '', true) ?: ''),
                        ];
                    }
                    $filesForView = $this->storeTemporaryFiles($filesForView);
                }

                // 8. Retornar vista con datos (REUTILIZANDO renderView logic manualmente para mantener variables)
                return view('edocument.consulta', [
                    'solicitantes' => $user->clientApplicants()->get(),
                    'solicitante_seleccionado' => $solicitanteId,
                    'folio' => $folio,
                    'result' => $result,
                    'record' => $record,
                    'files' => $filesForView,
                    
                    // Variables dinámicas para mantener el contexto
                    'mode' => $mode,
                    'pageTitle' => $mode === 'COVE' ? 'Consulta de COVE (Valor)' : 'Consulta de eDocument',
                    'pageSubtitle' => $mode === 'COVE' ? 'Valor en Aduana y Mercancías' : 'Digitalización de Documentos',
                    'formRoute' => $mode === 'COVE' ? route('cove.consulta') : route('edocument.consulta'),
                    'description' => $mode === 'COVE' 
                        ? 'Resultados de la consulta de valor y mercancías en VUCEM.' 
                        : 'Documentos digitalizados recuperados de VUCEM.',
                ]);
                
            } finally {
                if (file_exists($tempCertificadoPath)) @unlink($tempCertificadoPath);
                if (file_exists($tempLlavePath)) @unlink($tempLlavePath);
            }
            
        } catch (\Exception $e) {
            Log::error('[CONSULTA] Error:', ['msg' => $e->getMessage()]);
            return back()->withErrors(['folio_edocument' => 'Error del sistema: ' . $e->getMessage()])->withInput();
        }
    }

    public function descargar(string $token)
    {
        $cacheKey = 'edocument_download:' . $token;
        $payload = Cache::get($cacheKey);

        if (!$payload) return back()->withErrors(['download' => 'El enlace de descarga ha expirado. Por favor consulte nuevamente.']);

        $user = auth()->user();
        // Validación simple de propiedad (opcionalmente podrías validar user_id si lo guardaste en cache)
        
        return response()->streamDownload(function () use ($payload) {
            echo base64_decode($payload['content']);
        }, $payload['name'], ['Content-Type' => $payload['mime']]);
    }

    private function storeTemporaryFiles(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            $token = (string) Str::uuid();
            // Guardar en caché por 60 minutos
            Cache::put('edocument_download:' . $token, [
                'user_id' => auth()->id(),
                'name' => $file['name'],
                'mime' => $file['mime'],
                'content' => $file['content'],
            ], now()->addMinutes(60));

            $stored[] = ['token' => $token, 'name' => $file['name'], 'mime' => $file['mime']];
        }
        return $stored;
    }
}