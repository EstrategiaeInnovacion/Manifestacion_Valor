<?php

namespace App\Http\Controllers;

use App\Constants\VucemCatalogs;
use App\Services\DigitalizarDocumentoService;
use App\Services\DocumentUploadService;
use App\Models\EdocumentRegistrado;
use App\Models\MvClientApplicant;
use Illuminate\Http\Request;
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

        // Tipos de documento desde catálogo centralizado
        $tiposDocumento = VucemCatalogs::$tiposDocumento;
        
        return view('digitalizacion.create', compact('tiposDocumento', 'solicitantes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|exists:mv_client_applicants,id',
            'vucem_password' => 'required|string', // Pass del Web Service
            'password_fiel' => 'nullable|string',  // Pass de la .key (Opcional si es PEM)
            'certificado_file' => 'required|file',
            'private_key_file' => 'required|file',
            'tipo_documento' => 'required|string',
            'rfc_consulta' => 'nullable|string|size:12,13', // Validación de longitud si escriben algo
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            // 1. OBTENER DATOS DEL SOLICITANTE
            $applicant = MvClientApplicant::findOrFail($request->applicant_id);
            $rfc = $applicant->applicant_rfc;
            
            // 2. LIMPIEZA CRÍTICA DEL RFC CONSULTA
            // Si viene null o vacío, forzamos cadena vacía ''.
            // Esto es VITAL para que el Servicio sepa que debe OMITIR la etiqueta en el XML.
            $rfcConsulta = $request->rfc_consulta ? strtoupper(trim($request->rfc_consulta)) : '';
            
            // Validación de negocio: Evitar auto-referencia si escribieron algo
            /*if (!empty($rfcConsulta) && $rfcConsulta === $rfc) {
                return back()->withErrors(['rfc_consulta' => 'El RFC de Consulta NO puede ser el mismo que el RFC firmante. Si es un trámite propio, deja este campo vacío.']);
            }*/

            $passwordVucem = $request->vucem_password;
            $passwordFiel = $request->password_fiel ?? ''; // Vacío si es PEM

            // 3. PROCESAR ARCHIVOS (CERT, KEY, PDF)
            $certPath = $request->file('certificado_file')->getRealPath();
            $keyPath = $request->file('private_key_file')->getRealPath();

            $file = $request->file('archivo');
            $procesado = $this->pdfService->processUploadedPdf($file);

            if (!$procesado['success']) {
                return back()->withErrors(['archivo' => 'Error al procesar PDF: ' . ($procesado['error'] ?? 'N/A')]);
            }
            
            $contenidoBase64 = $procesado['file_content'];
            $nombreArchivo = $procesado['original_name'];
            $email = auth()->user()->email;

            // 4. LLAMAR A VUCEM
            // Al pasar $rfcConsulta limpio (vacío), el servicio borrará la etiqueta <dig:rfcConsulta>
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

            // 5. PROCESAR RESPUESTA
            if ($resultado['success']) {
                // Guardar éxito en BD
                EdocumentRegistrado::create([
                    'folio_edocument' => $resultado['eDocument'],
                    'existe_en_vucem' => true,
                    'fecha_ultima_consulta' => now(),
                    'response_message' => 'Generado para ' . $rfc . ' (Tipo ' . $request->tipo_documento . ')'
                ]);

                return back()->with('success', "¡ÉXITO! eDocument Generado: " . $resultado['eDocument']);
            }

            // Manejo de errores de VUCEM
            return back()->withErrors(['error' => $resultado['message']]);

        } catch (\Exception $e) {
            Log::error("Error Controller: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error inesperado: ' . $e->getMessage()]);
        }
    }
}