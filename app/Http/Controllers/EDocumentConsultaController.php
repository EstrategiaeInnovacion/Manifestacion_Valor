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
    public function index()
    {
        $user = Auth::user();
        $solicitantes = $user->clientApplicants()->get();
        
        return view('edocument.consulta', compact('solicitantes'));
    }

    public function consultar(
        ConsultarEDocumentRequest $request,
        ManifestacionValorService $manifestacionService
    ) {
        try {
            // 1. Validaciones
            $user = Auth::user();
            if (!$user) return back()->withErrors(['folio_edocument' => 'Usuario no autenticado'])->withInput();

            $solicitanteId = $request->input('solicitante_id');
            if (!$solicitanteId) return back()->withErrors(['solicitante_id' => 'Debe seleccionar un solicitante'])->withInput();

            $solicitante = $user->clientApplicants()->find($solicitanteId);
            if (!$solicitante) return back()->withErrors(['solicitante_id' => 'El solicitante seleccionado no es válido'])->withInput();

            $rfc = $solicitante->applicant_rfc;
            $claveWebService = $solicitante->ws_file_upload_key;

            if (!$rfc || !$claveWebService) {
                return back()->withErrors(['solicitante_id' => 'El solicitante no tiene RFC o clave VUCEM configurada'])->withInput();
            }

            // 2. Archivos eFirma
            $certificado = $request->file('certificado');
            $llavePrivada = $request->file('llave_privada');
            $passwordLlave = $request->input('contrasena_llave');

            if (!$certificado || !$llavePrivada || !$passwordLlave) {
                return back()->withErrors(['certificado' => 'Faltan archivos de eFirma'])->withInput();
            }

            // 3. Folio
            $folio = $manifestacionService->normalizeEdocumentFolio($request->input('folio_edocument'));
            $validation = $manifestacionService->validateEdocumentFolio($folio);
            if (!$validation['valid']) return back()->withErrors(['folio_edocument' => $validation['message']])->withInput();

            // 4. Cache Check (Saltado intencionalmente para debug)
            // $existingRecord = EdocumentRegistrado::where('folio_edocument', $folio)->first();

            // 5. Temporales
            $tempCertificadoPath = tempnam(sys_get_temp_dir(), 'cert_');
            $tempLlavePath = tempnam(sys_get_temp_dir(), 'key_');
            
            try {
                file_put_contents($tempCertificadoPath, $certificado->get());
                file_put_contents($tempLlavePath, $llavePrivada->get());

                // 6. Servicio
                $consultarService = app(ConsultarEdocumentService::class);
                
                Log::info('[EDOCUMENT] --- INICIANDO CONSULTA ---', ['folio' => $folio]);
                
                $result = $consultarService->consultarEdocument(
                    $folio, 
                    $rfc, 
                    $claveWebService,
                    $tempCertificadoPath,
                    $tempLlavePath,
                    $passwordLlave
                );

                // =================================================================
                // 🔍 ZONA DE DEPURACIÓN (Muestra los datos crudos en el log)
                // =================================================================
                $debug = $consultarService->getDebugInfo();
                
                Log::channel('single')->info('⬇️⬇️⬇️ REQUEST XML (LO QUE ENVIAMOS) ⬇️⬇️⬇️');
                Log::channel('single')->info($debug['last_request'] ?? 'NO DISPONIBLE');
                
                Log::channel('single')->info('⬇️⬇️⬇️ RESPONSE XML (LO QUE VUCEM RESPONDIÓ) ⬇️⬇️⬇️');
                Log::channel('single')->info($debug['last_response'] ?? 'NO DISPONIBLE');
                
                Log::channel('single')->info('⬇️⬇️⬇️ RESULTADO PROCESADO (ARRAY) ⬇️⬇️⬇️');
                Log::channel('single')->info(print_r($result, true));
                // =================================================================

                if (!$result['success']) {
                    return back()->withErrors(['folio_edocument' => $result['message']])->withInput();
                }

                // 7. Guardar y Procesar (CORREGIDO: ASIGNACIÓN A $record)
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
                
                $filesForView = [];
                
                // Caso A: Hay archivos adjuntos (PDFs)
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
                
                // Caso B: Solo hay datos COVE (Esto es éxito para nosotros)
                elseif (!empty($result['cove_data'])) {
                    Log::info('[EDOCUMENT] ÉXITO: Datos COVE recuperados (Sin PDF adjunto).');
                } 
                
                // Caso C: Respuesta vacía
                else {
                    Log::warning('[EDOCUMENT] ALERTA: VUCEM respondió éxito pero sin datos.');
                }

                $solicitantes = $user->clientApplicants()->get();
                
                // CORREGIDO: Se pasa la variable $record a la vista
                return view('edocument.consulta', [
                    'folio' => $folio,
                    'result' => $result,
                    'record' => $record, // <--- AQUÍ ESTABA EL ERROR ANTES
                    'files' => $filesForView,
                    'solicitantes' => $solicitantes,
                    'solicitante_seleccionado' => $solicitanteId,
                    'debug_xml' => $debug['last_response'] ?? '' 
                ]);
                
            } finally {
                if (file_exists($tempCertificadoPath)) @unlink($tempCertificadoPath);
                if (file_exists($tempLlavePath)) @unlink($tempLlavePath);
            }
            
        } catch (\Exception $e) {
            Log::error('[EDOCUMENT] CRITICAL ERROR:', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['folio_edocument' => 'Error: ' . $e->getMessage()])->withInput();
        }
    }

    public function descargar(string $token)
    {
        $cacheKey = $this->getCacheKey($token);
        $payload = Cache::get($cacheKey);

        if (!$payload) return back()->withErrors(['download' => 'Archivo expirado.']);

        $user = auth()->user();
        if (($payload['user_id'] ?? null) !== $user->id && !in_array($user->role, ['Admin', 'SuperAdmin'], true)) {
            abort(403, 'No tienes permiso para descargar este archivo.');
        }

        return response()->streamDownload(function () use ($payload) {
            echo base64_decode($payload['content']);
        }, $payload['name'], ['Content-Type' => $payload['mime']]);
    }

    private function storeTemporaryFiles(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            $token = (string) Str::uuid();
            Cache::put($this->getCacheKey($token), [
                'user_id' => auth()->id(),
                'name' => $file['name'],
                'mime' => $file['mime'],
                'content' => $file['content'],
            ], now()->addMinutes(60));

            $stored[] = ['token' => $token, 'name' => $file['name'], 'mime' => $file['mime']];
        }
        return $stored;
    }

    private function getCacheKey(string $token): string
    {
        return 'edocument_download:' . $token;
    }
}