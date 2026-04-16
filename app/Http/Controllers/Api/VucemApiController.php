<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MvClientApplicant;
use App\Services\ConsultarEdocumentService;
use App\Services\MveConsultaService;
use App\Services\VucemPedimentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class VucemApiController extends Controller
{
    private ConsultarEdocumentService $edocumentService;
    private MveConsultaService $mveConsultaService;
    private VucemPedimentoService $pedimentoService;

    public function __construct()
    {
        $this->edocumentService = new ConsultarEdocumentService(
            app(\App\Services\EFirmaService::class)
        );
        $this->mveConsultaService = new MveConsultaService();
        $this->pedimentoService = new VucemPedimentoService(
            app(\App\Services\EFirmaService::class)
        );
    }

    /**
     * Find applicant by RFC from request
     *
     * @param Request $request
     * @return MvClientApplicant|null
     */
    private function findApplicant(Request $request): ?MvClientApplicant
    {
        $rfc = $request->input('rfc');
        
        if (empty($rfc)) {
            return null;
        }

        $rfc = strtoupper(trim($rfc));
        
        $applicants = MvClientApplicant::all();
        foreach ($applicants as $app) {
            if (strtoupper($app->applicant_rfc) === $rfc) {
                return $app;
            }
        }
        
        return null;
    }

    /**
     * Get credentials from applicant
     *
     * @param MvClientApplicant $applicant
     * @return array|null
     */
    private function getApplicantCredentials(MvClientApplicant $applicant): ?array
    {
        if (!$applicant->hasVucemCredentials()) {
            return null;
        }

        if (!$applicant->hasWebserviceKey()) {
            return null;
        }

        return [
            'rfc' => $applicant->applicant_rfc,
            'vucem_key' => $applicant->vucem_webservice_key,
            'cert_file' => $applicant->vucem_cert_file,
            'key_file' => $applicant->vucem_key_file,
            'password' => $applicant->vucem_password,
        ];
    }

    /**
     * Build standardized response
     *
     * @param bool $success
     * @param string $message
     * @param array $data
     * @param array $archivos
     * @return JsonResponse
     */
    private function buildResponse(bool $success, string $message, array $data = [], array $archivos = []): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        if (!empty($archivos)) {
            $response['archivos'] = array_map(function ($archivo) {
                return [
                    'nombre' => $archivo['nombre'] ?? 'documento.pdf',
                    'tipo' => $archivo['tipo'] ?? 'application/pdf',
                    'contenido' => base64_encode($archivo['contenido']),
                ];
            }, $archivos);
        }

        return response()->json($response);
    }

    /**
     * Build error response
     *
     * @param string $errorType
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    private function buildErrorResponse(string $errorType, string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $errorType,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * POST /api/vucem/consultar-edocument
     *
     * Consultar e-Document por folio
     */
    public function consultarEdocument(Request $request): JsonResponse
    {
        try {
            $folio = $request->input('folio');
            
            if (empty($folio)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "folio" es requerido.');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $certFile = $credentials['cert_file'];
            $keyFile = $credentials['key_file'];
            
            if (!empty($certFile)) {
                $certPath = storage_path('app/temp/cert_' . $applicant->id . '.cer');
                @mkdir(dirname($certPath), 0755, true);
                file_put_contents($certPath, base64_decode($certFile));
            }
            
            if (!empty($keyFile)) {
                $keyPath = storage_path('app/temp/key_' . $applicant->id . '.key');
                @mkdir(dirname($keyPath), 0755, true);
                file_put_contents($keyPath, base64_decode($keyFile));
            }

            $result = $this->edocumentService->consultarEdocument(
                $folio,
                $credentials['rfc'],
                $credentials['vucem_key'],
                $certFile ? $certPath : '',
                $keyFile ? $keyPath : '',
                $credentials['password']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            $archivos = [];
            if (!empty($result['archivos'])) {
                foreach ($result['archivos'] as $archivo) {
                    $decoded = base64_decode($archivo['contenido']);
                    if ($decoded !== false) {
                        $archivos[] = [
                            'nombre' => $archivo['nombre'],
                            'tipo' => $archivo['tipo'],
                            'contenido' => $decoded,
                        ];
                    }
                }
            }

            return $this->buildResponse(
                true,
                $result['message'] ?? 'Consulta exitosa',
                [
                    'folio' => $folio,
                    'cove_data' => $result['cove_data'] ?? null,
                ],
                $archivos
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en consultarEdocument', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/edocument/acuse
     *
     * Obtener acuse PDF de e-Document
     */
    public function obtenerAcuseEdocument(Request $request): JsonResponse
    {
        try {
            $folio = $request->input('folio');
            
            if (empty($folio)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "folio" es requerido.');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $result = $this->mveConsultaService->consultarEdocumentAcuse(
                $folio,
                $credentials['rfc'],
                $credentials['vucem_key']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            if (empty($result['acuse_pdf'])) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró acuse PDF para el folio proporcionado.');
            }

            $decoded = base64_decode($result['acuse_pdf']);
            
            return $this->buildResponse(
                true,
                'Acuse obtenido exitosamente',
                [
                    'folio' => $folio,
                ],
                [
                    [
                        'nombre' => 'acuse_edocument_' . $folio . '.pdf',
                        'tipo' => 'application/pdf',
                        'contenido' => $decoded,
                    ],
                ]
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en obtenerAcuseEdocument', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/consultar-cove
     *
     * Consultar datos estructurados de COVE
     */
    public function consultarCove(Request $request): JsonResponse
    {
        try {
            $folio = $request->input('folio');
            
            if (empty($folio)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "folio" es requerido.');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $certFile = $credentials['cert_file'];
            $keyFile = $credentials['key_file'];
            
            if (!empty($certFile)) {
                $certPath = storage_path('app/temp/cert_' . $applicant->id . '.cer');
                @mkdir(dirname($certPath), 0755, true);
                file_put_contents($certPath, base64_decode($certFile));
            }
            
            if (!empty($keyFile)) {
                $keyPath = storage_path('app/temp/key_' . $applicant->id . '.key');
                @mkdir(dirname($keyPath), 0755, true);
                file_put_contents($keyPath, base64_decode($keyFile));
            }

            $result = $this->edocumentService->consultarEdocument(
                $folio,
                $credentials['rfc'],
                $credentials['vucem_key'],
                $certFile ? $certPath : '',
                $keyFile ? $keyPath : '',
                $credentials['password']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            return $this->buildResponse(
                true,
                $result['message'] ?? 'Consulta exitosa',
                [
                    'folio' => $folio,
                    'cove_data' => $result['cove_data'] ?? null,
                ]
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en consultarCove', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/cove/acuse
     *
     * Obtener acuse PDF de COVE
     */
    public function obtenerAcuseCove(Request $request): JsonResponse
    {
        try {
            $folio = $request->input('folio');
            
            if (empty($folio)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "folio" es requerido.');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $result = $this->mveConsultaService->consultarCoveAcuse(
                $folio,
                $credentials['rfc'],
                $credentials['vucem_key']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            if (empty($result['acuse_pdf'])) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró acuse PDF para el COVE proporcionado.');
            }

            $decoded = base64_decode($result['acuse_pdf']);
            
            return $this->buildResponse(
                true,
                'Acuse de COVE obtenido exitosamente',
                [
                    'folio' => $folio,
                ],
                [
                    [
                        'nombre' => 'acuse_cove_' . $folio . '.pdf',
                        'tipo' => 'application/pdf',
                        'contenido' => $decoded,
                    ],
                ]
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en obtenerAcuseCove', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/consultar-mve
     *
     * Consultar Manifestación de Valor por número de operación
     */
    public function consultarMve(Request $request): JsonResponse
    {
        try {
            $folio = $request->input('folio');
            
            if (empty($folio)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "folio" es requerido (número de operación o número MVE).');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $result = $this->mveConsultaService->consultarManifestacion(
                $folio,
                $credentials['rfc'],
                $credentials['vucem_key']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            $archivos = [];
            if (!empty($result['acuse_pdf'])) {
                $decoded = base64_decode($result['acuse_pdf']);
                if ($decoded !== false) {
                    $archivos[] = [
                        'nombre' => 'acuse_mve_' . ($result['numero_mv'] ?? $folio) . '.pdf',
                        'tipo' => 'application/pdf',
                        'contenido' => $decoded,
                    ];
                }
            }

            return $this->buildResponse(
                true,
                $result['message'] ?? 'Consulta exitosa',
                [
                    'numero_operacion' => $folio,
                    'numero_mv' => $result['numero_mv'] ?? null,
                    'status' => $result['status'] ?? null,
                    'fecha_registro' => $result['fecha_registro'] ?? null,
                    'datos_manifestacion' => $result['datos_manifestacion'] ?? null,
                ],
                $archivos
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en consultarMve', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/mve/acuse
     *
     * Obtener acuse PDF de Manifestación de Valor
     */
    public function obtenerAcuseMve(Request $request): JsonResponse
    {
        try {
            $folio = $request->input('folio');
            
            if (empty($folio)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "folio" es requerido.');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $consultaResult = $this->mveConsultaService->consultarManifestacion(
                $folio,
                $credentials['rfc'],
                $credentials['vucem_key']
            );

            if (!$consultaResult['success'] || empty($consultaResult['acuse_pdf'])) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró acuse PDF para la Manifestación de Valor.');
            }

            $decoded = base64_decode($consultaResult['acuse_pdf']);
            
            return $this->buildResponse(
                true,
                'Acuse de MVE obtenido exitosamente',
                [
                    'numero_operacion' => $folio,
                    'numero_mv' => $consultaResult['numero_mv'] ?? null,
                ],
                [
                    [
                        'nombre' => 'acuse_mve_' . ($consultaResult['numero_mv'] ?? $folio) . '.pdf',
                        'tipo' => 'application/pdf',
                        'contenido' => $decoded,
                    ],
                ]
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en obtenerAcuseMve', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/consultar-pedimento
     *
     * Consultar pedimento completo
     */
    public function consultarPedimento(Request $request): JsonResponse
    {
        try {
            $numeroPedimento = $request->input('pedimento');
            $patente = $request->input('patente');
            $aduana = $request->input('aduana');
            
            if (empty($numeroPedimento)) {
                return $this->buildErrorResponse('VALIDATION_ERROR', 'El campo "pedimento" es requerido.');
            }

            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $result = $this->pedimentoService->consultarPedimento(
                $numeroPedimento,
                $patente,
                $aduana,
                $credentials['rfc'],
                $credentials['vucem_key'],
                $credentials['cert_file'],
                $credentials['key_file'],
                $credentials['password']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            return $this->buildResponse(
                true,
                $result['message'] ?? 'Consulta exitosa',
                $result['data'] ?? []
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en consultarPedimento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vucem/listar-pedimentos
     *
     * Listar pedimentos del solicitante
     */
    public function listarPedimentos(Request $request): JsonResponse
    {
        try {
            $applicant = $this->findApplicant($request);
            
            if (!$applicant) {
                return $this->buildErrorResponse('NOT_FOUND', 'No se encontró ningún solicitante con el RFC proporcionado.');
            }

            $credentials = $this->getApplicantCredentials($applicant);
            
            if (!$credentials) {
                return $this->buildErrorResponse('CREDENTIALS_MISSING', 'El solicitante no tiene credenciales VUCEM configuradas.');
            }

            $result = $this->pedimentoService->listarPedimentos(
                $credentials['rfc'],
                $credentials['vucem_key'],
                $credentials['cert_file'],
                $credentials['key_file'],
                $credentials['password']
            );

            if (!$result['success']) {
                return $this->buildErrorResponse('VUCEM_ERROR', $result['message']);
            }

            return $this->buildResponse(
                true,
                $result['message'] ?? 'Listado exitoso',
                $result['data'] ?? []
            );

        } catch (Exception $e) {
            Log::error('[VUCEM_API] Error en listarPedimentos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildErrorResponse('INTERNAL_ERROR', 'Error interno: ' . $e->getMessage(), 500);
        }
    }
}