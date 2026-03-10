<?php

namespace App\Http\Controllers;

use App\Constants\VucemCatalogs;
use App\Services\DigitalizarDocumentoService;
use App\Services\DocumentUploadService;
use App\Models\EdocumentRegistrado;
use App\Models\MvClientApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DigitalizacionController extends Controller
{
    protected $vucemService;
    protected $pdfService;

    public function __construct(DigitalizarDocumentoService $vucemService, DocumentUploadService $pdfService)
    {
        $this->vucemService = $vucemService;
        $this->pdfService = $pdfService;
    }

    public function create()
    {
        // Obtener solicitantes (RFCs Dueños)
        $solicitantes = MvClientApplicant::where('user_email', auth()->user()->getApplicantOwnerEmail())
            ->select('id', 'applicant_rfc', 'business_name')
            ->get();

        // Obtener flags de credenciales configuradas sin desencriptar los valores grandes
        $ids = $solicitantes->pluck('id')->toArray();
        $credentialFlags = [];
        if (!empty($ids)) {
            $rows = DB::table('mv_client_applicants')
                ->whereIn('id', $ids)
                ->selectRaw('id,
                    (vucem_cert_file IS NOT NULL) as has_cert,
                    (vucem_key_file IS NOT NULL) as has_key,
                    (vucem_password IS NOT NULL) as has_fiel_pass,
                    (vucem_webservice_key IS NOT NULL) as has_ws_key')
                ->get();
            foreach ($rows as $row) {
                $credentialFlags[$row->id] = [
                    'has_cert'      => (bool) $row->has_cert,
                    'has_key'       => (bool) $row->has_key,
                    'has_fiel_pass' => (bool) $row->has_fiel_pass,
                    'has_ws_key'    => (bool) $row->has_ws_key,
                ];
            }
        }

        // Tipos de documento desde catálogo centralizado
        $tiposDocumento = VucemCatalogs::$tiposDocumento;

        // Últimos 20 eDocuments del usuario (para la tabla de historial)
        $applicantIds = $solicitantes->pluck('id')->toArray();
        $edocuments = EdocumentRegistrado::whereIn('applicant_id', $applicantIds)
            ->orderByDesc('created_at')
            ->limit(20)
            ->with('applicant:id,applicant_rfc')
            ->get();

        return view('digitalizacion.create', compact('tiposDocumento', 'solicitantes', 'credentialFlags', 'edocuments'));
    }

    public function store(Request $request)
    {
        // 1. Validar applicant_id primero para poder cargar el solicitante
        $request->validate(['applicant_id' => 'required|exists:mv_client_applicants,id']);
        $applicant = MvClientApplicant::findOrFail($request->applicant_id);

        // 2. Reglas dinámicas según credenciales guardadas
        $request->validate([
            'vucem_password'   => $applicant->vucem_webservice_key ? 'nullable|string' : 'required|string',
            'password_fiel'    => 'nullable|string',
            'certificado_file' => $applicant->vucem_cert_file ? 'nullable|file' : 'required|file',
            'private_key_file' => $applicant->vucem_key_file  ? 'nullable|file' : 'required|file',
            'tipo_documento'   => 'required|string',
            'rfc_consulta'     => 'nullable|string',
            'archivo'          => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $tempPaths = [];

        try {
            $rfc = $applicant->applicant_rfc;

            $rfcConsulta = $request->rfc_consulta ? strtoupper(trim($request->rfc_consulta)) : '';

            // Contraseña Web Service: formulario tiene prioridad, luego la guardada
            $passwordVucem = trim($request->vucem_password ?? '') ?: ($applicant->vucem_webservice_key ?? '');
            // Contraseña FIEL (llave .key): formulario tiene prioridad, luego la guardada
            $passwordFiel  = trim($request->password_fiel ?? '') ?: ($applicant->vucem_password ?? '');

            // Certificado .cer
            if ($request->hasFile('certificado_file')) {
                $certPath = $request->file('certificado_file')->getRealPath();
            } elseif ($applicant->vucem_cert_file) {
                $certPath = $this->writeTempFile($applicant->vucem_cert_file, '.cer');
                $tempPaths[] = $certPath;
            } else {
                return back()->withErrors(['certificado_file' => 'El solicitante no tiene certificado .cer configurado. Súbelo manualmente.']);
            }

            // Llave privada .key
            if ($request->hasFile('private_key_file')) {
                $keyPath = $request->file('private_key_file')->getRealPath();
            } elseif ($applicant->vucem_key_file) {
                $keyPath = $this->writeTempFile($applicant->vucem_key_file, '.key');
                $tempPaths[] = $keyPath;
            } else {
                return back()->withErrors(['private_key_file' => 'El solicitante no tiene llave privada .key configurada. Súbela manualmente.']);
            }

            // PDF
            $procesado = $this->pdfService->processUploadedPdf($request->file('archivo'));
            if (!$procesado['success']) {
                return back()->withErrors(['archivo' => 'Error al procesar PDF: ' . ($procesado['error'] ?? 'N/A')]);
            }

            $contenidoBase64 = $procesado['file_content'];
            $nombreArchivo   = $procesado['original_name'];
            $email           = auth()->user()->email;

            $resultado = $this->vucemService->digitalizarDocumento(
                $rfc,
                $passwordVucem,
                $request->tipo_documento,
                $nombreArchivo,
                $contenidoBase64,
                $certPath,
                $keyPath,
                $passwordFiel,
                $email,
                $rfcConsulta
            );

            if ($resultado['success']) {
                EdocumentRegistrado::create([
                    'folio_edocument'       => $resultado['eDocument'],
                    'applicant_id'          => $applicant->id,
                    'numero_operacion'      => $resultado['numero_operacion'] ?? null,
                    'tipo_documento'        => $request->tipo_documento,
                    'nombre_documento'      => $nombreArchivo,
                    'existe_en_vucem'       => true,
                    'fecha_ultima_consulta' => now(),
                    'response_message'      => 'Generado para ' . $rfc . ' (Tipo ' . $request->tipo_documento . ')',
                ]);
                return back()->with('success', "¡ÉXITO! eDocument Generado: " . $resultado['eDocument']);
            }

            return back()->withErrors(['error' => $resultado['message']]);

        } catch (\Exception $e) {
            Log::error("Error Controller: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error inesperado: ' . $e->getMessage()]);
        } finally {
            foreach ($tempPaths as $path) {
                if ($path && file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * Consulta el folio eDocument en VUCEM usando el número de operación guardado.
     */
    public function consultarOperacion($id)
    {
        $edoc = EdocumentRegistrado::findOrFail($id);

        if (!$edoc->numero_operacion || !str_starts_with($edoc->folio_edocument, 'PENDIENTE')) {
            return response()->json(['success' => false, 'message' => 'Este registro no tiene operación pendiente.']);
        }

        $applicant = $edoc->applicant;
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'No se encontró el solicitante asociado.']);
        }

        $tempPaths = [];
        try {
            $passwordVucem = $applicant->vucem_webservice_key ?? '';
            $passwordFiel  = $applicant->vucem_password ?? '';

            if (!$applicant->vucem_cert_file) {
                return response()->json(['success' => false, 'message' => 'El solicitante no tiene certificado .cer configurado.']);
            }
            $certPath = $this->writeTempFile($applicant->vucem_cert_file, '.cer');
            $tempPaths[] = $certPath;

            if (!$applicant->vucem_key_file) {
                return response()->json(['success' => false, 'message' => 'El solicitante no tiene llave privada .key configurada.']);
            }
            $keyPath = $this->writeTempFile($applicant->vucem_key_file, '.key');
            $tempPaths[] = $keyPath;

            $resultado = $this->vucemService->consultarPorOperacion(
                $applicant->applicant_rfc,
                $passwordVucem,
                $edoc->numero_operacion,
                $certPath,
                $keyPath,
                $passwordFiel
            );

            if ($resultado && !empty($resultado['eDocument'])) {
                $edoc->update([
                    'folio_edocument'       => $resultado['eDocument'],
                    'fecha_ultima_consulta' => now(),
                    'response_message'      => 'Folio obtenido por consulta de operación ' . $edoc->numero_operacion,
                ]);
                return response()->json(['success' => true, 'folio' => $resultado['eDocument']]);
            }

            return response()->json(['success' => false, 'message' => 'VUCEM aún no tiene el folio disponible. Intente de nuevo en unos segundos.']);

        } catch (\Exception $e) {
            Log::error('[CONSULTA_OPERACION] Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } finally {
            foreach ($tempPaths as $path) {
                if ($path && file_exists($path)) @unlink($path);
            }
        }
    }

    /**
     * Escribe contenido base64 en un archivo temporal y devuelve la ruta.
     */
    private function writeTempFile(string $base64Data, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vucem_') . $extension;
        file_put_contents($path, base64_decode($base64Data));
        return $path;
    }
}